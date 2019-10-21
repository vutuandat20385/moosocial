<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');

class ActivitiesController extends AppController {
			
	/*
	 * Show activities based on $type
	 * @param string $type - possible value: friends, everyone, profile, event, group
	 */
        public function ajax_browse($type = null, $param = null,$isRedirect = true) {
                $uid = $this->Auth->user('id');
                if($isRedirect) {
                    $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
                }
                else {
                    $page = $this->request->query('page') ? $this->request->query('page') : 1;
                }
                $admins = array();

                if (in_array($type, array('home', 'everyone', 'friends'))) {
                    $param = $this->Auth->user('id');
                    if ($type == 'friends' && !$param)
                        $this->_checkPermission();

                    // save to cookie
                    if (in_array($type, array('everyone', 'friends')))
                        $this->Cookie->write('activity_feed', $type);
                }

                switch (strtolower($type)) {
                    case 'home':
                    case 'everyone':
                    case 'friends':
                        break;
                    case 'profile':
                        $admins = array($param);
                        MooCore::getInstance()->setSubject(MooCore::getInstance()->getItemByType('User', $param));
                        break;
                    default:
                        $model = MooCore::getInstance()->getModel($type);
                        list($plugin, $name) = mooPluginSplit($type);
                        $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                        $subject = $model->findById($param);
                        $check_see_activity = $helper->checkSeeActivity($subject,$uid);
                        $is_member = $helper->isMember($subject,$uid );
                        MooCore::getInstance()->setSubject($subject);

                        $this->set('is_member', $is_member);
                        if (!$check_see_activity)
                        {
                            return;
                        }
                        $admins = $helper->getAdminList($subject);

                        break;
                }

                $this->set('admins', $admins);

                $activity_feed = $type;
                if ($type == 'home') {
                    $activity_feed = Configure::read('core.default_feed');

                    // save activity feed that you selected
                    if (!empty($uid) && Configure::read('core.feed_selection') && $this->Cookie->read('activity_feed'))
                        $activity_feed = $this->Cookie->read('activity_feed');

                    $this->set('activity_feed', $activity_feed);
                }

                $activities = $this->Activity->getActivities($activity_feed, $param, $uid, $page);
                $activities_count = $this->Activity->getActivitiesCount($activity_feed, $param, $uid) ;
                $this->set('activities', $activities);

                // Custom for mooApp api feed .
                if($this->request->params['plugin'] == 'api' || $this->request->params['plugin'] == 'Api' ) {
                    $activities = $this->_removeGroupJoinActivities($activities);
                }
                
                
                // MOOSOCIAL-2707
                $check_post_status = true;
                $subject = MooCore::getInstance()->getSubject();
                list($plugin, $name) = mooPluginSplit($type);
                if (!empty($plugin)){
                    $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                    $check_post_status = $helper->checkPostStatus($subject,$uid);
                }
                $this->set('check_post_status', $check_post_status);

                // get activity likes
                if (!empty($uid)) {
                    $this->loadModel('Like');

                    $activity_likes = $this->Like->getActivityLikes($activities, $uid);
                    $this->set('activity_likes', $activity_likes);
                }

                $url = (!empty($param) ) ? $type . '/' . $param : $type;
                if(!isset($activities['page'])){
                    $this->set('more_url', '/activities/ajax_browse/' . h($url) . '/page:' . ( $page + 1 ));
                }else{
                    $this->set('more_url', '/activities/ajax_browse/' . h($url) . '/page:' . ( $activities['page'] + 1 ));
                    unset($activities['page']);
                }
                $this->set('bIsACtivityloadMore', $activities_count - $page * RESULTS_LIMIT);
                if($isRedirect && $this->theme != "mooApp" ) {
                    if ($page == 1 && $type == 'home'){
                        $this->set('homeActivityWidgetParams',$this->Feeds->get());
                        $this->render('/Elements/ajax/home_activity');
                    }
                    else {
                        if ($this->request->is('ajax')){
                            if (Configure::read('core.comment_sort_style') == COMMENT_RECENT){
                                $this->render('/Elements/activities');
                            }else{
                                $this->render('/Elements/activities_chrono');
                            }
                        }
                        else{
                            $this->set('homeActivityWidgetParams',$this->Feeds->get());
                            $this->render('/Elements/activities_m');
                        }
                    }
                }else {
                    $this->set('datas', $activities);
                }
            
        }
        public function ajax_load_photo_album($idAlbum,$idPhoto) {
            if($this->theme == "mooApp"){
                $this->set('albumId', $idAlbum);
                $this->set('photoId', $idPhoto);
                if (isset($this->request->query['uid'])) $this->set('userUid', $this->request->query['uid']);
            }
        }
        public function ajax_load_feed_form($type = null,$id = null) {
            if($this->theme == "mooApp"){
                switch ($type) {
                    case 'event':
                        $this->loadModel('Event.Event');
                        $event= $this->Event->findById($id);
                        $this->_checkExistence($event);
                        MooCore::getInstance()->setSubject($event);
                        break;
                    
                    case 'group':
                        $this->loadModel("Group.Group");
                        $group = $this->Group->findById($id);
                        $this->_checkExistence($group);
                        MooCore::getInstance()->setSubject($group);
                        break;
                    
                    case 'user':
                        $this->loadModel('User');
                        $user = $this->User->findById($id);
                        $this->_checkExistence($user);
                        MooCore::getInstance()->setSubject($user);
                        break;
                }
                $this->set('title_for_layout', __('Activities'));
                $this->set('loadFeedForm',$this->Feeds->get());
            }
        }
        public function ajax_load_photo_feed($idPhoto) {
            if($this->theme == "mooApp"){
                $this->set('photoId', $idPhoto);
            }
        }
        public function view($id) {
            if($this->theme == "mooApp"){
                $targetPhotoId = $this->request->query('targetPhotoId') ? $this->request->query('targetPhotoId') : '';
                if($targetPhotoId) $this->set('targetPhotoId', $targetPhotoId);
                $youtubeId = $this->request->query('youtubeId') ? $this->request->query('youtubeId') : '';
                if($youtubeId) $this->set('youtubeId', $youtubeId);
                $this->set('activityId', $id);
                $this->set('title_for_layout', __('Activity'));
            }
        }

