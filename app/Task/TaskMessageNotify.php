<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('Cron.Task','CronTaskAbstract');
class TaskMessageNotify extends CronTaskAbstract
{
    public function execute()
    {
    	if (!Configure::read('core.time_notify_message_unread'))
    	{
    		return;
    	}
    	$time = Configure::read('core.time_notify_message_unread') * 60;
    	$modelConversation = MooCore::getInstance()->getModel("ConversationUser");
    	
    	$results = $modelConversation->find('all',array(
    			'conditions' => array(
    					'ConversationUser.modified <' => date('Y-m-d H:i:s',strtotime('-'.$time.' seconds')),
    					'ConversationUser.unread' => 1,
    					'ConversationUser.check_send' => 0,
    			)
    	));
    	$mailComponent = MooCore::getInstance()->getComponent('Mail.MooMail');
    	$ssl_mode = Configure::read('core.ssl_mode');
    	$http = (!empty($ssl_mode)) ? 'https' :  'http';
    	$request = Router::getRequest();
    	$commentModel = MooCore::getInstance()->getModel("Comment");
    	$userModel = MooCore::getInstance()->getModel("User");
    	$notificationModel = MooCore::getInstance()->getModel("Notification");
    	
    	foreach ($results as $result)
    	{
    		$modelConversation->id = $result['ConversationUser']['id'];
    		$modelConversation->save(array('check_send' => 1));
    		
    		$comment = $commentModel->find('first', array('conditions' => array(
    				'Comment.type' => 'conversation',
    				'Comment.target_id' => $result['ConversationUser']['conversation_id']
    		),
    				'order' => array('Comment.id desc')
    		));
    		if ($comment && $comment['Comment']['user_id'] != $result['ConversationUser']['user_id'])
    		{
    			$to = MooCore::getInstance()->getItemByType("User", $result['ConversationUser']['user_id']);
    			if($to['User']['send_email_when_send_message'] == 1) {
    				
    				$sender_user = MooCore::getInstance()->getItemByType("User", $comment['Comment']['user_id']);
    				$core = Configure::read('core');
    				$params = array(
    						'sender_link' => $http.'://'.$_SERVER['SERVER_NAME'].$request->base.$sender_user['User']['moo_url'],
    						'sender_title' => $sender_user['User']['name'],
    						'time' => $comment['Comment']['created'],
    						'message_link' => $http.'://'.$_SERVER['SERVER_NAME'].$request->base.'/conversations/view/'.$result['ConversationUser']['conversation_id'],
    						'site_name' => $core['site_name'],
    				);
    				$mailComponent->send($to['User']['email'],'private_message',$params);
    			}
    			if ($userModel->checkSettingNotification($result['ConversationUser']['user_id'],'notify_message_user'))
    			{
    				$notificationModel->record( array( 'recipients' => $result['ConversationUser']['user_id'],
    						'sender_id' => $comment['Comment']['user_id'],
    						'action' => 'message_send',
    						'url' => '/conversations/view/'.$result['ConversationUser']['conversation_id']
    				) );
    			}
    		}
    		elseif (!$comment)
    		{
    			if ($result['Conversation'])
    			{
    				$to = MooCore::getInstance()->getItemByType("User", $result['ConversationUser']['user_id']);
    				if($to['User']['send_email_when_send_message'] == 1) {
    					
    					$sender_user = MooCore::getInstance()->getItemByType("User", $result['Conversation']['user_id']);
    					$core = Configure::read('core');
    					$params = array(
    							'sender_link' => $http.'://'.$_SERVER['SERVER_NAME'].$request->base.$sender_user['User']['moo_url'],
    							'sender_title' => $sender_user['User']['name'],
    							'time' => $result['Conversation']['created'],
    							'message_link' => $http.'://'.$_SERVER['SERVER_NAME'].$request->base.'/conversations/view/'.$result['ConversationUser']['conversation_id'],
    							'site_name' => $core['site_name'],
    					);
    					$mailComponent->send($to['User']['email'],'private_message',$params);
    				}
    				if ($userModel->checkSettingNotification($result['ConversationUser']['user_id'],'notify_message_user'))
    				{
    					$notificationModel->record( array( 'recipients' => $result['ConversationUser']['user_id'],
    							'sender_id' => $result['Conversation']['user_id'],
    							'action' => 'message_send',
    							'url' => '/conversations/view/'.$result['ConversationUser']['conversation_id']
    					) );
    				}
    			}
    		}
    		
    	}
    }
}