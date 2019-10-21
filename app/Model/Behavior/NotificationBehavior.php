<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('ModelBehavior', 'Model');
App::uses('CakeSession', 'Model/Datasource');

class NotificationBehavior extends ModelBehavior {

    public $runtime = array();

    protected $_joinTable;

    protected $_runtimeModel;

    public function setup(Model $Model, $settings = array()) {
        $this->settings[$Model->alias] = (array) $settings;
    }

    public function getRuntimeModel() {
        if (!$this->_runtimeModel)
            $this->_runtimeModel = ClassRegistry::init('Notification');
        return $this->_runtimeModel;
    }

    public function afterSave(Model $Model, $created, $options = array()) {
        $field_created = "users_taggings";
        $status_field = 'item_id';
        $cuid = MooCore::getInstance()->getViewer(true);
        $RuntimeModel = $this->getRuntimeModel();
        $userTaggingModel = ClassRegistry::init('UserTagging');
        $stopNotificationModel = MooCore::getInstance()->getModel('NotificationStop');
        $userModel = MooCore::getInstance()->getModel("User");
        $userBlockModel = MooCore::getInstance()->getModel("UserBlock");
        $activityModel = ClassRegistry::init('Activity');
        $groupModel = ClassRegistry::init('Group.Group');
        if (isset($Model->data['Like']) && !empty($Model->data['Like']['thumb_up']) ){ // like item
            // send notification to tagged user
            $activity = null;
            $type = isset($Model->data['Like']['type']) ? $Model->data['Like']['type'] : '';
            $target_id =  isset($Model->data['Like']['target_id']) ? $Model->data['Like']['target_id'] : '';
            $parent_id = null;
            
            if (empty($type) || empty($target_id)){
                return true;
            }
            
            $itemTagged = $userTaggingModel->getTaggedItem($target_id, Inflector::pluralize($type));

            $notified_url = '';
            $notified_action = '';
            switch ($type){
                case 'activity':
                    $notified_url = "/users/view/$cuid/activity_id:$target_id";
                    $notified_action = 'like_tagged_status';
                    $activityModel = ClassRegistry::init('Activity');
                    $activity = $activityModel->findById($Model->data['Like']['target_id']);
                    $parent_id[] = $activity['User']['id'];
                    switch (strtolower($activity['Activity']['type'])) {
                    	case 'user':
                    		$parent_id[] = $activity['Activity']['target_id'];
                    		break;
                    	/*default:
                    		$subject = MooCore::getInstance()->getItemByType($activity['Activity']['type'], $activity['Activity']['target_id']);
                    		$parent_id = $subject['User']['id'];
                    		break;*/
                    }
                    break;
                default :
                	$target = MooCore::getInstance()->getItemByType($type,$target_id);
                	$parent_id[] = $target['User']['id'];
                	if ($target)
                	{
                		switch (strtolower(key($target))){
                			case 'activitycomment':
			                	switch (strtolower($target['Activity']['type'])) {
			                    	case 'user':
			                    		$parent_id[] = $target['Activity']['target_id'] ? $target['Activity']['target_id'] : $target['Activity']['user_id'];
			                    		break;
			                    	/*default:
			                    		$subject = MooCore::getInstance()->getItemByType($target['Activity']['type'], $target['Activity']['target_id']);
			                    		$parent_id = $subject['User']['id'];
			                    		break;*/
			                    }
                				break;
                			case 'comment':                				
                				$target_comment = MooCore::getInstance()->getItemByType($target['Comment']['type'],$target['Comment']['target_id']);
                				$parent_id[] = $target_comment['User']['id'];
                				if (strtolower($target['Comment']['type']) == 'photo_photo')
                				{
	                				$activity = $activityModel->find('first',array(
	                					'conditions' => array(
	                						'Activity.action' => 'wall_post',
	                						'Activity.item_type' => 'Photo_Album',
	                						'Activity.items' => $target['Comment']['target_id']
	                					)
	                				));
	                				if ($activity)
	                				{
		                				switch (strtolower($activity['Activity']['type'])) {
					                    	case 'user':
					                    		$parent_id[] = $activity['Activity']['target_id'];
					                    		break;
					                    	/*default:
					                    		$subject = MooCore::getInstance()->getItemByType($activity['Activity']['type'], $activity['Activity']['target_id']);
					                    		$parent_id[] = $subject['User']['id'];
					                    		break;*/
					                    }
	                				}
	                				
	                				/*$photoModel = MooCore::getInstance()->getModel('Photo.Photo');
	                				$photo = $photoModel->findById($target['Comment']['target_id']);
									if ($photo['Photo']['album_type'])
						        	{
						        		$target_photo = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);        				
						        		if ($target_photo)
						        		{
						        			$parent_id[] = $target_photo['User']['id'];
						        		}
						        	}
                					if (strtolower($photo['Photo']['type']) != 'photo_album' && $photo['Photo']['target_id'])
						        	{
						        		$target = MooCore::getInstance()->getItemByType($photo['Photo']['type'],$photo['Photo']['target_id']);
						        		if ($target)
						        		{
						        			$parent_id[] = $target['User']['id'];
						        		}
						        	}*/
                				}
                				
                				break;
                			/*default:
                				$parent_id[] = $target['User']['id'];
                				break;*/
                		}
                	}
                    break;
            }
            $userTaggings = empty($itemTagged) ? false : $itemTagged['UserTagging']['users_taggings'];

            $listUserTaggings = explode(',', $userTaggings);
            $listUserTaggings[] = $cuid;
            $user_blocks = $userBlockModel->getBlockedUsers($cuid);
            if ($parent_id)
            {
            	if (is_array($parent_id))
            	{
            		foreach ($parent_id as $id)
            			$user_blocks = array_merge($user_blocks,$userBlockModel->getBlockedUsers($id));
            	}
            	else
            	{
            		$user_blocks = array_merge($user_blocks,$userBlockModel->getBlockedUsers($parent_id));
            	}
            }

            $plugin_name = '';
            $notification_params = '';
            $controller = new Controller();
            $controller->getEventManager()->dispatch(new CakeEvent('Notification.Controller.ModelBehavior.NotificationBehavior.BeforeSendToTagged', $this,array(
                'action' => &$notified_action,
                'plugin_name' => &$plugin_name,
                'notification_params' => &$notification_params,
                'like' => $Model->data['Like']
            )));

            foreach ($listUserTaggings as $tagged_uid) {
                if ($cuid != $tagged_uid) { // dont send notification to liker
                	
                    if (in_array($tagged_uid,$user_blocks))
                    {
                    	continue;
					}
                	
                    if (!$userModel->checkSettingNotification($tagged_uid,'like_tag_user')) {
                        continue;
                    }

                    // dont send notification to user who setting it stop
                    $notificationStop = $stopNotificationModel->isNotificationStop($target_id, $type, $tagged_uid);

                    if ($notificationStop) {
                        continue;
                    }
                    $RuntimeModel->clear();
                    $RuntimeModel->record(array(
                        'recipients' => $tagged_uid,
                        'sender_id' => $cuid,
                        'action' => $notified_action,
                        'url' => $notified_url,
                        'params' => $notification_params,
                        'plugin' => $plugin_name
                    ));
                }
            }
            //user mention
            $setting_type = 'like_mention_status';
            if($type == 'activity'){
                $action = 'like_mentioned_post';
                $isActivity = true;
                $mentionUrl = $notified_url;
                if(!empty($activity)){
                    preg_match_all(REGEX_MENTION,$activity['Activity']['content'],$matches);
                }
            }else{
                $setting_type = 'like_comment_mention';
                $action = 'like_mentioned_comment';
                if($type == 'comment'){
                    $commentModel = ClassRegistry::init('Comment');
                    $comment = $commentModel->findById($Model->data['Like']['target_id']);
                    if(!empty($comment)){
                        $content = $comment['Comment']['message'];
                        if($comment['Comment']['type'] == 'core_activity_comment'){
                            $object = MooCore::getInstance()->getItemByType($comment['Comment']['type'], $comment['Comment']['target_id']);
                            $mentionUrl = '/users/view/' . $cuid. '/activity_id:' . $object['ActivityComment']['activity_id'].'/comment_id:'.$object['ActivityComment']['id'].'/reply_id:'.$comment['Comment']['id'];
                        }elseif($comment['Comment']['type'] == 'comment'){
                            $object = MooCore::getInstance()->getItemByType($comment['Comment']['type'], $comment['Comment']['target_id']);
                            list($plugin, $model) = mooPluginSplit($object['Comment']['type']);
                            $mentionUrl = "/" . lcfirst(Inflector::pluralize($model)) . "/view/" . $object['Comment']['target_id'].'/comment_id:'.$object['Comment']['id'].'/reply_id:'.$comment['Comment']['id'];
                        }else {
                            list($plugin, $model) = mooPluginSplit($comment['Comment']['type']);
                            $mentionUrl = "/" . lcfirst(Inflector::pluralize($model)) . "/view/" . $comment['Comment']['target_id'];
                        }
                    }
                }elseif($type == 'core_activity_comment'){
                    $activityComment = ClassRegistry::init('ActivityComment');
                    $comment = $activityComment->findById($Model->data['Like']['target_id']);
                    if(!empty($comment)){
                        $content = $comment['ActivityComment']['comment'];
                        $mentionUrl = "/users/view/$cuid/activity_id:".$comment['ActivityComment']['activity_id'];
                    }
                }
                if(!empty($comment) && isset($content)){
                    preg_match_all(REGEX_MENTION,$content,$matches);
                }
            }

            $plugin_name = '';
            $notification_params = '';
            $controller = new Controller();
            $controller->getEventManager()->dispatch(new CakeEvent('Notification.Controller.ModelBehavior.NotificationBehavior.BeforeSendToMention', $this,array(
                'action' => &$action,
                'plugin_name' => &$plugin_name,
                'notification_params' => &$notification_params,
                'like' => $Model->data['Like']
            )));

            if(!empty($matches) && !empty($mentionUrl)){            	
                foreach($matches[0] as $key => $value){
                    if($matches[1][$key] != $cuid){
	                    if (in_array($matches[1][$key],$user_blocks))
	                    {
	                    	continue;
						}
                    	
                        if (!$userModel->checkSettingNotification($matches[1][$key],$setting_type)) {
                            continue;
                        }
                        if(!empty($isActivity)){
                            // dont send notification to user who setting it stop
                            $notificationStop = $stopNotificationModel->isNotificationStop($target_id,$type,$matches[1][$key]);
                            if ($notificationStop){
                                continue;
                            }
                        }

                        $RuntimeModel->clear();
                        $RuntimeModel->record(array('recipients' => $matches[1][$key],
                                'sender_id' => $cuid,
                                'action' => $action,
                                'url' => $mentionUrl,
                                'params' => $notification_params,
                                'plugin' => $plugin_name
                            ));
                    }
                }
            }
        }else if (isset($Model->data['ActivityComment'])){ // comment on activity
            // send notification to tagged user
            $activity_id = isset($Model->data['ActivityComment']['activity_id']) ? $Model->data['ActivityComment']['activity_id'] : '';
            
            if (empty($activity_id)){
                return true;
            }
            
            $activityModel = ClassRegistry::init('Activity');
            $activity = $activityModel->findById($Model->data['ActivityComment']['activity_id']);
            $user_blocks = $userBlockModel->getBlockedUsers($cuid);
            $parent_id = array($activity['User']['id']);
            switch (strtolower($activity['Activity']['type'])) {
            	case 'user':
            		$parent_id[] = $activity['Activity']['target_id'];
            		break;
            	/*default:
            		$subject = MooCore::getInstance()->getItemByType($activity['Activity']['type'], $activity['Activity']['target_id']);
            		$parent_id = $subject['User']['id'];
            		break;*/
            }            
            foreach ($parent_id as $tmp)
            	$user_blocks = array_merge($user_blocks,$userBlockModel->getBlockedUsers($tmp));
            
            $activity_type = 'activity';
            $itemTagged = $userTaggingModel->getTaggedItem($activity_id, Inflector::pluralize($activity_type));
            
            $notified_url = "/users/view/$cuid/activity_id:$activity_id";
            $notified_action = 'comment_tagged_status';

            $userTaggings = empty($itemTagged) ? false : $itemTagged['UserTagging']['users_taggings'];

            $listUserTaggings = explode(',', $userTaggings);
            $listUserTaggings[] = $cuid;
            
            foreach ($listUserTaggings as $tagged_uid){
                if ($cuid != $tagged_uid){ // dont send notification to liker
                    if (in_array($tagged_uid,$user_blocks))
                    {
                    	continue;
					}
                            	
                    if (!$userModel->checkSettingNotification($tagged_uid,'comment_tag_user')) {
                        continue;
                    }

                    // dont send notification to user who setting it stop
                	$notificationStop = $stopNotificationModel->isNotificationStop($activity_id,$activity_type,$tagged_uid);
                    if ($notificationStop){
                        continue;
                    }
                    $RuntimeModel->clear();
                    $RuntimeModel->record( array(
                        'recipients'  => $tagged_uid,
                        'sender_id'   => $cuid,
                        'action'      => $notified_action,
                        'url'         => $notified_url,
                    ) );
                }
            }

            //user mention            
            preg_match_all(REGEX_MENTION,$activity['Activity']['content'],$matches);
            if(!empty($matches)){
            	foreach($matches[0] as $key => $value){
            		if($matches[1][$key] != $cuid){
            
            			if (in_array($matches[1][$key],$user_blocks))
            			{
            				continue;
            			}
            			if (!$userModel->checkSettingNotification($matches[1][$key],'comment_mention_status')) {
            				continue;
            			}
            
            			// dont send notification to user who setting it stop
            			$notificationStop = $stopNotificationModel->isNotificationStop($activity_id,$activity_type,$matches[1][$key]);
            			if ($notificationStop){
            				continue;
            			}
            
            			$RuntimeModel->clear();
            			$RuntimeModel->record(array('recipients' => $matches[1][$key],
            					'sender_id' => $cuid,
            					'action' => 'comment_mentioned_post',
            					'url' => $notified_url
            			));
            		}
            	}
            }

        }else if (isset($Model->data['UserTagging'])) { // tagged user on a status
            $userIds = $this->filterUsers($Model->data[$Model->alias][$field_created]);
            $activity_id = $Model->data[$Model->alias][$status_field];
            $listUserIds = explode(',', $userIds);
            $action = 'tagged_status';
            $params = '';
            
            // check status group
            $activity = $activityModel->find('first', array(
                'conditions' => array(
                    'Activity.id' => $activity_id
                )
            ));
            $user_blocks = $userBlockModel->getBlockedUsers($cuid);
            /*switch (strtolower($activity['Activity']['type'])) {
            	case 'user':
            		$parent_id = $activity['Activity']['target_id'];
            		break;
            	default:
            		$subject = MooCore::getInstance()->getItemByType($activity['Activity']['type'], $activity['Activity']['target_id']);
            		$parent_id = $subject['User']['id'];
            		break;
            }*/
            $parent_id = $activity['User']['id'];
            if ($parent_id)
            	$user_blocks = array_merge($user_blocks,$userBlockModel->getBlockedUsers($parent_id));
            
            /*if (isset($activity['Activity']['type']) && $activity['Activity']['type'] == 'Group_Group'){
                $action = 'tagged_group_status';
                $group = $groupModel->find('first', array(
                    'conditions' => array(
                        'Group.id' => $activity['Activity']['target_id']
                    )
                ));
                $params = isset($group['Group']['name']) ? $group['Group']['name'] : '';
            }*/
            
            foreach ($listUserIds as $uid){
            	if (in_array($uid,$user_blocks))
            	{
            		continue;
            	}
            	
            	if (!$userModel->checkSettingNotification($uid,'comment_mention_status')) {
            		continue;
            	}
            	
                if (!$userModel->checkSettingNotification($uid,'tag_user')) {
                    continue;
                }
                $RuntimeModel->clear();
                $RuntimeModel->record(array(
                    'recipients' => $uid,
                    'sender_id' => $cuid,
                    'action' => $action,
                    'url' => "/users/view/$cuid/activity_id:$activity_id",
                    'params' => $params
                ));
            }
        }
        
    }

    public function afterDelete(Model $Model) {
        
    }

    public function beforeFind(Model $Model, $query) {
        
    }

    public function afterFind(Model $Model, $results, $primary = false) {
        
    }

    private function filterUsers($ids = null) {
        if (empty($ids))
            return false;
        $ids = explode(",", $ids);
        if (empty($ids))
            return false;
        $in_SQL = '';
        foreach ($ids as $id) {
            $in_SQL.=(is_int((int) $id) ? ((int) $id) . "," : "");
        }
        $in_SQL = trim($in_SQL, ',');
        if (empty($in_SQL))
            return false;
        $db = ConnectionManager::getDataSource('default');
        $prefix = (!empty($db->config['prefix']) ? $db->config['prefix'] : '');

        $sql = "SELECT id FROM " . $prefix . "users WHERE id IN($in_SQL)";
        $users = $db->fetchAll($sql);

        $results = Hash::extract($users, '{n}.' . $prefix . 'users.id');
        return implode(',', $results);
    }

}