    public function browse($type = null, $param = null) {
            $uid = $this->Auth->user('id');
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            $admins = array();

            if (in_array($type, array('home', 'everyone', 'friends'))) {
                $param = $this->Auth->user('id');
                if ($type == 'friends' && !$param)
                    $this->_checkPermission();

                // save to cookie
                if (in_array($type, array('everyone', 'friends')))
                    $this->Cookie->write('activity_feed', $type);
            }
            $data = array() ;
            switch ($type) {
                case 'Group_Group':
                    $uid = $this->Auth->user('id');

                    $this->loadModel('Group.Group');
                    $this->loadModel('Group.GroupUser');

                    $group = $this->Group->findById($param);
                    $is_member = $this->GroupUser->isMember($uid, $param);
                    
                    MooCore::getInstance()->setSubject($group);
                    
                    if ($group['Group']['type'] == PRIVACY_PRIVATE) {
                        $cuser = $this->_getUser();

                        if (!$cuser['Role']['is_admin'] && !$is_member)
                            return;
                    }

                    $admins = $this->GroupUser->getUsersList($param, GROUP_USER_ADMIN);
                    $data['is_member'] = $is_member ;
                    $this->set('is_member', $is_member);

                    break;

                case 'Event_Event':
                    $this->loadModel('Event.Event');

                    // add event creator to the admins array
                    $event = $this->Event->findById($param);
                    $admins = array($event['Event']['user_id']);
                    
                    MooCore::getInstance()->setSubject($event);
                    
                    break;

                case 'profile':
                    $admins = array($param);
                    MooCore::getInstance()->setSubject(MooCore::getInstance()->getItemByType('User', $param));
                    break;
            }
            
            // MOOSOCIAL-2707
            $check_post_status = true;
            $subject = MooCore::getInstance()->getSubject();
            list($plugin, $name) = mooPluginSplit($type);
            if (!empty($plugin)){
                $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                $check_post_status = $helper->checkPostStatus($subject,$uid);
            }
            $this->set('check_post_status', $check_post_status);
            
            $this->set('admins', $admins);
            $data['admins'] = $admins ;
            $activity_feed = $type;
            if ($type == 'home') {
                $activity_feed = Configure::read('core.default_feed');

                // save activity feed that you selected
                if (!empty($uid) && Configure::read('core.feed_selection') && $this->Cookie->read('activity_feed'))
                    $activity_feed = $this->Cookie->read('activity_feed');

                $this->set('activity_feed', $activity_feed);
                $data['activity_feed'] = $activity_feed ;
            }

            $activities = $this->Activity->getActivities($activity_feed, $param, $uid, $page);
            $activities_count = $this->Activity->getActivitiesCount($activity_feed, $param, $uid) ;  
            
            $url = (!empty($param) ) ? $type . '/' . $param : $type;
            if(!isset($activities['page'])){
                $this->set('more_url', '/activities/ajax_browse/' . h($url) . '/page:' . ( $page + 1 ));
            }else{
                $this->set('more_url', '/activities/ajax_browse/' . h($url) . '/page:' . ( $activities['page'] + 1 ));
                unset($activities['page']);
            }
            //filter group_join activity
            if($activity_feed == 'Group_Group')
            {
                foreach($activities as $index => &$activity)
                {
                    if($activity['Activity']['action'] == 'group_join')
                    {
                        $aItem = explode(',',$activity['Activity']['items']);
                        if(!in_array($param,$aItem))
                        {
                            unset($activities[$index]);
                            //$activities_count--;
                        }
                        else
                        {
                            $activity['Activity']['items'] = $param;
                            $activity['Activity']['target_id'] = $param;
                        }
                    }
                }
            }

            $this->set('activities', $activities);
            $data['activities'] = $activities ;

            // get activity likes
            if (!empty($uid)) {
                $this->loadModel('Like');

                $activity_likes = $this->Like->getActivityLikes($activities, $uid);
                $this->set('activity_likes', $activity_likes);
                $data['activity_likes'] = $activity_likes ;
            }

            
            $this->set('bIsACtivityloadMore', $activities_count - $page * RESULTS_LIMIT);
            
            if (Configure::read('core.comment_sort_style') == COMMENT_RECENT){
                $this->render('/Elements/activities');
            }else{
                $this->render('/Elements/activities_chrono');
            }
            
    }

