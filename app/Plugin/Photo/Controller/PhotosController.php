<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class PhotosController extends PhotoAppController {

    
    public function beforeFilter(){
        parent::beforeFilter();
        $this->loadModel('Photo.Photo');
        $this->loadModel('Photo.Album');
        $this->loadModel('Photo.PhotoTag');
    }
    public function index($cat_id = null) {
        $this->loadModel('Photo.Album');
        $this->loadModel('Tag');

        $cat_id = intval($cat_id);
        $role_id = $this->_getUserRoleId();

        $tags = $this->Tag->getTags('Photo_Album', Configure::read('core.popular_interval'));
        //get friend list
        $this->loadModel('Friend');
        $sFriendsList = '';
        $aFriendListId =  array_keys($this->Friend->getFriendsList($this->Auth->user('id')));
        $sFriendsList = implode(',',$aFriendListId);
        $album_more_result = 0;
        if (!empty($cat_id)){
            $albums = $this->Album->getAlbums('category', $cat_id, 1, RESULTS_LIMIT, '', $role_id);
            $more_albums = $this->Album->getAlbums('category', $cat_id, 2, RESULTS_LIMIT, '', $role_id);
            if(!empty($more_albums))
                $album_more_result = 1;
        }else{
            $albums = $this->Album->getAlbums(null,$this->Auth->user('id'),1,RESULTS_LIMIT,$sFriendsList, $role_id);
            $more_albums = $this->Album->getAlbums(null,$this->Auth->user('id'),2,RESULTS_LIMIT,$sFriendsList, $role_id);
            if(!empty($more_albums))
                $album_more_result = 1;
        }

        $albums = Hash::sort($albums,'{n}.Album.id','desc');
        $this->set('tags', $tags);
        $this->set('albums', $albums);
        $this->set('cat_id', $cat_id);
        $this->set('title_for_layout', '');
        $this->set('album_more_result', $album_more_result);
    }
    
    public function profile_user_photo($uid = null,$isRedirect=true) {
        $uid = intval($uid);
        if($isRedirect) {
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        }
        else {
                $page = $this->request->query('page') ? $this->request->query('page') : 1;
        }

        $photos = $this->PhotoTag->getPhotos($uid, $page);

        $role_id = $this->_getUserRoleId();

        $addition_param = null;
        //check if current user is this profile's owner
        if($this->Auth->user('id') == $uid)
            $role_id = ROLE_ADMIN;
        else{
            //check if current user is a friend of this profile's owner
            $this->loadModel('Friend');
            $are_friend = $this->Friend->areFriends($this->Auth->user('id'), $uid);
            if(!empty($are_friend))
                $addition_param['are_friend'] = true;
        }
        $this->set('photos', $photos);
        $this->set('more_url', '/photos/profile_user_photo/' . $uid . '/page:' . ( $page + 1 ));
        $this->set('album_more_url', '/photos/profile_user_album/' . $uid . '/page:' . ( $page + 1 ));
        $this->set('tag_uid', $uid);
        $albums = $this->Album->getAlbums('user', $uid,$page,RESULTS_LIMIT,$addition_param, $role_id);
        $more_albums = $this->Album->getAlbums('user', $uid,$page + 1,RESULTS_LIMIT,$addition_param, $role_id);
        $album_more_result = 0;
        if(!empty($more_albums))
            $album_more_result = 1;
        $this->set('albums', $albums);
        $this->set('page', $page);
        $this->set('profileUserPhoto', true);
        $this->set('type', APP_USER);
        $this->set('is_tag',true);
        $this->set('photosAlbumCount', $this->PhotoTag->getPhotosCount($uid));
        $this->set('album_more_result', $album_more_result);
        if($isRedirect) {
            if ($page > 1)
                $this->render('/Elements/lists/photos_list');
            else
                $this->render('Photo.Photos/profile_user_photo');
        }
        
    }
    
    public function profile_user_album($uid = null,$isRedirect=true) {
        $uid = intval($uid);
        $this->loadModel('Photo.Album');
        if($isRedirect) {
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        }
        else {
                $page = $this->request->query('page') ? $this->request->query('page') : 1;
        }
        $role_id = $this->_getUserRoleId();
        
        $addition_param = null;
     	if($this->Auth->user('id') == $uid)
            $role_id = ROLE_ADMIN;
        else{
            //check if current user is a friend of this profile's owner
            $this->loadModel('Friend');
            $are_friend = $this->Friend->areFriends($this->Auth->user('id'), $uid);
            if(!empty($are_friend))
                $addition_param['are_friend'] = true;
        }

        $albums = $this->Album->getAlbums('user', $uid, $page,RESULTS_LIMIT, $addition_param, $role_id);
        $album_more_result = 0;
        $more_albums = $this->Album->getAlbums('user', $uid, $page + 1,RESULTS_LIMIT, $addition_param, $role_id);
        if(!empty($more_albums))
            $album_more_result = 1;

        $this->set('albums', $albums);
        $this->set('album_more_url', '/photos/profile_user_album/' . $uid . '/page:' . ( $page + 1 ));
        $this->set('user_id', $uid);
        $this->set('album_more_result', $album_more_result);
        if($isRedirect) {
            if ($page > 1)
                $this->render('/Elements/lists/albums_list');
            else
                $this->render('Photo.Photos/profile_user_album');
        }
        
    }

    public function ajax_browse($type = null, $target_id = null,$isRedirect = true) {
         if($isRedirect) {
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        }
        else {
            $page = $this->request->query('page') ? $this->request->query('page') : 1;
        }
        $uid = $this->Auth->user('id');
        $this->set('title_for_layout', __( 'Photos'));
        if($type == 'group_group'){ 
            // check permission if group is private
            $this->loadModel('Group.Group');
            $group = $this->Group->findById($target_id);
            $this->_checkExistence($group);
            $this->loadModel('Group.GroupUser');
            $is_member = $this->GroupUser->isMember($uid, $target_id);

            if ($group['Group']['type'] == PRIVACY_PRIVATE) {
                $cuser = $this->_getUser();

                if (!$cuser['Role']['is_admin'] && !$is_member)
                {
                    if($isRedirect) {
                        $this->autoRender = false;
                        echo 'Only group members can view photos';
                        return;
                    }
                    else {
                        $this->throwErrorCodeException('not_group_member');
                        return $error = array(
                            'code' => 401,
                            'message' => __("This is a private group"),
                        );
                    }
                }
            }
        }
        if ($type != 'Photo_Album')
        {
        	//Load helper for third plugin
        	list($plugin, $name) = mooPluginSplit($type);
        	$this->set('plugin',$plugin);
        	$this->set('name',$name);
        	if ($plugin)
        		$this->helpers[] = $plugin.'.'.$plugin;        	
        }

        $limit = Configure::read('Photo.photo_item_per_pages');
   		$params = array();
   		if ($type == 'Photo_Album')
   		{
   			$album = MooCore::getInstance()->getItemByType($type,$target_id);
	        if ($album['Album']['type'] == 'newsfeed')
	        {
	        	$this->loadModel('Friend');
	        	$params['newsfeed'] = true;
	        	if ($uid == $album['User']['id'] || $this->_getUserRoleId() == ROLE_ADMIN || ($uid && $this->Friend->areFriends($uid,$album['User']['id'])))
	        	{
	        		$params['is_friend'] = true;
	        	}
	        }
   		}
        $photos = $this->Photo->getPhotos($type, $target_id, $page, $limit,$params);

        $this->set('photosAlbumCount', $this->Photo->getPhotosCount($type, $target_id,$params));
        $this->set('photos', $photos);
        $this->set('target_id', $target_id);
        $this->set('page', $page);
        $this->set('type', $type);
        $this->set('more_url', '/photos/ajax_browse/' . h($type) . '/' . intval($target_id) . '/page:' . ( $page + 1 ));
        if($isRedirect) {
            if ($page == 1 && $type != 'Photo_Album'){
                if($this->theme != "mooApp"){
                    $this->render(($plugin ? $plugin.'.' : '').'/Elements/ajax/'.strtolower($name).'_photo');
                }
            }
            else{
                $this->render('/Elements/lists/photos_list');
            }
        }
    }

    public function upload($aid = null) {
        $this->_checkPermission(array('confirm' => true));
        $this->loadModel('Photo.Album');

        $uid = $this->Auth->user('id');

        $albums = $this->Album->find('list', array('conditions' => array('Album.user_id' => $uid, 'Album.photo_count <= ' . MAX_PHOTOS, 'Album.type' => '')));
        $this->set('albums', $albums);

        $this->set('aid', $aid);
        $this->set('title_for_layout', __('Upload Photos'));
    }

    public function view($id = null) {
    	$uid = $this->Auth->user('id');
        $id = intval($id);
        $photo = $this->Photo->findById($id);
        $this->_checkExistence($photo);
        $this->_checkPermission(array('aco' => 'photo_view'));
        
            $is_show_full_photo = true;
            $is_redirect = false;
            $this->loadModel("UserBlock");
            $block_users = $this->UserBlock->getBlockedUsers($uid);

            if (in_array($photo['Photo']['user_id'],$block_users))
            {
                    $is_show_full_photo = false;
            }

            MooCore::getInstance()->setSubject($photo);
            if ($this->request->is('ajax'))
            {
                    $limit = 20;
            }
            else 
            {
                    $limit = Configure::read('Photo.photo_item_per_pages');
            }

            $params = array();
            switch ($photo['Photo']['type']) {
                case 'Photo_Album':
                    $is_redirect = !$this->_checkPrivacy($photo['Album']['privacy'], $photo['User']['id'],null, false);
                    if ($is_redirect)
                    {
                            $is_show_full_photo = false;
                    }

                    $title = $photo['Album']['moo_title'];

                            if ($photo['Album']['type'] == 'newsfeed')
                            {
                                    $this->loadModel('Friend');
                                    $params['newsfeed'] = true;
                                    if ($uid == $photo['Album']['user_id'] || $this->_getUserRoleId() == ROLE_ADMIN || ($uid && $this->Friend->areFriends($uid,$photo['Album']['user_id'])))
                                    {
                                            $params['is_friend'] = true;
                                    }
                            }
                    if (!$photo['Photo']['album_type'])
                    {
                        $photos = $this->Photo->getPhotos('Photo_Album', $photo['Photo']['target_id'], 1, $limit, $params);
                    }
                    else
                    {
                        $cuser = MooCore::getInstance()->getViewer();
                            //check privacy
                        switch ($photo['Photo']['album_type'])
                        {
                            case 'Group_Group':
                                $this->loadModel('Group.GroupUser');
                                $group = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                                if (!$group || ((!$cuser || ($cuser && !$cuser['Role']['is_admin'] )) && $group['Group']['type'] == PRIVACY_PRIVATE && !$this->GroupUser->isMember($uid, $group['Group']['id']))){                                
                                    $is_redirect = true;
                                    $is_show_full_photo = false;
                                }
                                break;
                            case 'Event_Event':
                                $this->loadModel('Event.EventRsvp');
                                $event = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                                if (!$event || ((!$cuser || ($cuser && !$cuser['Role']['is_admin'] )) && $event['Event']['type'] == PRIVACY_PRIVATE && !$this->EventRsvp->getMyRsvp($uid, $event['Event']['id']))){
                                    $is_redirect = true;
                                    $is_show_full_photo = false;
                                }
                                break;
                        }
                        $photos = $this->Photo->getFeedPhotos($photo['Photo']['album_type'],$photo['Photo']['album_type_id'], 1, $limit, $params);
                    }


                    break;

                case 'Group_Group':
                    $group = MooCore::getInstance()->getItemByType($photo['Photo']['type'],$photo['Photo']['target_id']);               
                    $title = __( 'Photos of %s', $group['Group']['name']);
                    $photos = $this->Photo->getPhotos('Group_Group', $photo['Photo']['target_id'], 1, $limit);
                    $this->set('group',$group);

                    // check group privacy if it's group photo
                    $cuser = MooCore::getInstance()->getViewer();
                    $this->loadModel('Group.GroupUser');
                    if ((!$cuser || ($cuser && !$cuser['Role']['is_admin'] )) && $group['Group']['type'] == PRIVACY_PRIVATE && !$this->GroupUser->isMember($uid, $group['Group']['id'])){                    
                        $is_redirect = true;
                        $is_show_full_photo = false;
                    }

                    break;
            }
        
            $this->_getPhotoDetail($photo);

            $this->loadModel('Friend');        
            $friends = $this->Friend->getFriendsList($uid);

            $type = $photo['Photo']['type'];
            $target_id = $photo['Photo']['target_id'];		
            if (!empty($this->request->named['uid'])) {
                $this->loadModel('User');
                $user = $this->User->findById($this->request->named['uid']);
                $this->set('user', $user);

                $this->loadModel('Photo.PhotoTag');
                $photos = $this->PhotoTag->getPhotos($this->request->named['uid']);

                $type = APP_USER;
                $target_id = $this->request->named['uid'];
            }

            if (!empty($this->request->query['uid'])) {
                    $this->loadModel('Photo.PhotoTag');
                $photos = $this->PhotoTag->getPhotos($this->request->query['uid'],1,$limit);
            }

            $can_tag = false;
            if ($uid && ( $uid == $photo['User']['id'] || $this->Friend->areFriends($uid, $photo['User']['id']) ))
                $can_tag = true;

            if (!empty($this->request->query['uid'])) 
            {        	
                    $total_photos = $this->PhotoTag->getPhotosCount($this->request->query['uid']);
            }
            else 
            {
                    if (!$photo['Photo']['album_type'])
                    {
                        $total_photos = $this->Photo->getPhotosCount($photo['Photo']['type'], $photo['Photo']['target_id'], $params);
                    }
                    else
                    {
                        $total_photos = $this->Photo->getFeedPhotosCount($photo['Photo']['album_type'], $photo['Photo']['album_type_id']);
                    }
            }
            $this->set('photosAlbumCount', $total_photos);
            $this->set('page', 1);
            if (!empty($this->request->query['uid']))
            {
                    $all_photos = $this->PhotoTag->find('all',array('conditions'=>array(
                            'PhotoTag.user_id' => $this->request->query['uid']
                    )));
            }
            else 
            {
                    if (!$photo['Photo']['album_type'])
                    {
                        $all_photos = $this->Photo->getAllPhotos($photo['Photo']['type'], $photo['Photo']['target_id'], $params);
                    }
                    else
                    {
                        $all_photos = $this->Photo->getAllFeedPhotos($photo['Photo']['album_type'], $photo['Photo']['album_type_id']);
                    }
            }
            $photo_position = $this->findPositionItem($photo, $all_photos);
            $this->set(compact('photos', 'photo', 'type', 'target_id', 'can_tag', 'friends', 'total_photos', 'photo_position'));
            $this->set('no_right_column', true);
            $this->set('title_for_layout', $title);
            $photo_description = !empty($photo['Photo']['caption']) ? htmlspecialchars($photo['Photo']['caption']) : htmlspecialchars($photo['Album']['description']);

            $description = $this->getDescriptionForMeta($photo_description);
            if ($description && $is_show_full_photo) {
                $this->set('description_for_layout', $description);
                $this->set('mooPageKeyword', $this->getKeywordsForMeta($description));
            }

            // set og:image
            if ($photo['Photo']['thumbnail']) {
                $mooHelper = MooCore::getInstance()->getHelper('Core_Moo');
                $this->set('og_image', $mooHelper->getImageUrl($photo, array('prefix' => '850')));

            }

            $this->set('is_show_full_photo',$is_show_full_photo);
            $this->set('is_redirect',$is_redirect);
            // theater mode MOOSOCIAL-1593
            if ($this->request->is('ajax')){
                $this->render('/Elements/photos/theater');
            }
            else {
                    if ($is_redirect)
                    {
                            $this->_checkPrivacy($photo['Album']['privacy'], $photo['User']['id'],null);

                            $this->Session->setFlash(__('Item does not exist'), 'default', array('class' => 'error-message'));
                    $this->redirect(array(
                            "plugin" => "page", 
                            "controller" => "pages",
                            "action" => "error"));
                    }
            }
            if($this->theme == "mooApp"){
                $this->set('photoId',$id);
            }
        
    }
    
    // $needle : Photo need to be find position in $haystack
    // $haystack : list Photo
    protected function findPositionItem($needle, $haystack){
        foreach ($haystack as $key => $item){
            if ($needle['Photo']['id'] == $item['Photo']['id']){
                return $key + 1;
            }
        }
        return false;
    }

    public function ajax_view($id = null, $mode = null) {
        $uid = $this->Auth->user('id');
        $id = intval($id);
        $photo = $this->Photo->findById($id);
        $this->_checkExistence($photo);
        $this->_checkPermission(array('aco' => 'photo_view'));
        
        $is_show_full_photo = true;
        $is_redirect = false;
        $this->loadModel("UserBlock");
        $this->loadModel("Friend");
        $block_users = $this->UserBlock->getBlockedUsers($uid);
		
        if (in_array($photo['Photo']['user_id'],$block_users))
        {
        	$is_show_full_photo = false;
        }
        
        MooCore::getInstance()->setSubject($photo);
        if ($this->request->is('ajax')){
        	$limit = 20;
        }
        else
        {
        	$limit = Configure::read('Photo.photo_item_per_pages');
        }
        $params = array();
        switch ($photo['Photo']['type']) {
            case 'Photo_Album':
                $is_redirect = !$this->_checkPrivacy($photo['Album']['privacy'], $photo['User']['id'],null,false);
        		if ($is_redirect)
                {
              		$is_show_full_photo = false;
                }
                
                $title = $photo['Album']['moo_title'];
                
		        if ($photo['Album']['type'] == 'newsfeed')
		        {
		        	$this->loadModel('Friend');
		        	$params['newsfeed'] = true;
		        	if ($uid == $photo['Album']['user_id'] || $this->_getUserRoleId() == ROLE_ADMIN || ($uid && $this->Friend->areFriends($uid,$photo['Album']['user_id'])))
		        	{
		        		$params['is_friend'] = true;
		        	}
		        }
                if (!$photo['Photo']['album_type'])
                {
                    //$photos = $this->Photo->getPhotos('Photo_Album', $photo['Photo']['target_id'], 1, $limit, $params);
                }
                else
                {
                    $cuser = MooCore::getInstance()->getViewer();
                	//check privacy
                    switch ($photo['Photo']['album_type'])
                    {
                        case 'Group_Group':
                            $this->loadModel('Group.GroupUser');
                            $group = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                            if (!$group || ((!$cuser || ($cuser && !$cuser['Role']['is_admin'] )) && $group['Group']['type'] == PRIVACY_PRIVATE && !$this->GroupUser->isMember($uid, $group['Group']['id']))){
                                $is_redirect = true;
                    			$is_show_full_photo = false;
                            }
                            break;
                        case 'Event_Event':
                            $this->loadModel('Event.EventRsvp');
                            $event = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                            if (!$event || ((!$cuser || ($cuser && !$cuser['Role']['is_admin'] )) && $event['Event']['type'] == PRIVACY_PRIVATE && !$this->EventRsvp->getMyRsvp($uid, $event['Event']['id']))){
                                $is_redirect = true;
                   				$is_show_full_photo = false;
                            }
                            break;
                    }
                    $photos = $this->Photo->getFeedPhotos($photo['Photo']['album_type'],$photo['Photo']['album_type_id'], 1, $limit, $params);
                }


                break;

            case 'Group_Group':
            	$group = MooCore::getInstance()->getItemByType($photo['Photo']['type'],$photo['Photo']['target_id']);         
                $title = __( 'Photos of %s', $group['Group']['name']);
                //$photos = $this->Photo->getPhotos('Group_Group', $photo['Photo']['target_id'], 1, $limit);
               	$this->set('group',$group);
                
                // check group privacy if it's group photo
        		$cuser = MooCore::getInstance()->getViewer();
                $this->loadModel('Group.GroupUser');
                if ((!$cuser || ($cuser && !$cuser['Role']['is_admin'] )) && $group['Group']['type'] == PRIVACY_PRIVATE && !$this->GroupUser->isMember($uid, $group['Group']['id'])){                    
                    $is_redirect = true;
                    $is_show_full_photo = false;
                }
                
                break;
        }
        
        $this->_getPhotoDetail($photo, $mode);

        $can_tag = false;
        if ($uid && ( $uid == $photo['User']['id'] || $this->Friend->areFriends($uid, $photo['User']['id']) )){
            $can_tag = true;
        }
        
        $type = $photo['Photo']['type'];
        $target_id = $photo['Photo']['target_id'];

        if (!empty($this->request->named['uid'])) {
            $type = APP_USER;
            $target_id = $this->request->named['uid'];
        }
        
        $this->set(compact('photo', 'can_tag', 'type', 'target_id', 'is_show_full_photo','is_redirect'));
        
        $this->render('/Elements/ajax/photo_detail');
        
    }
    
    public function ajax_view_theater($id = null, $mode = null) {
    	$uid = $this->Auth->user('id');
        $id = intval($id);
        $photo = $this->Photo->findById($id);
        $this->_checkExistence($photo);
        $this->_checkPermission(array('aco' => 'photo_view'));
        
        $is_show_full_photo = true;
        $is_redirect = false;
        $this->loadModel("UserBlock");
        $block_users = $this->UserBlock->getBlockedUsers($uid);
		
        if (in_array($photo['Photo']['user_id'],$block_users))
        {
        	$is_show_full_photo = false;
        }
        
        MooCore::getInstance()->setSubject($photo);
        if ($this->request->is('ajax')){
        	$limit = 20;
        }
        else
        {
        	$limit = Configure::read('Photo.photo_item_per_pages');
        }
        $params = array();
        switch ($photo['Photo']['type']) {
            case 'Photo_Album':
                $is_redirect= !$this->_checkPrivacy($photo['Album']['privacy'], $photo['User']['id'],null,false);
              	if ($is_redirect)
              	{
              		 $is_show_full_photo = false;
              	}
                
                $title = $photo['Album']['moo_title'];
                
		        if ($photo['Album']['type'] == 'newsfeed')
		        {
		        	$this->loadModel('Friend');
		        	$params['newsfeed'] = true;
		        	if ($uid == $photo['Album']['user_id'] || $this->_getUserRoleId() == ROLE_ADMIN || ($uid && $this->Friend->areFriends($uid,$photo['Album']['user_id'])))
		        	{
		        		$params['is_friend'] = true;
		        	}
		        }
                if (!$photo['Photo']['album_type'])
                {
                    $photos = $this->Photo->getPhotos('Photo_Album', $photo['Photo']['target_id'], 1, $limit, $params);
                }
                else
                {
                    $cuser = MooCore::getInstance()->getViewer();
                	//check privacy
                    switch ($photo['Photo']['album_type'])
                    {
                        case 'Group_Group':
                            $this->loadModel('Group.GroupUser');
                            $group = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                            if (!$group || ((!$cuser || ($cuser && !$cuser['Role']['is_admin'] )) && $group['Group']['type'] == PRIVACY_PRIVATE && !$this->GroupUser->isMember($uid, $group['Group']['id']))){                                
                               	$is_redirect = true;
                               	$is_show_full_photo = false;
                            }
                            break;
                        case 'Event_Event':
                            $this->loadModel('Event.EventRsvp');
                            $event = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                            if (!$event || ((!$cuser || ($cuser && !$cuser['Role']['is_admin'] )) && $event['Event']['type'] == PRIVACY_PRIVATE && !$this->EventRsvp->getMyRsvp($uid, $event['Event']['id']))){
                                $is_redirect = true;
                               	$is_show_full_photo = false;
                            }
                            break;
                    }
                    $photos = $this->Photo->getFeedPhotos($photo['Photo']['album_type'],$photo['Photo']['album_type_id'], 1, $limit, $params);
                }


                break;

            case 'Group_Group':
            	$group = MooCore::getInstance()->getItemByType($photo['Photo']['type'],$photo['Photo']['target_id']);              
                $title = __( 'Photos of %s', $group['Group']['name']);
                $photos = $this->Photo->getPhotos('Group_Group', $photo['Photo']['target_id'], 1, $limit);
               	$this->set('group',$group);
                
                // check group privacy if it's group photo
        		$cuser = MooCore::getInstance()->getViewer();
                $this->loadModel('Group.GroupUser');
                if ((!$cuser || ($cuser && !$cuser['Role']['is_admin'] )) && $group['Group']['type'] == PRIVACY_PRIVATE && !$this->GroupUser->isMember($uid, $group['Group']['id'])){                    
                    $is_redirect = true;
                    $is_show_full_photo = false;
                }
                
                break;
        }

        $this->_getPhotoDetail($photo);
        
        $this->loadModel('Friend');        
        $friends = $this->Friend->getFriendsList($uid);

        $type = $photo['Photo']['type'];
        $target_id = $photo['Photo']['target_id'];		
        if (!empty($this->request->named['uid'])) {
            $this->loadModel('User');
            $user = $this->User->findById($this->request->named['uid']);
            $this->set('user', $user);

            $this->loadModel('Photo.PhotoTag');
            $photos = $this->PhotoTag->getPhotos($this->request->named['uid']);

            $type = APP_USER;
            $target_id = $this->request->named['uid'];
        }
        
        if (!empty($this->request->query['uid'])) {
        	$this->loadModel('Photo.PhotoTag');
            $photos = $this->PhotoTag->getPhotos($this->request->query['uid']);
        }        
        
        $can_tag = false;
        if ($uid && ( $uid == $photo['User']['id'] || $this->Friend->areFriends($uid, $photo['User']['id']) ))
            $can_tag = true;
            
        if (!empty($this->request->query['uid'])) 
        {
        	$total_photos = count($photos);
        }
        else
        {
        	$total_photos = $this->Photo->getPhotosCount($photo['Photo']['type'], $photo['Photo']['target_id'], $params);
        }
        $this->set('photosAlbumCount', $total_photos);
        $this->set('page', 1);
        
        if (!empty($this->request->query['uid'])) 
        {
        	$all_photos = $photos;
        }
        else
        {
        	$all_photos = $this->Photo->getAllPhotos($photo['Photo']['type'], $photo['Photo']['target_id'], $params);
        }
        $photo_position = $this->findPositionItem($photo, $all_photos);
        $this->set('is_show_full_photo',$is_show_full_photo);
        $this->set('is_redirect',$is_redirect);
        $this->set('page', 1);
        $this->set(compact('can_tag','photo','photos', 'friends', 'total_photos', 'photo_position'));        
        $this->render('/Elements/ajax/photo_detail_theater');
        
    }
    
    public function ajax_thumb_theater($id = null , $page = null,$isRedirect = true)
    {
    	$uid = $this->Auth->user('id');
        $id = intval($id);
        $photo = $this->Photo->findById($id);
        $this->_checkExistence($photo);
        $this->_checkPermission(array('aco' => 'photo_view'));
        $limit = 20;

        $params = array();        
        if (!isset($this->request->query['uid']))
        {
	        switch ($photo['Photo']['type']) {
	            case 'Photo_Album':
	                $this->_checkPrivacy($photo['Album']['privacy'], $photo['User']['id']);
	                
			        if ($photo['Album']['type'] == 'newsfeed')
			        {
			        	$this->loadModel('Friend');
			        	$params['newsfeed'] = true;
			        	if ($uid == $photo['Album']['user_id'] || $this->_getUserRoleId() == ROLE_ADMIN || ($uid && $this->Friend->areFriends($uid,$photo['Album']['user_id'])))
			        	{
			        		$params['is_friend'] = true;
			        	}
			        }
	                
	                $photos = $this->Photo->getPhotos('Photo_Album', $photo['Photo']['target_id'], $page, $limit, $params);
	
	                break;
	
	            case 'Group_Group':
	                $photos = $this->Photo->getPhotos('Group_Group', $photo['Photo']['target_id'], $page, $limit);
	                break;
	        }
        }
        else
        {
        	$this->loadModel("Photo.PhotoTag");
        	$photos = $this->PhotoTag->getPhotos($this->request->query['uid'], $page,$limit);
        	$this->set('photosAlbumCount', $this->PhotoTag->getPhotosCount($this->request->query['uid']));
        	$this->set('page', $page);
        }
        $this->set('photos',$photos);  
        if($isRedirect) {
            $this->render('/Elements/theater/photo_thumbs');   
        }
    }

    private function _getPhotoDetail($photo, $mode = null) {
        $uid = $this->Auth->user('id');
        $tag_uid = 0;

        if (!empty($this->request->named['uid'])) { // tagged photos
            $this->loadModel('Photo.PhotoTag');
            $photo_tag = $this->PhotoTag->find('first', array('conditions' => array('photo_id' => $photo['Photo']['id'],
                    'PhotoTag.user_id' => $this->request->named['uid'])
            ));

            $photo_path = 'uploads'. DS . 'photos' . DS . 'thumbnail' . DS .$photo_tag['PhotoTag']['photo_id'] . DS . $photo['Photo']['thumbnail'] ;
            App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));

            $thumb = PhpThumbFactory::create(WWW_ROOT . DS . $photo_path, array('jpegQuality' => 100));
            $image_real_dimension = $thumb->getCurrentDimensions();

            $neighbors = array();
            
            if ($photo_tag){
                $neighbors = $this->PhotoTag->find('neighbors', array('field' => 'id',
                    'value' => $photo_tag['PhotoTag']['id'],
                    'conditions' => array('PhotoTag.user_id' => $this->request->named['uid']
                )));

                $tag_uid = $this->request->named['uid'];
            }
            
        } if (!empty($this->request->query['uid'])) { // tagged photos
        	$this->loadModel('Photo.PhotoTag');
            $photo_tag = $this->PhotoTag->find('first', array('conditions' => array('photo_id' => $photo['Photo']['id'],
                   'PhotoTag.user_id' => $this->request->query['uid'])
            ));
            
        	$neighbors = array();
            
            if ($photo_tag){
                $neighbors = $this->PhotoTag->find('neighbors', array('field' => 'id',
                    'value' => $photo_tag['PhotoTag']['id'],
                    'conditions' => array('PhotoTag.user_id' => $this->request->query['uid']
                )));
            }
            
        }else {
        	if (!$photo['Photo']['album_type'])
			{
				$neighbors = $this->Photo->find('neighbors', array('field' => 'id',
					'value' => $photo['Photo']['id'],
					'conditions' => $this->Photo->addBlockCondition(array('Photo.type' => $photo['Photo']['type'],
						'target_id' => $photo['Photo']['target_id']
					))));
			}
			else
			{
				$neighbors = $this->Photo->find('neighbors', array('field' => 'id',
					'value' => $photo['Photo']['id'],
					'conditions' => $this->Photo->addBlockCondition(array('Photo.album_type' => $photo['Photo']['album_type'],
						'Photo.album_type_id' => $photo['Photo']['album_type_id']
					))));
			}
        }

        $this->loadModel('Comment');
        $this->loadModel('Like');

        $comments = $this->Comment->getComments($photo['Photo']['id'], 'Photo_Photo');
        
        $comment_count = $photo['Photo']['comment_count'];
        $comment_likes = array();

        // get comment likes
        if (!empty($uid)) {
            $comment_likes = $this->Like->getCommentLikes($comments, $uid);            

            $like = $this->Like->getUserLike($photo['Photo']['id'], $uid, 'Photo_Photo');
            $this->set('like', $like);
        }
        
        $this->set('comment_likes', $comment_likes);

        $likes = $this->Like->getLikes($photo['Photo']['id'], 'Photo_Photo');
        $dislikes = $this->Like->getDisLikes($photo['Photo']['id'], 'Photo_Photo');

        $this->loadModel('Photo.PhotoTag');
        $photo_tags = $this->PhotoTag->findAllByPhotoId($photo['Photo']['id']);
        // check to see if user can delete photo
        $admins = array($photo['Photo']['user_id']);

        if ($photo['Photo']['type'] == 'Group_Group') { // if it's a group photo, add group admins to the admins array
            // get group admins
            $this->loadModel('Group.GroupUser');

            $is_member = $this->GroupUser->isMember($uid, $photo['Photo']['target_id']);
            $this->set('is_member', $is_member);

            $group_admins = $this->GroupUser->getUsersList($photo['Photo']['target_id'], GROUP_USER_ADMIN);
            $admins = array_merge($admins, $group_admins);
        }

        if ($photo['Photo']['album_type'])
        {
            switch ($photo['Photo']['album_type'])
            {
                case 'Group_Group':
                    $this->loadModel('Group.GroupUser');
                    $group_admins = $this->GroupUser->getUsersList($photo['Photo']['album_type_id'], GROUP_USER_ADMIN);
                    $admins = array_merge($admins, $group_admins);

                    break;
                case 'Event_Event':
                    $this->loadModel('Event.EventRsvp');
                    $event = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                    $admins[] = $event['Event']['user_id'];
                    break;
            }
        }

        $this->set('likes', $likes);
        $this->set('dislikes', $dislikes);
        $this->set('photo_tags', $photo_tags);
        
        $this->set('neighbors', $neighbors);
        $this->set('admins', $admins);
        $this->set('tag_uid', $tag_uid);

        
        $page = 1;
        $data['bIsCommentloadMore'] = $comment_count - $page * RESULTS_LIMIT;
        $data['more_comments'] = '/comments/browse/photo_photo/' . $photo['Photo']['id'] . '/page:' . ($page + 1);
        //$data['admins'] = $admins;
        $data['comments'] = $comments;
        $this->set('data', $data);

        if($this->request->is('ajax')){
            //close comment
            $closeCommentModel =  MooCore::getInstance()->getModel('CloseComment');
            $item_close_comment = $closeCommentModel->getCloseComment($photo['Photo']['id'], 'Photo_Photo');

            if(!empty($item_close_comment)){
                $is_close_comment = 1;
                $title =  __('Open Comment');
            }else{
                $is_close_comment = 0;
                $title =   __('Close Comment');
            }
            $this->set('is_close_comment',$is_close_comment);
            $this->set('item_close_comment',$item_close_comment);
            $this->set('title',$title);
        }
    }

    public function ajax_upload($type = null, $target_id = null) {
        $target_id = intval($target_id);
        $this->_checkPermission(array('aco' => 'photo_upload'));
        $this->set('target_id', $target_id);
        $this->set('type', $type);
        $this->set('title_for_layout', __( 'Upload Photos'));
    }

    public function do_activity($type, $redirect = true) {
        $this->_checkPermission();
        $uid = $this->Auth->user('id');

        if (!empty($this->request->data['new_photos'])) {
            $new_photos = explode(',', $this->request->data['new_photos']);
            
            $this->loadModel('Activity');
            $this->loadModel('Photo.Album');
            $photoList = explode(',', $this->request->data['new_photos']);

            $this->loadModel('Photo.Photo');
            $this->request->data['type'] = $type;
            $this->request->data['user_id'] = $uid;
            $photoId = array();
            foreach ($photoList as $photoItem){
                if(!empty($photoItem))
                {
                    $this->request->data['thumbnail'] = $photoItem;
                    $this->Photo->create();

                    $this->Photo->set($this->request->data);
                    $this->Photo->save();
                    array_push($photoId, $this->Photo->id);
                }
            }
            switch ($type) {
                case 'Photo_Album':
                    $album = $this->Album->findById($this->request->data['target_id']);
                    $url = '/albums/edit/' . $this->request->data['target_id'];
                    $activity = $this->Activity->getItemActivity('Photo_Album', $this->request->data['target_id']);

                    if (!empty($activity)) { // update the existing one
                        $this->Activity->id = $activity['Activity']['id'];
                        $this->Activity->save(array('items' => join(',', $photoId), 'privacy' => $album['Album']['privacy']));
                    } else {// insert new
                        $this->Activity->save(array('type' => APP_USER,
                            'action' => 'photos_add',
                            'user_id' => $uid,
                            'items' => join(',', $photoId),
                            'item_type' => 'Photo_Album',
                            'item_id' => $this->request->data['target_id'],
                            'privacy' => $album['Album']['privacy'],
                            'query' => 1,
                            'params' => 'item',
                            'plugin' => 'Photo'
                        ));

                        $event = new CakeEvent('Plugin.Controller.Photo.afterSavePhoto', $this, array(
                            'uid' => $uid,
                            'activity_id' =>  $this->Activity->id,
                        ));
                        $this->getEventManager()->dispatch($event);
                    }
                    
                    // update privacy photo album
                    $this->Photo->updateAll(array('Photo.privacy' => $album['Album']['privacy']), array('Photo.id' => $photoId));
                    
                    $event = new CakeEvent('Plugin.Controller.Album.afterSaveAlbum', $this, array(
                        'uid' => $uid, 
                        'id' => $album['Album']['id'], 
                        'privacy' => $album['Album']['privacy']
                    ));

                    $this->getEventManager()->dispatch($event);

                    break;

                default:                    
                    $privacy = PRIVACY_EVERYONE;
                    list($plugin, $name) = mooPluginSplit($type);
                    
                    $item = MooCore::getInstance()->getItemByType($type,$this->request->data['target_id']);                    
                    $url = $item[$name]['moo_url'];
                    
                    if ($item[$name]['type'] == PRIVACY_PRIVATE || $item[$name]['type'] == PRIVACY_RESTRICTED)
                        $privacy = PRIVACY_ME;

                    $share = 0;
                    if ($privacy == PRIVACY_EVERYONE){
                        $share = 1;
                    }

                    $this->Activity->save(array(
                        'type' => $type,
                        'target_id' => $this->request->data['target_id'],
                        'action' => 'photos_add',
                        'user_id' => $uid,
                        'items' => join(',', $photoId),
                        'item_type' => 'Photo_Photo',
                        'privacy' => $privacy,
                        'query' => 1,
                    	'plugin' => 'Photo',
                        'share' => $share
                    ));

                    $event = new CakeEvent('Plugin.Controller.Photo.afterSaveItemPhoto', $this, array(
                        'uid' => $uid,
                        'activity' =>  $this->Activity->read(),
                        'type' =>  $type,
                    ));
                    $this->getEventManager()->dispatch($event);
                    
                    if ($this->isApp() && $redirect)
                    {
                    	$this->Session->setFlash(__('Photos has been successfully uploaded'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
                    	$this->redirect('/photos/upload_done');
                    	return;
                    }
                    break;
            }
        }

        if($redirect)
        {        	
            $this->redirect($url);
        }
        return true;
    }
    
    public function upload_done()
    {
    	
    }

    public function ajax_tag($isRedirect = true) {
        $this->autoRender = false;
        $this->_checkPermission(array('confirm' => true));

        $uid = $this->Auth->user('id');
        $this->loadModel('Photo.PhotoTag');
        $this->loadModel('Photo.Photo');

        $user_id = $this->request->data['uid'];
        $photo_id = $this->request->data['photo_id'];

        $photo = $this->Photo->findById($photo_id);
        // if tagging a member then check if that member is already tagged in this photo
        if (!empty($user_id))
            $tag = $this->PhotoTag->find('first', array('conditions' => array('photo_id' => $photo_id, 'PhotoTag.user_id' => $user_id)));
        
        if (empty($tag)) {
            $this->PhotoTag->save(array('photo_id' => $photo_id,
                    'user_id' => $user_id,
                    'tagger_id' => $uid,
                    'value' => $this->request->data['value'],
                    'style' => $this->request->data['style']
                ));

            if ($user_id) {
                // insert into activity
                $this->loadModel('Activity');
                $activity = $this->Activity->getRecentActivity('photos_tag', $user_id);

                if (!empty($activity)) {
                    $photo_ids = explode(',', $activity['Activity']['items']);
                    $photo_ids[] = $photo_id;

                    $this->Activity->id = $activity['Activity']['id'];
                    $this->Activity->save(array('items' => implode(',', $photo_ids)
                    ));
                } else {
                    $this->Activity->save(array('type' => APP_USER,
                        'action' => 'photos_tag',
                        'user_id' => $user_id,
                        'item_type' => 'Photo_Photo',
                        'items' => $photo_id,
                        'query' => 1,
                        'params' => 'no-comments',
                    	'plugin' => 'Photo',
                        'privacy' => $photo['Photo']['moo_privacy']
                    ));
                }

                if ($user_id != $uid) {
                    // add notification
                    $this->loadModel("User");
                    $this->loadModel('UserBlock');
        			$blocks = $this->UserBlock->getBlockedUsers($photo['Photo']['user_id']);
        			
        			if (!in_array($user_id, $blocks))
        			{
	                    if ($this->User->checkSettingNotification($user_id,'tag_photo')) {
	                        $this->loadModel('Notification');
	                        $this->Notification->record(array('recipients' => $user_id,
	                            'sender_id' => $uid,
	                            'action' => 'photo_tag',
	                            'url' => '/photos/view/' . $photo_id . '#content'
	                        ));
	                    }
        			}
                }

                //activitylog event
                $cakeEvent = new CakeEvent('Controller.Photo.afterTagPhoto', $this, array('uid' => $uid, 'user_id' => $user_id, 'photo_id' => $photo_id));
                $this->getEventManager()->dispatch($cakeEvent);
            }
            if($isRedirect) {
                $response['result'] = 1;
                $response['id'] = $this->PhotoTag->id;
            }
            else {
                return $this->PhotoTag->id;
            }
        } else {
            if($isRedirect) {
                $response['result'] = 0;
                $response['message'] = __( 'Duplicated tag!');
            }
            else {
                return $error = array(
                        'code' => 400,
                        'message' => __("Duplicated tag!"),
                    );
            }
        }

        if($isRedirect) echo json_encode($response);
    }

    public function ajax_remove_tag($isRedirect = true) {
        $this->autoRender = false;
        $this->_checkPermission(array('confirm' => true));
        $uid = $this->Auth->user('id');

        $this->loadModel('Photo.PhotoTag');
        $tag = $this->PhotoTag->findById($this->request->data['tag_id']);
        if (!$tag){
            if($isRedirect) {
                return;
            }
            else {
                return $error = array(
                    'code' => 404,
                    'message' => __('Not tagged yet.'),
                );
            }
        }
        
        
        // tagger, user was tagged and photo author can delete tag
        $admins = array($tag['PhotoTag']['user_id'], $tag['PhotoTag']['tagger_id'], $tag['Photo']['user_id']);

        $this->_checkPermission(array('admins' => $admins));
        $this->PhotoTag->delete($this->request->data['tag_id']);
                
        $this->loadModel('Activity');             
        $activity = $this->Activity->getRecentActivity('photos_tag', $tag['PhotoTag']['tagger_id']);

        if ($activity) {
            $items = array_filter(explode(',',$activity['Activity']['items']));
        	$items = array_diff($items,array($tag['PhotoTag']['photo_id']));
        	
        	if (!count($items))
        	{
        		$this->Activity->delete($activity['Activity']['id']);
        	}
        	else
        	{
        		$this->Activity->id = $activity['Activity']['id'];
                $this->Activity->save(
                    array('items' => implode(',',$items))                        
                );
        	}
        }

        //activitylog event
        $cakeEvent = new CakeEvent('Controller.Photo.afterRemoveTagPhoto', $this, array('uid' => $uid, 'user_id' =>  $tag['PhotoTag']['user_id'], 'photo_id' => $tag['PhotoTag']['photo_id']));
        $this->getEventManager()->dispatch($cakeEvent);
    }

    public function ajax_fetch($isReturn = true) {
        $limit = Configure::read('Photo.photo_item_per_pages');
        if (!$this->data['taguserid'])
        {
	        switch ($this->data['type']) {
	            case 'Photo_Album':
	                // check the privacy of album
	                $this->loadModel('Photo.Album');
	                $album = $this->Album->findById($this->data['target_id']);
	
	                $uid = $this->Auth->user('id');
	                /*if ($album)
	                    $this->_checkPrivacy($album['Album']['privacy'], $album['User']['id']);*/
	
	                if ($this->data['album_type'])
	                {
	                    $photos = $this->Photo->getFeedPhotos($this->data['album_type'], $this->data['album_type_id'], $this->data['page'], $limit);
	                }
	                else
	                {
	                    $photos = $this->Photo->getPhotos('Photo_Album', $this->data['target_id'], $this->data['page'], $limit);
	                }
	
	                break;
	
	            case 'Group_Group':
	                // @todo: check the type of group
	                $photos = $this->Photo->getPhotos('Group_Group', $this->data['target_id'], $this->data['page'], $limit);
	
	                break;
	
	            case APP_USER:
	                $this->loadModel('Photo.PhotoTag');
	                $photos = $this->PhotoTag->getPhotos($this->data['target_id'], $this->data['page']);
	
	                break;
	        }
	        if ($this->data['album_type'])
	        {
	            $this->set('photosAlbumCount', $this->Photo->getFeedPhotosCount($this->data['album_type'], $this->data['album_type_id']));
	        }
	        else
	        {
	            $this->set('photosAlbumCount', $this->Photo->getPhotosCount('Photo_Album', $this->data['target_id']));
	        }
        }
        else
        {
        	 $photos = $this->PhotoTag->getPhotos($this->data['taguserid'], $this->data['page']);
        	 $this->set('photosAlbumCount', $this->PhotoTag->getPhotosCount($this->data['taguserid']));
        }
        
        $this->set('page', $this->data['page']);
        $this->set('photos', $photos);
        if($isReturn) $this->render('/Elements/ajax/photo_thumbs');
    }

    public function ajax_friends_list() {
        $this->_checkPermission();
        $uid = $this->Auth->user('id');

        $this->loadModel('Friend');
        $friends = $this->Friend->getFriendsList($uid);

        $this->set('friends', $friends);
        $this->render('/Elements/misc/photo_friends_list');
    }

    public function ajax_remove($isRedirect = true) {
        if($isRedirect) {
            $photoId = intval($this->request->params['named']['photo_id']);
        }
        else {
            $photoId = $this->request->params['photo_id'];
        }

        $this->autoRender = false;
        $this->_checkPermission(array('confirm' => true));

        $photo = $this->Photo->findById($photoId);
        
        if (!$photo){
            if($isRedirect) {
                throw new NotFoundException();
            }
            else {
                return $error = array(
                        'code' => 404,
                        'message' => __('Photo not found'),
                );
            }
        }
        
        $admins = array($photo['Photo']['user_id']);

        if ($photo['Photo']['type'] == 'Group_Group') { // if it's a group photo, add group admins to the admins array
            // get group admins
            $this->loadModel('Group.GroupUser');

            $group_admins = $this->GroupUser->getUsersList($photo['Photo']['target_id'], GROUP_USER_ADMIN);
            $admins = array_merge($admins, $group_admins);
        }

        if ($photo['Photo']['album_type'])
        {
            switch ($photo['Photo']['album_type'])
            {
                case 'Group_Group':
                    $this->loadModel('Group.GroupUser');
                    $group_admins = $this->GroupUser->getUsersList($photo['Photo']['album_type_id'], GROUP_USER_ADMIN);
                    $admins = array_merge($admins, $group_admins);

                    break;
                case 'Event_Event':
                    $event = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                    if ($event)
                        $admins[] = $event['Event']['user_id'];
                    break;
            }
        }

        // make sure user can delete photo
        $this->_checkPermission(array('admins' => $admins));
        
        // permission ok, delete photo now
        $this->Photo->delete($photo['Photo']['id']);
        
        // delete activity comment_add_photo
        $activityModel = MooCore::getInstance()->getModel('Activity');
        $activityModel->deleteAll(array('Activity.item_type' => 'Photo_Photo', 'Activity.action' => 'comment_add_photo', 'Activity.item_id' => $photo['Photo']['id']));
        
        // delete activity photos_tag
        $activityModel->deleteAll(array('Activity.item_type' => 'Photo_Photo', 'Activity.action' => 'photos_tag', 'Activity.items' => $photo['Photo']['id']));
        
        $cakeEvent = new CakeEvent('Plugin.Controller.Group.afterDeletePhoto', $this, array('item' => $photo));
        $this->getEventManager()->dispatch($cakeEvent);

        if (!$photo['Photo']['album_type'])
        {
            // update cover of album
            $nextCoverPhoto = $this->Photo->find('first', array('conditions' => array('Photo.type' => 'Photo_Album', 'Photo.target_id' => $photo['Photo']['target_id'])));
            $currentCoverPhoto = $this->Album->find('first', array('conditions' => array('Album.id' => $photo['Photo']['target_id'])));

            if (!empty($nextCoverPhoto)) {
                // cond1: delete item is cover => need to update cover
                // cond2: current album have no cover => need to update cover
                if ($photo['Photo']['thumbnail'] == $currentCoverPhoto['Album']['cover'] || empty($currentCoverPhoto['Album']['cover'])) {
                    $this->Album->id = $photo['Photo']['target_id'];
                    $this->Album->save(array(
                        'cover' => $nextCoverPhoto['Photo']['thumbnail']
                    ));
                }

            } else {
                $this->Album->id = $photo['Photo']['target_id'];
                $this->Album->save(array(
                    'cover' => ''
                ));
            }
            if($isRedirect) {
                if ($this->request->params['named']['next_photo'] == 0) {
                    if ($photo['Photo']['type'] == 'group') {
                        $this->redirect('/groups/view/' . $photo['Photo']['target_id'] . '/tab:photos');
                    } else {
                        $this->Session->setFlash(__('Photos has been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
                        $this->redirect(array('controller' => 'photos', 'action' => 'index'));
                    }
                }

                $this->redirect(array('controller' => 'photos', 'action' => 'view', $this->request->params['named']['next_photo']));
            }
        }
        else
        {
            switch ($photo['Photo']['album_type'])
            {
                case 'Group_Group':
                    $group = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                    if($isRedirect) {
                        $this->redirect($group['Group']['moo_url']);
                    }
                    break;
                case 'Event_Event':
                    $event = MooCore::getInstance()->getItemByType($photo['Photo']['album_type'],$photo['Photo']['album_type_id']);
                    if($isRedirect) {
                        $this->redirect($event['Event']['moo_url']);
                    }
                    break;
            }
        }
    }
    
    public function categories_list($isRedirect = true) {
        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();
        $categories = $this->Category->getCategories('Photo', $role_id);
        if ($this->request->is('requested')) {
                return $categories;
            }
        if($isRedirect && $this->theme == "mooApp") {
                $this->render('/Elements/lists/categories_list');
        }
    }

    public function ajax_rotate($id = null, $mode = null,$isRedirect = true){
        if($isRedirect) $id = $this->data['id'];
        $id = intval($id);
        
        $mode = $this->data['mode'];
        
        if(empty($id) || empty($mode)){
            return;
        }
        
        if($mode == 'left'){
            $mode = 90;
        }else{
            $mode = -90;
        }
        
        $photo = Cache::read('photo.photo_view_'.$id, 'photo');
        if(empty($photo)){
            $photo = $this->Photo->findById($id);
            Cache::write('photo.photo_view_'.$id, $photo, 'photo');
        }
        
        $this->_checkExistence($photo);
        
        if (!$photo && $isRedirect){
            return;
        }
        
        $this->_checkPermission(array('aco' => 'photo_view'));

        $uid = $this->Auth->user('id');
       
        $year = date('Y', strtotime($photo['Photo']['created']));
        $month = date('m', strtotime($photo['Photo']['created']));
        $day = date('d', strtotime($photo['Photo']['created']));
                    
        $photoConfig = Configure::read('core.photo_image_sizes');
        $photoSizes = explode('|', $photoConfig);
        
        $photo_path = 'uploads'. DS . 'photos' . DS . 'thumbnail' . DS . $year . DS . $month . DS . $day . DS .$photo['Photo']['id'] . DS;
        App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));
        
        if (!is_array($photoSizes)) {
            if($isRedirect) return false;
            else {
                return $error = array(
                        'code' => 400,
                        'message' => __("Image Size error"),
                    );
            }
        }

        // Hacking for cdn

        $originFile = $photo_path . $photo['Photo']['thumbnail'];
        $view = new View($this);
        if(Configure::read('Storage.storage_current_type') != "local"){

            if(!file_exists($originFile)){
                $url = $view->Moo->getImageUrl($photo);
                file_put_contents($originFile, fopen($url, 'r'));
                $fileInfo = pathinfo($originFile);
                $extension = $fileInfo['extension'];
                $photo = $this->_createThumbnail($originFile,$photo_path."tmp_".$photo['Photo']['thumbnail'],"new_".uniqid().".$extension",$photo );
            }


        }

        if(file_exists($originFile)){
            $isCreateThumb = false;
            foreach ($photoSizes as $key => $size) {
                $destFile = $photo_path . $size . '_' . $photo['Photo']['thumbnail'];
                if(!file_exists($destFile)){
                    $isCreateThumb = true;
                }
            }

            if($isCreateThumb){
                $fileInfo = pathinfo($originFile);
                $extension = $fileInfo['extension'];
                $photo = $this->_createThumbnail($originFile,$photo_path."tmp_".$photo['Photo']['thumbnail'],"new_".uniqid().".$extension",$photo );
            }
        }
        // End hacking for cdn
        foreach ($photoSizes as $key => $size) {
            $destFile = $photo_path . $size . '_' . $photo['Photo']['thumbnail'];
            if(file_exists($destFile)){
                $photo_clone = PhpThumbFactory::create($destFile);
                $photo_clone->rotateImageNDegrees($mode)->save($destFile);
            }

        }

        $this->Photo->clear();
        $data = array('id' => $photo['Photo']['id'], 'thumbnail' => $photo['Photo']['thumbnail']);
        $this->Photo->id=$photo['Photo']['id'];
        $this->Photo->saveField('thumbnail',$photo['Photo']['thumbnail']);
        $data = array();
        foreach ($photoSizes as $key => $size) {
            $data[$size] = $view->Moo->getImageUrl($photo,array('prefix' => $size)).'?'.rand(1,9999);
        }
        $this->set('data', $data);
        //exit();
    }
    private function _createThumbnail($srcFile,$desFile,$name,$photo){
        $file = new File($srcFile);
        $file->copy($desFile);
        $fileInfo = $file->info();
        $file->close();

        $tmpFile = new File($desFile);
        $this->Photo->clear();
        $id = $photo['Photo']['id'];
        $this->Photo->id = $id;
        $this->Photo->save(array('thumbnail'=>
            array(
                'name'=>$name,
                'deleteOnUpdate'=>false,
                'type'=> $fileInfo['mime'],
                'tmp_name'=>$tmpFile->pwd(),
            )
        ));
        $tmpFile->close();


        $newPhoto = $this->Photo->findById($id);
        //Cache::delete('photo.photo_view_'.$id,'photo');
        Cache::write('photo.photo_view_'.$id, $newPhoto, 'photo');

        // Fixing the missing album cover
        $album = $this->Album->findById($photo['Photo']['target_id']);
        if($album){
            if($album['Album']['cover'] == $photo['Photo']['thumbnail']){
                $this->Album->clear();
                $this->Album->id = $album['Album']['id'];
                $this->Album->save(array('cover'=>$newPhoto['Photo']['thumbnail']));
            }
        }
        return $newPhoto;
    }
    
    public function ajax_update_size()
    {
    	$photo_id = isset($this->request->data['photo_id']) ? $this->request->data['photo_id'] : 0;
    	$width = isset($this->request->data['width']) ? $this->request->data['width'] : 0;
    	$height= isset($this->request->data['height']) ? $this->request->data['height'] : 0;
    	
    	$photo = $this->Photo->findById($photo_id);
    	
    	if (!$photo || $photo['Photo']['size'])
    	{
    		die();
    	}
    	
    	if (!$width || !$height)    		
    		die();
    	
    	$this->Photo->id = $photo_id;
    	$this->Photo->save(array('size'=>$width.','.$height));
    	die();
    }
}
