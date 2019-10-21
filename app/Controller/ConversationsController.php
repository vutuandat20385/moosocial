<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ConversationsController extends AppController 
{
    
    
    public function show() {
        $this->_checkPermission();
        $uid = $this->Auth->user('id');

        $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        $this->loadModel('ConversationUser');

        $this->Conversation->unbindModel(
                array('belongsTo' => array('User'))
        );

        $this->Conversation->unbindModel(
                array('hasMany' => array('Comment'))
        );

        $this->Conversation->bindModel(array
        (
            'belongsTo' => array
            (
                'LastReply' => array
                (
                    'className'     => 'Comment',
                    'foreignKey' => 'last_reply_id',
                )
            )
        ));

        $this->ConversationUser->recursive = 3;
        $conversations = $this->ConversationUser->find('all', array('conditions' => array('ConversationUser.user_id' => $uid),
            'limit' => 10,
            'page' => $page,
            'order' => 'modified desc'
                ));

        $this->set('conversations', $conversations);
    }

    public function ajax_browse()
	{
		$this->_checkPermission();
		$uid = $this->Auth->user('id');
		
		$page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
		$this->loadModel( 'ConversationUser' );

		$this->Conversation->unbindModel(
			array('belongsTo' => array('User'))
		);
        
        $this->Conversation->unbindModel(
            array('hasMany' => array('Comment'))
        );

        $this->Conversation->bindModel(array
        (
            'belongsTo' => array
            (
                'LastReply' => array
                (
                    'className'     => 'Comment',
                    'foreignKey' => 'last_reply_id',
                )
            )
        ));

        $this->ConversationUser->recursive = 3;
		$conversations = $this->ConversationUser->find( 'all', array( 'conditions' => array( 'ConversationUser.user_id' => $uid ), 
																	  'limit' => RESULTS_LIMIT, 
												 					  'page' => $page,
																	  'order' => 'modified desc'
													)	);

		$this->set('conversations', $conversations);
		$this->set('more_url', '/conversations/ajax_browse/page:' . ( $page + 1 ) ) ;
		
		if ( $page > 1 )
			$this->render('/Elements/lists/messages_list');
	}
	
	public function ajax_send($recipient = null)
	{
		$this->_checkPermission( array( 'confirm' => true ) );
		$uid = $this->Auth->user('id');

		if ( !empty($recipient) )
		{
                     $this->_checkPermission( array('user_block' => $recipient) ); 
			$this->loadModel( 'User' );
            $this->loadModel('Friend');

            $to = $this->User->findById($recipient);
			$this->_checkExistence( $to );
			$roles = $this->_getUserRoleParams();
            $allow_send_message_to_non_friend = in_array('message_send_non_member', $roles);
            if ($allow_send_message_to_non_friend) {
                if(empty($to['User']['receive_message_from_non_friend'])){
                    $areFriend = $this->Friend->areFriends($uid, $to['User']['id']);
                    if(!$areFriend)
                        $this->set('notAllow', 1);
                }
            }
            else {
                $areFriend = $this->Friend->areFriends($uid, $to['User']['id']);
                if(!$areFriend)
                    $this->set('notAllow', 1);
            }
			$this->set('to', $to);
		}
	}

	public function ajax_doSend()
	{			
		$this->autoRender = false;
		$this->_checkPermission( array( 'confirm' => true ) );
		
		$uid = $this->Auth->user('id');
        $recipients = explode( ',', $this->request->data['friends'] );

		$this->request->data['user_id'] = $uid;

        if(!empty($recipients)){
            $this->request->data['lastposter_id'] = $uid;
            $this->request->data['other_last_poster'] = end($recipients);
        }else {
            $this->request->data['lastposter_id'] = $uid;
        }
		
		$this->Conversation->set( $this->request->data );
		$this->_validateData( $this->Conversation );
		
		// @todo: validate recipients
		
		if ( !empty($this->request->data['friends']) )
		{
			if ( $this->Conversation->save() ) // successfully saved	
			{
				// save convo users
				$participants = array();
				$roles = $this->_getUserRoleParams();
				$allow_send_message_to_non_friend = in_array('message_send_non_member', $roles);

                $this->loadModel('Friend');
				foreach ( $recipients as $participant ) {
                    if ($allow_send_message_to_non_friend) {
                        $to = $this->User->findById($participant);
                        if(empty($to['User']['receive_message_from_non_friend'])){
                            $areFriend = $this->Friend->areFriends($uid, $participant);
                            if(!$areFriend)
                                continue;
                        }
                    }
                    else {
                        $areFriend = $this->Friend->areFriends($uid, $participant);
                        if(!$areFriend)
                            continue;
                    }

					$participants[] = array('conversation_id' => $this->Conversation->id, 'user_id' => $participant);

                    // send private mail
                    /*$to = $this->User->findById($participant);
                    if($to['User']['send_email_when_send_message'] == 1) {
                        $ssl_mode = Configure::read('core.ssl_mode');
                        $http = (!empty($ssl_mode)) ? 'https' :  'http';
                        $mailComponent = MooCore::getInstance()->getComponent('Mail.MooMail');
                        $request = Router::getRequest();
                        $sender_user = $this->User->findById($uid);
                        $core = Configure::read('core');
                        $params = array(
                            'sender_link' => $http.'://'.$_SERVER['SERVER_NAME'].$request->base.$sender_user['User']['moo_url'],
                            'sender_title' => $sender_user['User']['name'],
                            'time' => date("Y-m-d H:i:s"),
                            'message_link' => $http.'://'.$_SERVER['SERVER_NAME'].$request->base.'/conversations/view/'.$this->Conversation->id,
                            'site_name' => $core['site_name'],
                        );                        
                        $mailComponent->send($to['User']['email'],'private_message',$params);
                    }*/
                    // end

                }
				// add sender to convo users array
				$participants[] = array('conversation_id' => $this->Conversation->id, 'user_id' => $uid, 'unread' => 0);

				$this->loadModel( 'ConversationUser' );
				$this->ConversationUser->saveAll( $participants );
				
                                // MOOSOCIAL-2876
				/*$this->loadModel( 'Notification' );
				$this->Notification->record( array( 'recipients' => $recipients,
													'sender_id' => $uid,
													'action' => 'message_send',
													'url' => '/conversations/view/'.$this->Conversation->id
				) );*/
				
				
				$response['result'] = 1;
                $response['id'] = $this->Conversation->id;
                echo json_encode($response);
			}
		}
		else
			$this->_jsonError(__('Recipient is required'));
	}

	public function view($id)
	{
		$id = intval($id);
		$this->_checkPermission();
		$uid = $this->Auth->user('id');
                
		$conversation = $this->Conversation->findById($id);
		$this->_checkExistence( $conversation );

		// check permission to view
		$this->loadModel('ConversationUser');
		$convo_users = $this->ConversationUser->findAllByConversationId($id);
		$users_array = array();
                $users_info = array();
		foreach ($convo_users as $user)
		{                       
			$users_array[] = $user['ConversationUser']['user_id'];
                        $users_info[$user['ConversationUser']['user_id']] = $user['User'];
			if ( $uid == $user['ConversationUser']['user_id'] )
				$convo_user = $user['ConversationUser'];
		}
                
		$this->_checkPermission( array( 'admins' => $users_array ) );
                
		// set to read if unread
		if ( $convo_user['unread'] )
		{
			$this->ConversationUser->id = $convo_user['id'];
			$this->ConversationUser->save( array( 'unread' => 0 ,'check_send' => 0) );
		}
		
		//mark read notify
		$this->loadModel("Notification");
		$notify = $this->Notification->find( 'first', array( 'conditions' => array( 'Notification.user_id' => $uid,
				'Notification.url' => '/conversations/view/'.$conversation['Conversation']['id'],
				'Notification.action' => 'message_send',
				'Notification.read' => 0
		) ) );
		
		if ($notify)
		{
			$this->Notification->clear();
			$this->Notification->id = $notify['Notification']['id'];
			$this->Notification->save( array( 'read' => 1 ) );
		}

		// get messages
		$this->loadModel('Comment');
		$comments = $this->Comment->getComments( $id, APP_CONVERSATION );
		
		// get friends
		$this->loadModel( 'Friend' );
		$friends = $this->Friend->getFriends($uid);
		
		$this->set('convo_users', $convo_users);
		$pair_blocker = array();
		if (count($convo_users) ==  2)
		{
			$this->loadModel("UserBlock");
			$pair_blocker = $this->UserBlock->getPairBlockUser($users_array);

			if(count($pair_blocker)){
                foreach ($pair_blocker as $key_block => $value_block) {
                	$pair_blocker['block_user'] = $users_info[$key_block];
                    $pair_blocker['blocked_user'] = $users_info[$value_block];
            	}
            }
		}
		$this->set('pair_blocker', $pair_blocker);
                
		$this->set('friends', $friends);
		$this->set('conversation', $conversation);
                
                $this->set('comment_type', APP_CONVERSATION);
		
		$this->set('title_for_layout', htmlspecialchars($conversation['Conversation']['subject']));
                $data = array();
                $page = 1 ;
                
                $data = array(
                    'bIsCommentloadMore' => $conversation['Conversation']['message_count'] - $page * RESULTS_LIMIT,
                    'more_comments' => '/comments/browse/conversation/' . $id . '/page:' . ($page + 1),
                    'comments' => $comments
                );
                $this->set('data', $data);
	}
	
	public function mark_all_read()
	{
	        $uid = $this->Auth->user('id');
	        $this->loadModel('ConversationUser');
	        $convo_users = $this->ConversationUser->findAllByUserId($uid);
	        foreach($convo_users as $user){
	            if ( $user['ConversationUser']['unread'] )
	            {
	                $this->ConversationUser->id = $user['ConversationUser']['id'];
	                $this->ConversationUser->save( array( 'unread' => 0 ) );

	            }
	        }
	        $this->redirect($this->referer());
	}
	public function ajax_add($msg_id = null)
	{
		$msg_id = intval($msg_id);
		$this->_checkPermission( array( 'confirm' => true ) );

		$this->set('msg_id', $msg_id);
	}
	
	public function ajax_doAdd()
	{			
		$this->autoRender = false;
		$this->_checkPermission( array( 'confirm' => true ) );
		
		if ( !empty($this->request->data['friends']) )
		{		
			$msg_id = $this->request->data['msg_id'];
			$uid = $this->Auth->user('id');
            $friends = explode(',', $this->request->data['friends']);
			
			$this->loadModel( 'ConversationUser' );
			$users = $this->ConversationUser->getUsersList( $msg_id );
			$this->_checkPermission( array( 'admins' => $users ) ); // check to see if the user is a participant
			
			
			
			$participants = array();
			foreach ( $friends as $participant )
                if ( !in_array($participant, $users) )
				    $participants[] = array('conversation_id' => $msg_id, 'user_id' => $participant);
	
            if ( !empty($participants) )
            {
    			$this->ConversationUser->saveAll( $participants );
    			
    			$this->loadModel( 'Notification' );
    			$this->Notification->record( array( 'recipients' => $friends,
    												'sender_id' => $uid,
    												'action' => 'conversation_add',
    												'url' => '/conversations/view/'.$msg_id
    			) );
            }
            
            $response['result'] = 1;
		}
		else
        {
            $response['result'] = 0;
            $response['message'] = __('Please select at least one person');        
        }
        
        echo json_encode($response);
	}
	
	public function do_leave( $msg_id = null )
	{
		$msg_id = intval($msg_id);
		$this->_checkPermission( array( 'confirm' => true ) );
		$uid = $this->Auth->user('id');
		
		$this->loadModel( 'ConversationUser' );
		$this->ConversationUser->deleteAll( array( 'conversation_id' => $msg_id, 'ConversationUser.user_id' => $uid ), true, true );
		if (!$this->ConversationUser->hasAny(array('conversation_id' => $msg_id))) {//all users had left the conversation
            $this->Conversation->delete($msg_id);
        }
		$this->redirect( '/home/index/tab:messages' );
	}
        
        public function mark_read(){
            $this->autoRender = false;
            $id = isset($this->request->data['id']) ? $this->request->data['id'] : 0;
            $status = isset($this->request->data['status']) ? $this->request->data['status'] : 0;
            $viewer_id = MooCore::getInstance()->getViewer(true);
            $this->loadModel('ConversationUser');
            
            $conversationUser = $this->ConversationUser->find('all', array('conditions' => array('ConversationUser.conversation_id' => $id, 'ConversationUser.user_id' => $viewer_id)));
            foreach($conversationUser as $item){
                $this->ConversationUser->clear();
                $this->ConversationUser->id = $item['ConversationUser']['id'];
                $this->ConversationUser->save(array('unread' => $status));
            }
            
            echo json_encode(array('success' => true, 'status' => $status));
        }
}

?>