    public function ajax_share() {
        $this->_checkPermission(array('confirm' => true));
        $uid = $this->Auth->user('id');

        // MOOSOCIAL-2053
        if (!isset($this->request->data['type']) || !isset($this->request->data['target_id'])
                || !isset($this->request->data['action']) || !isset($this->request->data['wall_photo'])){
            exit;
        }
        
        $this->request->data['user_id'] = $uid;
        $this->request->data['content'] = (!isset($this->request->data['message']) && isset($this->request->data['messageText']) )? $this->request->data['messageText'] :$this->request->data['message'];//$this->request->data['messageText'];
        $this->request->data['privacy'] = (!empty($this->request->data['privacy']) ) ? $this->request->data['privacy'] : PRIVACY_ME;
        
        $this->request->data['message'] = $this->request->data['messageText'];
        
        if ($this->request->data['type'] == 'User' && $this->request->data['target_id'])
        {
        	$user_id = $this->request->data['target_id'];
        	$user = $this->User->findById($user_id);
        	$this->request->data['privacy'] = $user['User']['privacy'];
        }

        if (isset($this->request->data['wall_photo']) && $this->request->data['wall_photo']) {
            $this->loadModel('Photo.Album');
            $this->loadModel('Photo.Photo');
            $photoList = explode(',', $this->request->data['wall_photo']);

            $album = $this->Album->getUserAlbumByType($uid, 'newsfeed');
            $title = 'Newsfeed Photos';
            $album_type = '';
            $album_type_id = 0;

            if (empty($album)) {
                $this->Album->save(array('user_id' => $uid, 'type' => 'newsfeed', 'title' => $title), false);
                $album_id = $this->Album->id;
            } else
                $album_id = $album['Album']['id'];
            
            // MOOSOCIAL-2815
            if ($this->request->data['type'] == 'Event_Event' || $this->request->data['type'] == 'Group_Group'){
                $album_type = $this->request->data['type'];
                $album_type_id = $this->request->data['target_id'];
                $album_id = 0;
            }

            $album = $this->Album->read();

            $data = array();
            $data['type'] = 'Photo_Album';
            $data['target_id'] = $album_id;
            $data['user_id'] = $uid;
            $data['privacy'] = $this->request->data['privacy'];
            $data['album_type'] = $album_type;
            $data['album_type_id'] = $album_type_id;
            $photoId = array();
            $first = true;
            foreach ($photoList as $photoItem) {
                if ($photoItem) {
                    $data['thumbnail'] = $photoItem;
                    $this->Photo->create();
                    $this->Photo->set($data);
                    $this->Photo->save();
                    array_push($photoId, $this->Photo->id);
                    if ($first) {
                        $first = false;
                        if (!$album['Album']['cover'] && !empty($album_id)) {
                            $photo = $this->Photo->read();
                            $this->Album->clear();
                            $this->Album->id = $album_id;
                            $this->Album->save(array('cover' => $photo['Photo']['thumbnail']));
                        }
                    }
                }
            }
            $this->request->data['items'] = implode(',', $photoId);
            $this->request->data['item_type'] = 'Photo_Album';
            $this->request->data['item_id'] = $album_id;
        }
        else
        {
             if(isset($this->request->data['userShareLink']) && !empty($this->request->data['userShareLink'])){
                $this->request->data['source_url'] = $this->request->data['userShareLink'];
            }else if(isset($this->request->data['userShareVideo']) && !empty($this->request->data['userShareVideo'])){
                $this->request->data['source_url'] = $this->request->data['userShareVideo'];
            }
            // Make sure activity model is loaded for api
            $this->loadModel('Activity');
        	$this->Activity->parseLink($this->request->data);

                if(!empty($this->request->data['params'])){
                    $params_arr = unserialize($this->request->data['params']);
                    if(isset($params_arr['image']) && !isset($this->request->data['share_image'])){
                        unset($params_arr['image']);
                    }
                    if(isset($params_arr['title']) && !isset($this->request->data['share_text'])){
                        unset($params_arr['title']);
                        unset($params_arr['description']);
                        unset($params_arr['link']);
                        unset($params_arr['url']);
                    }
                    $this->request->data['params'] = serialize($params_arr);
                }
        }
        
        // enable shared feature for status
        // do not add share link for feed of Event and Group
        if ($this->request->data['type'] != 'Group_Group' && $this->request->data['type'] != 'Event_Event'){
            $this->request->data['share'] = true;
        }
        
        // enable for public Group
        if ($this->request->data['type'] == 'Group_Group'){
            $groupModel = MooCore::getInstance()->getModel('Group.Group');
            $group = $groupModel->findById($this->request->data['target_id']);
            if (!empty($group) && $group['Group']['type'] == PRIVACY_PUBLIC){
                $this->request->data['share'] = true;
            }
        }
       
        // enable for public Event
        if ($this->request->data['type'] == 'Event_Event'){
            $eventModel = MooCore::getInstance()->getModel('Event.Event');
            $event = $eventModel->findById($this->request->data['target_id']);
            if (!empty($event) && $event['Event']['type'] == PRIVACY_PUBLIC){
                $this->request->data['share'] = true;
            }
        }
        
        if (!empty($this->request->data['target_id']))
        {
        	$subject = MooCore::getInstance()->getItemByType($this->request->data['type'], $this->request->data['target_id']);
        	MooCore::getInstance()->setSubject($subject);
        }
        
        // MOOSOCIAL-2241
        if (isset($this->request->data['video_destination']) && $this->request->data['video_destination']) {
            $this->request->data['action'] = "wall_post_video";
        }
         
        if ($this->Activity->save($this->request->data)) {
            $activity = $this->Activity->read();
            
            $admins = array(); // activity poster
            $subject_type = !empty($this->request->data['subject_type']) ? $this->request->data['subject_type'] : '';
            
            switch (strtolower($activity['Activity']['type']) )
            {
            	case 'user':
            		if (!$activity['Activity']['target_id'])
            		{
            			$admins[] = $activity['Activity']['user_id']; 
            		}
            		break;
            		
            	default:
            		$type = $activity['Activity']['type'];
            		$model = MooCore::getInstance()->getModel($type);
            		list($plugin, $name) = mooPluginSplit($type);
            		$helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
            		$subject = $model->findById( $activity['Activity']['target_id']);
            		if(method_exists($helper,'getAdminList'))
            			$admins = $helper->getAdminList($subject);            			
            			break;
            }
            
            $this->set('admins',$admins);
            $this->set('subject_type',$subject_type);
            
            //notification for user mention
            $url = '/users/view/' . $uid . '/activity_id:' . $activity['Activity']['id'];
            $parent_id = '';
        	switch (strtolower($this->request->data['type'])) {
                case 'user':
                    $parent_id = $this->request->data['target_id'];
                    break;
            }
            $this->_sendNotificationToMentionUser($activity['Activity']['content'],$url,'mention_user', array(),$parent_id);

            if (!empty($this->request->data['wall_photo_id'])) {
                $this->loadModel('Photo.Photo');
                $activity['Content'] = $this->Photo->findById($this->request->data['wall_photo_id']);
            } else {
                $event = new CakeEvent('ActivitesController.afterShare', $this, array('activity' => $activity));
                $this->getEventManager()->dispatch($event);
                $activity = $this->Activity->read();
            }

            $this->set('activity', $activity);

            switch (strtolower($this->request->data['type'])) {
                case 'user':
                    if (!empty($this->request->data['target_id']) && $this->request->data['target_id'] != $uid) { // post on other user's profile
                        $this->loadModel("User");
                        if ($this->User->checkSettingNotification($this->request->data['target_id'],'post_profile')) {
                            $this->loadModel('Notification');
                            $this->Notification->record(array('recipients' => $this->request->data['target_id'],
                                'sender_id' => $uid,
                                'action' => 'profile_comment',
                                'url' => '/users/view/' . $this->request->data['target_id'] . '/activity_id:' . $activity['Activity']['id']
                            ));
                        }
                    }
                    break;
            }
            
            // MOOSOCIAL-2241
            if (isset($this->request->data['video_destination']) && $this->request->data['video_destination']) {
                
                $params = array(
                    'title' => empty($this->request->data['title']) ? __("Untitled video") : $this->request->data['title'],
                    'category_id' => isset($this->request->data['category_id']) ? $this->request->data['category_id'] : 0,
                    'description' => $this->request->data['description'],
                    'video_destination' => $this->request->data['video_destination'],
                );
                
                $this->Activity->updateAll(array(
                    'Activity.params' => "'" . addslashes(json_encode($params)) . "'",
                    'Activity.plugin' => "'UploadVideo'",
                    'Activity.item_type' => "'Video_Video'"
                        ), 
                        array('Activity.id' => $activity['Activity']['id']));
                
                $event = new CakeEvent('ActivitesController.processVideoUpload', $this, array('item' => $this->Activity->read()));
                $this->getEventManager()->dispatch($event);

                echo json_encode(array('activity_id' => $activity['Activity']['id']));

                exit();

            }
        }
    }

