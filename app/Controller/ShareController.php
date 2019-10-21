<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ShareController extends AppController {

    public function beforeFilter() {
        parent::beforeFilter();

        $cuid = MooCore::getInstance()->getViewer(true);

        // require login user
        if (empty($cuid)) {
            echo json_encode(array('nonLogin' => '1'));
            exit;
        }
    }

    public function index() {
        
    }
    // Redirect page on App
    public function share_success() {
        $this->Session->setFlash( __('Shared Successfully') , 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
    }

    // display share form for select type share
    public function ajax_share($param = null) {
        $this->layout = 'default_simple';

        $object_id = isset($this->request->named['id']) ? $this->request->named['id'] : null;
        $type = isset($this->request->named['type']) ? $this->request->named['type'] : null;
        $activity = array();

        $plugin = '';
        $object = null;
        $social_link_share = FULL_BASE_URL . $this->request->webroot;
        if (!empty($param)) {
            list($plugin, $name) = mooPluginSplit($param);
            if (!empty($object_id)) {
                $object = MooCore::getInstance()->getItemByType($param, $object_id);
            }
            if (!empty($plugin)){
                $social_link_share = FULL_BASE_URL . $object[key($object)]['moo_href'];
            }
        }

        if (!empty($object_id) && empty($param)) {
            $this->loadModel('Activity');
            $activity = $this->Activity->findById($object_id);
        }

        $this->set(compact('activity', 'cuid', 'param', 'plugin', 'type', 'object', 'object_id', 'social_link_share'));
    }

    public function do_share() {
        $this->autoRender = false;
        
        $messageText = (!isset($this->request->data['message']) && isset($this->request->data['messageText']) )? $this->request->data['messageText'] :$this->request->data['message'];
        $share_type = isset($this->request->data['share_type']) ? $this->request->data['share_type'] : '';
        $action = isset($this->request->data['action']) ? $this->request->data['action'] : '';
        $param = isset($this->request->data['param']) ? $this->request->data['param'] : '';
        $object_id = isset($this->request->data['object_id']) ? $this->request->data['object_id'] : '';
        $userTagging = isset($this->request->data['userTagging']) ? $this->request->data['userTagging'] : '';
        $friendSuggestion = isset($this->request->data['friendSuggestion']) ? $this->request->data['friendSuggestion'] : '';

        $tagsUid = array();
        if (!empty($friendSuggestion)) {
            $tagsUid = explode(',', $friendSuggestion);
        }

        $groupSuggestion = isset($this->request->data['groupSuggestion']) ? $this->request->data['groupSuggestion'] : '';

        $groupIds = array();
        if (!empty($groupSuggestion)) {
            $groupIds = explode(',', $groupSuggestion);
        }

        $email = isset($this->request->data['email']) ? $this->request->data['email'] : '';

        $emailList = array();
        if (!empty($email)) {
            $emailList = explode(',', $email);
        }

        $uid = $this->Auth->user('id');

        $sender = MooCore::getInstance()->getViewer();
        $userModel = MooCore::getInstance()->getModel('User');

        // $share_type : me, friend, group, msg, email

        $this->loadModel('Activity');
        $result = array(
            'success' => false,
            'msg' => __('Error while sharing feed. Please try again later.')
        );

        // check activity_id, make sure it is number
        if (!empty($object_id) && is_numeric($object_id)) {

            $data = array();
            $data['content'] = $messageText;
            $data['message'] = $messageText;
            $data['messageText'] = $messageText;
            $data['user_id'] = $uid;
            $data['share'] = true;
            
            if (strstr($action, "_share")){
                $data['action'] = $action;
            }
            else{
                $data['action'] = $action . "_share";
            }
            
            
            $owner_id = null;
            
            if (empty($param)) { // share activity feed
                // find activity by activity_id
                $activity = $this->Activity->findById($object_id);
                $data['parent_id'] = $activity['Activity']['id'];
                $data['item_type'] = $activity['Activity']['item_type'];
                $data['plugin'] = $activity['Activity']['plugin'];
                $data['items'] = $activity['Activity']['items'];
                $data['privacy'] = $activity['Activity']['privacy'];
                $data['params'] = $activity['Activity']['params'];
                $data['type'] = 'User';
                $owner_id = $activity['Activity']['user_id'];
                $shared_link = Router::url(array(
                            'plugin' => false,
                            'controller' => 'users',
                            'action' => 'view',
                            $activity['Activity']['user_id'],
                            'activity_id' => $activity['Activity']['id']
                                ), true);
            } else { // share item detail
                list($plugin, $name) = mooPluginSplit($param);
                $object = MooCore::getInstance()->getItemByType($param, $object_id);
                
                $data['parent_id'] = $object[key($object)]['id'];
                if (isset($object[key($object)]['moo_privacy'])){
                    $data['privacy'] = $object[key($object)]['moo_privacy'];
                }
                $data['type'] = 'User';
                $data['item_type'] = $param;
                $data['plugin'] = $plugin;
                $shared_link = FULL_BASE_URL . $object[key($object)]['moo_href'];
                $owner_id = $object[key($object)]['user_id'];
            }
            
            $share_activity = null;

            if (!empty($data)) {
                // do share
                switch ($share_type) {
                    case '#me': // share my wall
                        $share_activity = $this->_shareToMyWall($data);
                        $activity_id = isset($share_activity['Activity']['id']) ? $share_activity['Activity']['id'] : 0;
                        //notification for user mention
                        $url = '/users/view/' . $uid . '/activity_id:' . $activity_id;
                        $this->_sendNotificationToMentionUser($messageText,$url,'mention_user');
                        
                        // tagging
                        if (!empty($userTagging)){
                            $this->loadModel('UserTagging');
                            $this->UserTagging->save(array('item_id' => $activity_id,
                                'item_table' => 'activities',
                                'users_taggings' => $userTagging,
                                'created' => date("Y-m-d H:i:s"),
                            ));
                        }

                        //activitylog event
                        $cakeEvent = new CakeEvent('Controller.Share.afterDoShare', $this, array('activity_id' => $activity_id, 'data' => $data));
                        $this->getEventManager()->dispatch($cakeEvent);
                        
                        break;
                    case '#email': // share via email
		                // check captcha
                                    if (!$this->request->is('api') ) {
				        $checkRecaptcha = MooCore::getInstance()->isRecaptchaEnabled();
				        $recaptcha_privatekey = Configure::read('core.recaptcha_privatekey');
				        $is_mobile = $this->viewVars['isMobile'];
                                        if ( $checkRecaptcha && !$is_mobile)
						{
                                                    App::import('Vendor', 'recaptchalib');
                                                    $reCaptcha = new ReCaptcha($recaptcha_privatekey);
                                                    $resp = $reCaptcha->verifyResponse(
                                                        $_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"]
                                                    );
								
                                                    if ($resp != null && !$resp->success) {
                                                        $result['msg'] = __('Invalid security code');
                                                        echo json_encode($result);
                                                        return;
                                                    }
                                        }
                                    }
                        $i = 0;
                        foreach ($emailList as $email) {
                            if (Validation::email(trim($email))) {
                            	$i++;
                                $this->_shareViaEmail(array(
                                    'email' => $email,
                                    'shared_user' => $email,
                                    'user_shared' => $sender['User']['name'],
                                    'shared_link' => $shared_link,
                                    'shared_content' => $messageText,
                                ));
                                
                                if ($i >= 10)
                                	break;
                            }
                        }
                        
                        break;
                    case '#friend': // share to friend wall
                        $activity_id = null;
                        foreach ($tagsUid as $user_id) {
                            $data['target_id'] = $user_id;
                            $share_activity = $this->_shareToFriendWall($data);
                            $activity_id = isset($share_activity['Activity']['id']) ? $share_activity['Activity']['id'] : 0;
                            // tagging
                            if (!empty($userTagging)){
                                $this->loadModel('UserTagging');
                                $this->UserTagging->save(array('item_id' => $activity_id,
                                    'item_table' => 'activities',
                                    'users_taggings' => $userTagging,
                                    'created' => date("Y-m-d H:i:s"),
                                ));
                            }
                            //activitylog event
                            $cakeEvent = new CakeEvent('Controller.Share.afterDoShare', $this, array('activity_id' => $activity_id, 'data' => $data));
                            $this->getEventManager()->dispatch($cakeEvent);
                        }
                        
                        //notification for user mention
                        $url = '/users/view/' . $uid . '/activity_id:' . $activity_id;
                        $this->_sendNotificationToMentionUser($messageText,$url,'mention_user');
                        break;
                    case '#group': // share to group wall
                        $activity_id = null;
                        foreach ($groupIds as $group_id) {
                            $data['target_id'] = $group_id;
                            $data['type'] = 'Group_Group';
                            
                            // disable re-share for item shared in restrict and private group
                            $this->loadModel('Group.Group');
                            $group = $this->Group->findById($group_id);
                            if (!empty($group)){
                                if ($group['Group']['type'] == PRIVACY_RESTRICTED || $group['Group']['type'] == PRIVACY_PRIVATE){
                                    $data['share'] = false;
                                }
                            }
                            
                            $share_activity = $this->_shareToGroupWall($data);
                            $activity_id = isset($share_activity['Activity']['id']) ? $share_activity['Activity']['id'] : 0;
                            
                            // tagging
                            if (!empty($userTagging)){
                                $this->loadModel('UserTagging');
                                $this->UserTagging->save(array('item_id' => $activity_id,
                                    'item_table' => 'activities',
                                    'users_taggings' => $userTagging,
                                    'created' => date("Y-m-d H:i:s"),
                                ));
                            }
                            //activitylog event
                            $cakeEvent = new CakeEvent('Controller.Share.afterDoShare', $this, array('activity_id' => $activity_id, 'data' => $data));
                            $this->getEventManager()->dispatch($cakeEvent);
                        }
                        //notification for user mention
                        $url = '/users/view/' . $uid . '/activity_id:' . $activity_id;
                        $this->_sendNotificationToMentionUser($messageText,$url,'mention_user');
                        
                        break;
                    case '#msg': // share via private message
                        $message = "Hi , \r\n " . $sender['User']['name'] . " " . __("shared you a link") . " " . $shared_link . " \r\n " . $messageText;
                        $subject = __("%s shared you a link", $sender['User']['name']);
                        $this->_shareViaMsg(array(
                            'subject' => $subject,
                            'message' => $message,
                            'friends' => $friendSuggestion
                        ));

                    default :
                        break;
                }
                
                // notification
                $this->loadModel('Notification');
                if ($owner_id != $uid && !empty($share_activity)){ // not notify owner item if owner shared
                    
                	$sharedLink = '/users/view/'.$share_activity['Activity']['user_id'].'/activity_id:'.$share_activity['Activity']['id'];                     

                    if ($userModel->checkSettingNotification($owner_id,'share_item')) {
                        $this->Notification->record(array('recipients' => $owner_id,
                            'sender_id' => $uid,
                            'action' => 'shared_your_post',
                            'url' => $sharedLink
                        ));
                    }
                }
                
                
                // event
                $cakeEvent = new CakeEvent('Controller.Share.afterShare', $this, array('data' => $data));
                $this->getEventManager()->dispatch($cakeEvent);

                $result['success'] = true;
                $result['msg'] = __('Shared Successfully');
            }
        }

        echo json_encode($result);
    }

    private function _shareToMyWall($options = array()) {
        $this->loadModel('Activity');
        $options['created'] = null;
        $options['modified'] = null;
        unset($options['id']);
        $this->Activity->clear();
        $this->Activity->create();
        $this->Activity->set($options);
        $this->Activity->save();
        
        return $this->Activity->read();
    }

    private function _shareToFriendWall($options = array()) {
        $this->loadModel('Activity');
        $options['created'] = null;
        $options['modified'] = null;
        unset($options['id']);
        $this->Activity->clear();
        $this->Activity->create();
        $this->Activity->set($options);
        $this->Activity->save();

        // notification
        $this->loadModel('Notification');
        $this->Notification->record(array('recipients' => $options['target_id'],
            'sender_id' => $options['user_id'],
            'action' => 'shared_to_friend_wall',
            'url' => '/users/view/' . $options['user_id'] . '/activity_id:' . $this->Activity->id
        ));
        
        return $this->Activity->read();
    }

    private function _shareToGroupWall($options = array()) {
        $this->loadModel('Activity');
        $options['created'] = null;
        $options['modified'] = null;
        unset($options['id']);
        $this->Activity->clear();
        $this->Activity->create();
        $this->Activity->set($options);
        $this->Activity->save();
        
        return $this->Activity->read();
    }

    private function _shareViaEmail($options = array()) {
        $ssl_mode = Configure::read('core.ssl_mode');
        $http = (!empty($ssl_mode)) ? 'https' : 'http';

        $this->MooMail->send(trim($options['email']), 'shared_item', array(
            'email' => trim($options['email']),
            'shared_user' => $options['shared_user'],
            'user_shared' => $options['user_shared'],
            'shared_content' => $options['shared_content'],
            'shared_link' => $options['shared_link']
                )
        );
    }

    private function _shareViaMsg($options = array()) {

        $uid = $this->Auth->user('id');
        $data = array();
        $data['user_id'] = $uid;
        $data['lastposter_id'] = $uid;
        $data['subject'] = $options['subject'];
        $data['message'] = $options['message'];
        $friends = $options['friends'];
        $this->loadModel('Conversation');
        $this->Conversation->set($data);
        $this->_validateData($this->Conversation);

        if (!empty($friends)) {
            $recipients = explode(',', $friends);

            if ($this->Conversation->save()) { // successfully saved	
                $participants = array();

                foreach ($recipients as $participant) {
                    $participants[] = array('conversation_id' => $this->Conversation->id, 'user_id' => $participant);
                }

                // add sender to convo users array
                $participants[] = array('conversation_id' => $this->Conversation->id, 'user_id' => $uid, 'unread' => 0);
                
                $this->loadModel('ConversationUser');
                $this->ConversationUser->saveAll($participants);

                /*$this->loadModel('Notification');
                $this->Notification->record(array('recipients' => $recipients,
                    'sender_id' => $uid,
                    'action' => 'message_send',
                    'url' => '/conversations/view/' . $this->Conversation->id
                ));*/
            }
        }
    }

}