    public function ajax_comment()
	{
		$this->_checkPermission( array( 'confirm' => true ) );			
		$this->loadModel('ActivityComment');
		$this->loadModel('Notification');
		$this->loadModel("UserBlock");
		
		$uid = $this->Auth->user('id');
		$cuser = $this->_getUser();
		$commentdata = $this->request->data;

		$commentdata['user_id'] = $uid;

        $isCommentOnePhoto = false;

        // insert this comment to the item page
        $activity = $this->Activity->findById( $commentdata['activity_id'] );
        $profile_id = $activity['Activity']['user_id'];
        $url = '/users/view/' . $profile_id . '/activity_id:' . $activity['Activity']['id'];
        $like_url = $url;

        if ( !empty($activity) && !empty( $activity['Activity']['item_type'] ) )
        {
        	$item_type = $activity['Activity']['item_type'];
            $this->loadModel('Comment');
            // if this comment is on 1 photo
            if (
            	($item_type == 'Photo_Album' && $activity['Activity']['action'] == 'wall_post')
            	|| ($item_type == 'Photo_Photo' && $activity['Activity']['action'] == 'photos_add')
            ) {
                $items = explode(',',$activity['Activity']['items']);
                if (count($items) == 1) {
                    $isCommentOnePhoto = true;
                }
            }
        }
        $owner_id = null;
		switch (strtolower($activity['Activity']['type'])) {
			case 'user':
				$owner_id = $activity['Activity']['target_id'];
				break;
		}
		
		$block_users = array();
		if ($owner_id)
		{
			$block_users = $this->UserBlock->getBlockedUsers($owner_id);
		}
        
        if($isCommentOnePhoto){
            $this->Comment->clear();
            $this->request->data =  array( 'user_id' => $uid,
                'type' => 'Photo_Photo',
                'target_id' => $items[0],
                'thumbnail' => $commentdata['thumbnail'],
                'message' => $commentdata['comment'],
            ) ;
            $this->Comment->save($this->request->data);
            $photoComment = $this->Comment->read();
            $this->set('comment','1');
            $this->set('commentId', $this->Comment->id);
            $this->set('commentInPhoto',1);
            $this->set('photoComment',$photoComment);
			
			$this->Activity->id = $commentdata['activity_id'];
			$this->Activity->save( array( 'modified' => date('Y-m-d H:i:s') ) );

            //notification for user mention
            $url .= '/comment_id:'.$this->Comment->id;
            $this->_sendNotificationToMentionUser($photoComment['Comment']['message'],$url,'mention_user_comment',array(),array($activity['User']['id'],$owner_id));
            
            //send notify activity mention and tag
            $behavior = new NotificationBehavior();
            $model = ClassRegistry::init('ActivityComment');
            $model->data['ActivityComment'] = array(
            	'activity_id'=> $commentdata['activity_id']
            );
            $behavior->afterSave($model,true);
            
			//$this->ActivityComment->save( $commentdata );
			// send notifications to anyone who commented on this item within a day
            $users = $this->Comment->find('list', array('conditions' => array_merge($this->Comment->addBlockCondition(), array('Comment.target_id' => $items[0],
                    'Comment.type' => 'Photo_Photo',
                    'Comment.user_id <> ' . $uid . ' AND Comment.user_id <> ' . $profile_id,
                    'DATE_SUB(CURDATE(),INTERVAL 1 DAY) <= Comment.created'
                )),
                'fields' => array('Comment.user_id'),
                'group' => 'Comment.user_id'
                    ));
                    
        	if (!empty($users)) {
				foreach ($users as $user_id) {
					if (!in_array($user_id, $block_users))
					{
						if ($this->User->checkSettingNotification($user_id,'comment_of_comment')) {
							$this->Notification->record(array('recipients' => $user_id,
								'sender_id' => $uid,
								'action' => 'photo_comment',
								'url' => $url,
								'params' => serialize(array('actor' => $cuser, 'owner' => $activity['User']))
							));
						}
					}
				}
            }

            // event
            $cakeEvent = new CakeEvent('Controller.Comment.afterComment', $this, array('data' => $this->request->data));
            $this->getEventManager()->dispatch($cakeEvent);
        }
        else{
            if ( $this->ActivityComment->save( $commentdata ) )
            {
                $comment = $this->ActivityComment->read();
                $this->set('comment', $comment);

                // send notifications to commenters
                $activity = $this->Activity->findById( $commentdata['activity_id'] );

                $this->Activity->id = $commentdata['activity_id'];
                $this->Activity->save( array( 'modified' => date('Y-m-d H:i:s') ) );

                $params = array( 'actor' => $cuser, 'owner' => $activity['User'] );

                

                // insert this comment to the item page
                if ( !empty( $activity['Activity']['item_type'] ) && !empty( $activity['Activity']['item_id'] ) )
                {
                    $item_type = ( $activity['Activity']['item_type'] == 'Photo_Photo' ) ? 'Photo_Album' : $activity['Activity']['item_type'];

                    $this->loadModel('Comment');

                    /*$this->Comment->create();
                    $this->Comment->save( array( 'user_id' => $uid,
                            'type' => $item_type,
                            'target_id' => $activity['Activity']['item_id'],
                            'message' => $commentdata['comment']
                        ) );*/

                }

                //notification for user mention
                $url .= '/comment_id:'.$comment['ActivityComment']['id'];
                $this->_sendNotificationToMentionUser($comment['ActivityComment']['comment'],$url,'mention_user_comment',array(),array($activity['User']['id'],$owner_id));
                
                
                // send notifications to anyone who commented on this item within a day
            	$users = $this->ActivityComment->find('list', array('conditions' => array_merge($this->ActivityComment->addBlockCondition(), array('ActivityComment.activity_id' => $commentdata['activity_id'],
                    'ActivityComment.user_id <> ' . $uid . ' AND ActivityComment.user_id <> ' . $profile_id,
                    'DATE_SUB(CURDATE(),INTERVAL 1 DAY) <= ActivityComment.created'
                )),
                'fields' => array('ActivityComment.user_id'),
                'group' => 'ActivityComment.user_id'
                    ));
                
	            if (!empty($users)) {
					foreach ($users as $user_id) {
						if (!in_array($user_id, $block_users))
						{
							if ($this->User->checkSettingNotification($user_id,'comment_of_comment')) {
								$this->Notification->record(array('recipients' => $user_id,
									'sender_id' => $uid,
									'action' => 'status_comment_of_comment',
									'url' => $url,
									'params'=>h($activity['User']['moo_title'])
								));
							}
						}
					}
	            }

                // event
                $cakeEvent = new CakeEvent('Controller.Activity.afterComment', $this, array('item' => $comment));
                $this->getEventManager()->dispatch($cakeEvent);

            }
        }

        // send notification and email to wall author
        $notificationStopModel = MooCore::getInstance()->getModel('NotificationStop');

        if ( $uid != $activity['User']['id'] )
        {
			$check_send = false;
			if ($isCommentOnePhoto)
			{
				$check_send = !$notificationStopModel->isNotificationStop($items[0],'Photo_Photo',$activity['User']['id']);
			}
			else
			{
				$check_send = !$notificationStopModel->isNotificationStop($activity['Activity']['id'],'activity',$activity['User']['id']);
			}
			
			if($check_send ){
				
				if ($this->User->checkSettingNotification($activity['User']['id'],'comment_item')) {
					$this->Notification->record( array( 'recipients'  => $activity['User']['id'],
						'sender_id'   => $uid,
						'action'	  => 'own_status_comment',
						'url' 		  => $url,
						'like_url' 	  => $like_url,
					) );
				}
			}
        }
                
                
	}

	public function ajax_remove()
	{
		$this->autoRender = false;		
		$this->_checkPermission( array( 'confirm' => true ) );
		
		$activity = $this->Activity->findById( $this->request->data['id'] );
		$this->_checkExistence( $activity );
		
		$admins = array( $activity['Activity']['user_id'] ); // activity poster
		
		switch (strtolower($activity['Activity']['type']) )
		{
			case 'user':
				$admins[] = $activity['Activity']['target_id']; // user can delete status posted by other users on their profile
				break;
				
			default:
				$type = $activity['Activity']['type'];
				$model = MooCore::getInstance()->getModel($type);
                list($plugin, $name) = mooPluginSplit($type);
                $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                $subject = $model->findById( $activity['Activity']['target_id']);
                if(method_exists($helper,'getAdminList'))
                    $admins = $helper->getAdminList($subject);
                $admins[] = $activity['Activity']['user_id']; // user can delete status posted by other users on their profile
                
				break;
		}
		
		$this->_checkPermission( array( 'admins' => $admins ) );
		$this->Activity->delete( $this->request->data['id'] );
		
		// event
                $cakeEvent = new CakeEvent('Controller.Activity.afterDeleteActivity', $this, array('activity' => $activity));
                $this->getEventManager()->dispatch($cakeEvent);
	}
	
	public function ajax_removeComment()
	{
		$this->autoRender = false;		
		$this->_checkPermission( array( 'confirm' => true ) );
		
		$this->loadModel('ActivityComment');		
		$comment = $this->ActivityComment->findById( $this->request->data['id'] );
		$this->_checkExistence( $comment );
		
		$admins[] =  $comment['ActivityComment']['user_id']; // comment poster
		
		switch (strtolower($comment['Activity']['type']) )
		{
			case 'user':
				$admins[] = $comment['Activity']['user_id']; // user can delete comment posted by other users on their profile
				break;
				
			
				
			default:
                            $type = $comment['Activity']['type'];
                            $model = MooCore::getInstance()->getModel($type);
                            list($plugin, $name) = mooPluginSplit($type);
                            $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                            $subject = $model->findById( $comment['Activity']['target_id']);

                            $admins = $helper->getAdminList($subject);
                            $admins[] = $comment['ActivityComment']['user_id'];
                            $admins[] = $comment['Activity']['user_id'];
				break;
		}
		
		$this->_checkPermission( array( 'admins' => $admins ) );		
		$this->ActivityComment->delete( $this->request->data['id'] );
		
		$comment_last = $this->ActivityComment->find('first',array(
			'conditions' => array('ActivityComment.activity_id' =>  $comment['Activity']['id']),
			'order' => array('ActivityComment.id DESC'),
		));
		$this->loadModel('Activity');
		
		$this->Activity->id = $comment['Activity']['id'];
		if (count($comment_last))
		{
			$this->Activity->save( array( 'modified' => $comment_last['ActivityComment']['created'] ) );
		}
		else
		{
			$this->Activity->save( array( 'modified' => $comment['Activity']['created'] ) );
		}

        $this->loadModel("Comment");
        $this->Comment->deleteAll( array( 'Comment.type' => 'core_activity_comment', 'Comment.target_id' => $comment['ActivityComment']['id'] ), false, false);

        // event
                $cakeEvent = new CakeEvent('Controller.Activity.afterDeleteComment', $this, array('comment' => $comment));
                $this->getEventManager()->dispatch($cakeEvent);
	}
	
	public function ajax_loadActivityEdit($id)
	{
		$this->loadModel('Activity');
		$activity = $this->Activity->findById($id);
		$this->_checkExistence( $activity );
                
                switch (strtolower($activity['Activity']['type'])) {
                    case 'user':
                        $admins[] = $activity['Activity']['user_id']; // user can delete comment posted by other users on their profile
                        break;
                    default:
                        $type = $activity['Activity']['type'];
                        $model = MooCore::getInstance()->getModel($type);
                        list($plugin, $name) = mooPluginSplit($type);
                        $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                        $subject = $model->findById( $activity['Activity']['target_id']);
                        if(method_exists($helper,'getAdminList')){
                            $admins = $helper->getAdminList($subject);
                        }
                        $admins[] = $activity['Activity']['user_id']; // user can delete status posted by other users on their profile
                        break;
                }
                
		$this->_checkPermission( array( 'admins' => $admins ) );
		$this->set('activity', $activity);
	}
	
	public function ajax_editActivity($id)
	{
		$this->loadModel('Activity');
		$activity = $this->Activity->findById($id);
		$this->_checkExistence( $activity );
		$parent_id = null;
                
		switch (strtolower($activity['Activity']['type'])) {
                    case 'user':
                        $admins[] = $activity['Activity']['user_id']; // user can delete comment posted by other users on their profile
                        $parent_id = $activity['Activity']['target_id'];
                        break;
                    default:
                        $type = $activity['Activity']['type'];
                        $model = MooCore::getInstance()->getModel($type);
                        list($plugin, $name) = mooPluginSplit($type);
                        $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                        $subject = $model->findById( $activity['Activity']['target_id']);
                        if(method_exists($helper,'getAdminList')){
                            $admins = $helper->getAdminList($subject);
                        }
                        $admins[] = $activity['Activity']['user_id']; // user can delete status posted by other users on their profile
                        break;
                }

                $previous_users = $this->_getUserIdInMention($activity['Activity']['content']);
                $previous_users = is_array($previous_users)?$previous_users: array();
                $new_users = $this->_getUserIdInMention($this->request->data['message']);
                $new_users = is_array($new_users)? $new_users: array();
                $new_add_users = array_diff($new_users,$previous_users);

		$this->_checkPermission( array( 'admins' => $admins ) );
		
		$this->loadModel('CommentHistory');
		
		$this->Activity->id = $activity['Activity']['id'];
		$this->Activity->save(array('edited'=>true,'modified'=>false,'content'=>$this->request->data['message']));
                
                // event
                $cakeEvent = new CakeEvent('Controller.Activity.afterEditActivity', $this, array('item' => $activity));
                $this->getEventManager()->dispatch($cakeEvent);
		
		$uid = $this->Auth->user('id');

        if(!empty($new_add_users)){
            $url = '/users/view/' . $activity['Activity']['user_id'] . '/activity_id:' . $activity['Activity']['id'];
            $this->_sendNotificationToMentionUser($this->request->data['message'],$url,'mention_user',$new_add_users,$parent_id);
        }
        
		if (!$activity['Activity']['edited'])
		{
			$this->CommentHistory->save(array(
				'user_id' => $activity['Activity']['user_id'],
				'type' => 'Activity',
				'content' =>  $activity['Activity']['content'],
				'target_id' => $activity['Activity']['id'],
				'created' => $activity['Activity']['created']
			));
		}
		
		$this->CommentHistory->clear();
		$this->CommentHistory->save(array(
			'user_id' => $uid,
			'type' => 'Activity',
			'target_id' => $activity['Activity']['id'],
			'content' => $this->request->data['message'],
		));
		
		$activity = $this->Activity->read();
		if ($uid != $activity['Activity']['user_id'])
		{
			$this->set('other_user',$this->Auth->user());
		}	
		$this->set('activity', $activity);
		
		
		
	}
	
	public function ajax_loadActivityCommentEdit($id)
	{
		$this->loadModel('ActivityComment');
		$comment = $this->ActivityComment->findById($id);
		$this->_checkExistence( $comment );
                
		$admins = array( $comment['ActivityComment']['user_id'] ); // activity poster
                
		switch (strtolower($comment['Activity']['type'])) {
                    case 'user':
                        $admins[] = $comment['Activity']['target_id']; // user can delete comment posted by other users on their profile
                        break;
                    default:
                        $type = $comment['Activity']['type'];
                        $model = MooCore::getInstance()->getModel($type);
                        list($plugin, $name) = mooPluginSplit($type);
                        $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                        $subject = $model->findById($comment['Activity']['target_id']);

                        $admins = $helper->getAdminList($subject);
                        $admins[] = $comment['ActivityComment']['user_id'];
                        break;
                }
                
                $admins[] = $comment['Activity']['user_id'];
                
                $this->_checkPermission( array( 'admins' => $admins ) );
                
		$this->set('activity_comment', $comment);
	}
	
	public function ajax_editActivityComment($id)
	{
		$this->loadModel('ActivityComment');
		$activity_comment = $this->ActivityComment->findById($id);
		$this->_checkExistence( $activity_comment );
		$admins = array( $activity_comment['ActivityComment']['user_id'] ); // activity poster
        $owner_id = null;
		switch (strtolower($activity_comment['Activity']['type'])) {
                    case 'user':
                        $admins[] = $activity_comment['Activity']['target_id']; // user can delete comment posted by other users on their profile
                        $owner_id = $activity_comment['Activity']['target_id'];
                        break;
                    default:
                        $type = $activity_comment['Activity']['type'];
                        $model = MooCore::getInstance()->getModel($type);
                        list($plugin, $name) = mooPluginSplit($type);
                        $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                        $subject = $model->findById($activity_comment['Activity']['target_id']);

                        $admins = $helper->getAdminList($subject);
                        $admins[] = $activity_comment['ActivityComment']['user_id'];
                        break;
                }
                
                $admins[] = $activity_comment['Activity']['user_id'];
                
		$this->_checkPermission( array( 'admins' => $admins ) );

        $previous_users = $this->_getUserIdInMention($activity_comment['ActivityComment']['comment']);
        $previous_users = is_array($previous_users)?$previous_users: array();
        $new_users = $this->_getUserIdInMention($this->request->data['message']);
        $new_users = is_array($new_users)? $new_users: array();
        $new_add_users = array_diff($new_users,$previous_users);

		$this->loadModel('CommentHistory');
		
		$this->ActivityComment->id = $activity_comment['ActivityComment']['id'];
		
		$uid = $this->Auth->user('id');
		
		$photo = 0;
		if (trim($this->request->data['comment_attach']) == '')
		{
			if (trim($activity_comment['ActivityComment']['thumbnail']) != '')
			{
				$photo = 3; //Remove
			}
		}
		else
		{
			if (trim($activity_comment['ActivityComment']['thumbnail']) == '')
			{
				$photo = 1; // Add new
			}
			elseif (trim($activity_comment['ActivityComment']['thumbnail']) != $this->request->data['comment_attach'])
			{
				$photo = 2; //Replace
			}
		}
		
		if ($photo)
		{
			$this->ActivityComment->save(array('edited'=>true,'modified'=>false,'thumbnail'=>$this->request->data['comment_attach'],'comment'=>$this->request->data['message']));
		}
		else 
		{
			$this->ActivityComment->save(array('edited'=>true,'modified'=>false,'comment'=>$this->request->data['message']));
		}
                
                // event
                $cakeEvent = new CakeEvent('Controller.Activity.afterEditComment', $this, array('item' => $activity_comment));
                $this->getEventManager()->dispatch($cakeEvent);

        if(!empty($new_add_users)){
            $url = '/users/view/' . $activity_comment['ActivityComment']['user_id'] . '/activity_id:' . $activity_comment['ActivityComment']['activity_id'];
            $this->_sendNotificationToMentionUser($this->request->data['message'],$url,'mention_user_comment',$new_add_users,array($activity_comment['Activity']['user_id'],$owner_id));
        }
        
		if (!$activity_comment['ActivityComment']['edited'])
		{			
			$this->CommentHistory->save(array(
				'user_id' => $activity_comment['ActivityComment']['user_id'],
				'type' => 'Core_Activity_Comment',
				'content' =>  $activity_comment['ActivityComment']['comment'],
				'target_id' => $activity_comment['ActivityComment']['id'],
				'created' => $activity_comment['ActivityComment']['created'],
				'photo' => $activity_comment['ActivityComment']['thumbnail'] != '' ? 1 : 0, 
			));
		}
        
		$this->CommentHistory->clear();
		$this->CommentHistory->save(array(
			'user_id' => $uid,
			'type' => 'Core_Activity_Comment',
			'target_id' => $activity_comment['ActivityComment']['id'],
			'content' => $this->request->data['message'],
			'photo' => $photo
		));
		
		$activity_comment = $this->ActivityComment->read();	
		if ($uid != $activity_comment['ActivityComment']['user_id'])
		{
			$this->set('other_user',$this->Auth->user());
		}
		
		$this->set('activity_comment', $activity_comment);
	}
        
        public function ajax_stop_notification(){
            $this->autoRender = false;
            $data = $this->request->data;
            $item_id = isset($data['item_id']) ? $data['item_id'] : '';
            $item_type = isset($data['item_type']) ? $data['item_type'] : '';
            $uid = $this->Auth->user('id');
            
            $this->loadModel('NotificationStop');
            
            $count = $this->NotificationStop->find('count', array('conditions' => array('item_type' => $item_type,
                    'item_id' => $item_id,
                    'user_id' => $uid)
                    ));
            $response = array();
            $this->NotificationStop->set(array(
                'user_id' => $uid,
                'item_type' => $item_type,
                'item_id' => $item_id
            ));
            
            if ($count){
                $this->NotificationStop->deleteAll(array('NotificationStop.item_type' => $item_type, 
                    'NotificationStop.item_id' => $item_id,
                    'NotificationStop.user_id' => $uid));
                $response['is_stop'] = false;
                $response['message'] = __("You'll get notifications whenever this activity has new activity.");
            }
            else if ($this->NotificationStop->save()) { // successfully saved	
                $response['is_stop'] = true;
                $response['message'] = __("You'll no longer get notifications about this activity.");
            }
            echo json_encode($response);
            
        }
        
        public function ajax_remove_tags(){
            $this->autoRender = false;
            $data = $this->request->data;
            $item_id = isset($data['item_id']) ? $data['item_id'] : '';
            $item_type = isset($data['item_type']) ? $data['item_type'] : '';
            $uid = $this->Auth->user('id');
            
            // find user tagging by item_type and item_id
            $this->loadModel('UserTagging');
            $taggedUser = $this->UserTagging->find('first', array('conditions' => array(
                'item_id' => $item_id,
                'item_table' => Inflector::pluralize($item_type)
            )));

            if (!empty($taggedUser)){
                $tagging = $taggedUser['UserTagging']['users_taggings'];
                $xpl = explode(',', $tagging);
                if (($key = array_search($uid, $xpl)) !== false){
                    unset($xpl[$key]);
                }
                if (empty($xpl)){ // remove user tagging record
                    $this->UserTagging->delete($taggedUser['UserTagging']['id']);
                }else{ // update user tagging record
                    $new_tagging = implode(',', $xpl);
                    $this->UserTagging->id = $taggedUser['UserTagging']['id'];
                    $this->UserTagging->set(array(
                        'users_taggings' => $new_tagging
                    ));
                    $this->UserTagging->save();
                }
            }

            //remove mentioned user
            $this->loadModel('Activity');
            $activity = $this->Activity->findById($item_id);
            if(!empty($activity)){
                preg_match_all(REGEX_MENTION,$activity['Activity']['content'],$matches);
                if(!empty($matches) && in_array($uid,$matches[1])){
                    $new_content = preg_replace('/@\['.$uid.':([a-zA-Z0-9_ ]+)\]/','$1',$activity['Activity']['content']);
                    $this->Activity->clear();
                    $this->Activity->id = $activity['Activity']['id'];
                    $this->Activity->save(array('content' => $new_content));
                }
            }
        
        }
        
        public function send_birthday_wish() {
            $this->autoRender = false;
            $this->_checkPermission(array('confirm' => true));
            $uid = $this->Auth->user('id');

            $this->request->data['user_id'] = $uid;
            $this->request->data['content'] = $this->request->data['message'];
            $this->request->data['privacy'] = (!empty($this->request->data['privacy']) ) ? $this->request->data['privacy'] : PRIVACY_ME;
            if ($this->request->data['type'] == 'User' && $this->request->data['target_id']) {
                $user_id = $this->request->data['target_id'];
                $user = $this->User->findById($user_id);
                $this->request->data['privacy'] = $user['User']['privacy'];
            }

            $result = array(
                'success' => false
            );
            if ($this->Activity->save($this->request->data)) {
                $activity = $this->Activity->read();

                if (!empty($this->request->data['wall_photo_id'])) {
                    $this->loadModel('Photo.Photo');
                    $activity['Content'] = $this->Photo->findById($this->request->data['wall_photo_id']);
                } else {
                    $event = new CakeEvent('ActivitesController.afterShare', $this, array('activity' => $activity));
                    $this->getEventManager()->dispatch($event);
                    $activity = $this->Activity->read();
                }

                $this->set('activity', $activity);

                switch (strtolower($this->request->data['type'])) {
                    case 'user':
                        if (!empty($this->request->data['target_id']) && $this->request->data['target_id'] != $uid) { // post on other user's profile
                            $this->loadModel('Notification');
                            $this->Notification->record(array('recipients' => $this->request->data['target_id'],
                                'sender_id' => $uid,
                                'action' => 'profile_comment',
                                'url' => '/users/view/' . $this->request->data['target_id'] . '/activity_id:' . $activity['Activity']['id']
                            ));
                        }
                        break;
                }
                $result['success'] = true;
            }
            
            echo json_encode($result);
        }

    public function ajax_changeActivityPrivacy(){
        $this->autoRender = false;
        if($this->request->is('post')){
            $id = $this->request->data['activityId'];
            $privacy = $this->request->data['privacy'];
            $activity = $this->Activity->findById($id);
            if(!empty($activity)){
                $this->Activity->clear();
                $this->Activity->id = $id;
                if($this->Activity->save(array('privacy' => $privacy) ) ){
                    switch($privacy){
                        case '1':
                            $text = __('Shared with: Everyone');
                            $icon = 'public';
                            break;
                        case '2':
                            $text = __('Shared with: Friend');
                            $icon = 'people';
                            break;
                        case '3':
                            $text = __('Shared with: Only Me');
                            $icon = 'lock';
                            break;
                        default:
                            $text = '';
                            $icon = '';
                    }
                    echo json_encode(array('text' => $text, 'icon' => $icon));
                }
            }
        }
    }

    public function ajax_preview_link(){
            $this->Activity->parseLink($this->request->data);
            if(isset($this->request->data['params'])){
                echo json_encode(unserialize($this->request->data['params']));
            }else{
                echo json_encode('');
            }     
            exit();
    }
    private function _removeGroupJoinActivities($activities)
    {
        foreach ($activities as $key => &$activity) {
            $aActivity = $activity['Activity'];
            if ($aActivity['action'] == 'group_join') {
                $viewer = MooCore::getInstance()->getViewer();
                $groupIds = explode(',', $aActivity['items']);
                $groupModel = MooCore::getInstance()->getModel('Group.Group');

                $privateGroupIds = $groupModel->findPrivateGroup($groupIds, $viewer);
                $groupIds = array_diff($groupIds, $privateGroupIds);

                if (empty($groupIds)) {
                    unset($activities[$key]);
                } else {
                    $activity['Activity']['items'] = implode(',', $groupIds);
                    $activity['Activity']['target_id'] = 0;
                }
            }
        }
        return $activities;
    }
    
    public function ajax_pin($id,$isRedirect=true)
    {
    	$this->_checkPermission();
    	$this->loadModel("Activity");
    	$activity = $this->Activity->findById($id);
    	
    	$this->_checkExistence($activity);
    	$viewer = MooCore::getInstance()->getViewer();
    	$admins = array();
    	if ($activity['Activity']['target_id'] && strtolower($activity['Activity']['type']) != 'user')
    	{
    		list($plugin, $name) = mooPluginSplit($activity['Activity']['type']);
	    	$helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
	    	$subject = MooCore::getInstance()->getItemByType($activity['Activity']['type'], $activity['Activity']['target_id']);
	    	$admins = $helper->getAdminList($subject);
	    }    	
    	
	    if (!$viewer['Role']['is_admin'] && !in_array($viewer['User']['id'], $admins))
    	{
    		$this->_checkExistence(null);
    	}
    	
    	$this->Activity->id = $id;
    	$this->Activity->save(array(
    		'pin' => !$activity['Activity']['pin'],
    		'pin_date' => !$activity['Activity']['pin'] ? date('Y-m-d H:i:s') : null,
    		'pin_user_id' => $viewer['User']['id'],
    		'modified' => false
    	));
    	if (!$activity['Activity']['pin'])
    	{
            if($isRedirect) $this->Session->setFlash(__('Activity has been pined'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
            else {
                return $message = __('Activity has been pined');
            }
    	}
    	else 
    	{
            if($isRedirect) $this->Session->setFlash(__('Activity has been unpinned'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
            else {
                return $message = __('Activity has been unpinned');
            }
    	}
    	if($isRedirect)  $this->redirect($this->referer());
    }
    
    public function ajax_activity_pin($id,$isRedirect=true)
    {
    	$this->_checkPermission();
    	$this->loadModel("Activity");
    	$activity = $this->Activity->findById($id);
    	
    	$this->_checkExistence($activity);
    	$viewer = MooCore::getInstance()->getViewer();
    	
    	if (!$viewer['Role']['is_admin'])
    	{
    		$this->_checkExistence(null);
    	}
    	
    	$this->Activity->id = $id;
    	$this->Activity->save(array(
    		'activity_pin' => !$activity['Activity']['activity_pin'],
    		'activity_pin_date' => !$activity['Activity']['activity_pin'] ? date('Y-m-d H:i:s') : null,
    		'activity_pin_user_id' => $viewer['User']['id'],
    		'modified' => false
    	));
    	if (!$activity['Activity']['pin'])
    	{
            if($isRedirect)  $this->Session->setFlash(__('Activity has been pined'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
            else {
                return $message = __('Activity has been pined');
            }
    	}
    	else
    	{
            if($isRedirect)  $this->Session->setFlash(__('Activity has been unpinned'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
            else {
                return $message = __('Activity has been unpinned');
            }
    	}
    	if($isRedirect) $this->redirect($this->referer());
    }
}
