<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class VideosController extends VideoAppController
{
    
    public $paginate = array(
            'limit' => RESULTS_LIMIT,
             'findType' => 'translated',
            ); 
    public function beforeFilter(){
        parent::beforeFilter();
        $this->loadModel('Video.Video');
    }
	public function index($cat_id = null)
	{
        $cakeEvent = new CakeEvent('Plugin.Controller.Video.index', $this);
        $this->getEventManager()->dispatch($cakeEvent);

        $role_id = $this->_getUserRoleId();
        $more_result = 0;
        
        if ( !empty( $cat_id ) ){
            $videos  = $this->Video->getVideos('category', $cat_id);
            $more_videos = $this->Video->getVideos('category', $cat_id,2);
        }else{
            $videos  = $this->Video->getVideos(null,$this->Auth->user('id'),1,RESULTS_LIMIT,$cakeEvent->result['friends_list'],$role_id);
            $more_videos  = $this->Video->getVideos(null,$this->Auth->user('id'),2,RESULTS_LIMIT,$cakeEvent->result['friends_list'],$role_id);
        }
        
        if(!empty($more_videos)){
            $more_result = 1;
        }
        
        $this->set('more_result',$more_result);
		$this->set('videos', $videos);
        $this->set('cat_id', $cat_id);
		$this->set('title_for_layout', '');
	}
        
        public function profile_user_video($uid = null,$isRedirect=true) {
            $uid = intval($uid);
            if($isRedirect) {
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            }
            else {
                    $page = $this->request->query('page') ? $this->request->query('page') : 1;
            }

            $role_id = $this->_getUserRoleId();

            $this->loadModel('Friend');
            $are_friend = 0;
            if($this->Friend->areFriends($this->Auth->user('id'), $uid))
                $are_friend = 1;
            if($this->Auth->user('id') == $uid)
                $role_id = ROLE_ADMIN; //viewer view his own videos
            if(!empty($more_videos))
                $more_result = 1;

            $videos = $this->Video->getVideos('user', $uid, $page,RESULTS_LIMIT,$are_friend,$role_id);
            $more_videos = $this->Video->getVideos('user', $uid, $page + 1,RESULTS_LIMIT,$are_friend,$role_id);
            $this->set('videos', $videos);
            $this->set('more_url', '/videos/profile_user_video/' . $uid . '/page:' . ( $page + 1 ));
            $this->set('user_id', $uid);
            $this->set('more_result',$more_videos);

            if($isRedirect && $this->theme != "mooApp") {
                if ($page > 1)
                    $this->render('/Elements/lists/videos_list');
                else
                    $this->render('Video.Videos/profile_user_video');
            }
        }

    /********************
	* Ajax Add New Video
	********************/
	public function save($isReturn = true)
	{
            $this->_checkPermission( array( 'confirm' => true ) );
            $this->autoRender = false;	
            $uid = $this->Auth->user('id');

            if ( !empty($this->request->data['id']) ) // edit video
            {			
                // check edit permission			
                $video = $this->Video->findById( $this->request->data['id'] );
                $admins = array( $video['User']['id'] ); // video creator
                
                $this->loadModel('Group.GroupUser');
                $group_admins = $this->GroupUser->getUsersList($video['Video']['group_id'], GROUP_USER_ADMIN);
                $admins = array_unique(array_merge($admins, $group_admins));
                
                 // if it's a group video, add group admins to the admins array for permission checking
                $cakeEvent = new CakeEvent('Plugin.Controller.Video.edit', $this, array('video' => $video, 'admins' => $admins));
                $this->getEventManager()->dispatch($cakeEvent);
                if(!empty($cakeEvent->result['admins']))
                    $admins = $cakeEvent->result['admins'];

                $this->_checkPermission( array( 'admins' => $admins ) );
                $this->Video->id = $this->request->data['id'];
            }
            else
            {
                // if it's a group video, check if user has permission to create video in this group
                $cakeEvent = new CakeEvent('Plugin.Controller.Video.beforeSave', $this, array('uid' => $uid));
                $this->getEventManager()->dispatch($cakeEvent);
                if(!empty($cakeEvent->result['notMember'])) {
                    if(!$isReturn) {
                        return;
                    }
                    else {
                        $this->throwErrorCodeException('not_group_member');
                        return $error = array(
                            'code' => 400,
                            'message' => __("You are not member of this group."),
                        );
                    }
                }
                if(!empty($cakeEvent->result['privacy']))
                {
                    $privacy = $cakeEvent->result['privacy'];
                    if($cakeEvent->result['privacy'] == 3)
                        $privacy = 1;
                    $this->request->data['privacy'] = $privacy;
                }
                $this->request->data['user_id'] = $uid;
            }

            $this->Video->set( $this->request->data );
            $this->_validateData( $this->Video );

            if ( $this->Video->save() ) // successfully saved	
            {
                if ( empty($this->request->data['id']) ) // add video
                {
                    $cakeEvent = new CakeEvent('Plugin.Controller.Video.afterAdd', $this, array('privacy' => $this->request->data['privacy'], 'uid' => $uid, 'video_id' => $this->Video->id));
                    $this->getEventManager()->dispatch($cakeEvent);

                }
                $cakeEvent = new CakeEvent('Plugin.Controller.Video.afterSave', $this, array(
                    'id' => $this->Video->id,
                    'uid' => $uid,
                    'privacy' => isset($this->request->data['privacy']) ? $this->request->data['privacy'] : PRIVACY_PUBLIC
                        ));
                $this->getEventManager()->dispatch($cakeEvent);
                if($isReturn) {
                    $response['result'] = 1;
                    $response['id'] = $this->Video->id;
                    echo json_encode($response);
                }
                else {
                    return $this->Video->id;
                }
            }
	}

	/*
	 * Browse videos based on $type
	 * @param string $type - possible value: all (default), my, home friends, user, search
	 * @param mixed $param - could be uid (user) or a query string (search)
	 */
	public function browse($type = null, $param = null,$isRedirect = true) {
                if($isRedirect) {
                    $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
                }
                else {
                    $page = $this->request->query('page') ? $this->request->query('page') : 1;
                }
                $uid = $this->Auth->user('id');
                $role_id = $this->_getUserRoleId();

                if (!empty($this->request->named['category_id'])) {
                    $type = 'category';
                    $param = $this->request->named['category_id'];
                }
                $this->set('title_for_layout', __( 'Videos'));
                $url = (!empty($param) ) ? $type . '/' . $param : $type;
                //$data = '';
                $sFriendsList = '';
                switch ($type) {
                    case 'home':
                    case 'my':
                    case 'friends':
                        $this->_checkPermission();
                        $param = $uid;
                        break;

                    case 'search':
                        $param = urldecode($param);

                        if (!Configure::read('core.guest_search') && empty($uid))
                            $this->_checkPermission();

                        break;

                    case 'group':
                        // check permission if group is private
                        $this->loadModel('Group.Group');
                        $group = $this->Group->findById($param);

                        $this->loadModel('Group.GroupUser');
                        $is_member = $this->GroupUser->isMember($uid, $param);

                        if ($group['Group']['type'] == PRIVACY_PRIVATE) {
                            $cuser = $this->_getUser();

                            if (!$cuser['Role']['is_admin'] && !$is_member)
                            {
                                if($isRedirect) {
                                    $this->autoRender = false;
                                    echo 'Only group members can view videos';
                                    return;
                                }
                                else {
                                    $this->throwErrorCodeException('not_group_member');
                                    return $error = array(
                                        'code' => 400,
                                        'message' => __('Only group members can view videos'),
                                    );
                                }
                            }
                        }

                        $this->set('is_member', $is_member);
                        $this->set('ajax_view', true);
                        $this->set('group_id', $param);
                        $this->set('groupname', $group['Group']['name']);

                        //$data['is_member'] = $is_member;
                        //$data['ajax_view'] = true;
                        //$data['groupname'] = $group['Group']['name'];
                        break;
                    default:
                        $this->loadModel('Friend');
                        $friends_list = $this->Friend->getFriendsList($uid);
                        $aFriendListId = array_keys($friends_list);
                        $sFriendsList = implode(',', $aFriendListId);
                        if ($type != 'category'){
                            $param = $uid;
                        }
                }
                $more_result = 0;
                $videos = $this->Video->getVideos($type, $param, $page, RESULTS_LIMIT, $sFriendsList, $role_id);
                $more_videos = $this->Video->getVideos($type, $param, $page + 1, RESULTS_LIMIT, $sFriendsList, $role_id);

                if(!empty($more_videos)){
                    $more_result = 1;
                }

                $this->loadModel('Group.GroupUser');
                foreach ($videos as $key => $video){
                    $admins = $this->GroupUser->getUsersList($video['Video']['group_id'], GROUP_USER_ADMIN);
                    $videos[$key]['Video']['admins'] = $admins;
                } 

                $this->set('videos', $videos);
                $this->set('more_url', '/videos/browse/' . h($url) . '/page:' . ( $page + 1 ));
                $this->set('more_result', $more_result);

                // MOOSOCIAL-2214
                if ($type == 'group'){
                    $this->set('type', 'Group_Group');
                }
                
            if($isRedirect && $this->theme != "mooApp") {
                if ($page == 1 && $type == 'home'){
                    $this->render('/Elements/ajax/home_video');
                }
                elseif ($page == 1 && $type == 'group'){
                    $this->render('/Elements/ajax/group_video');
                }
                else {
                    if ($this->request->is('ajax')){
                        $this->render('/Elements/lists/videos_list');
                    }
                    else{
                        $this->render('/Elements/lists/videos_list_m');
                    }
                }
            }
            else { 
                if($type == 'category') $this->set('categoryId', $param);
                $this->set('type', $type);
            }
        }

        public function create($vid = 0)
	{
            $vid = intval($vid);
            $this->_checkPermission(array('confirm' => true));
            $this->_checkPermission(array('aco' => 'video_share'));
            $this->set('title_for_layout', __( 'Share New Video'));
            if (!empty($vid)) {
                $video = $this->Video->findById($vid);
                $this->_checkExistence($video);
                
                // find admin of group
                $this->loadModel('Group.GroupUser');
                $admins = $this->GroupUser->getUsersList($video['Video']['group_id'], GROUP_USER_ADMIN);
                $admins = array_merge($admins, array($video['User']['id']));
                
                $this->_checkPermission(array('admins' => $admins));

                $cakeEvent = new CakeEvent('Plugin.Controller.Video.create', $this, array('video_id' => $vid));
                $this->getEventManager()->dispatch($cakeEvent);

                $this->set('video', $video);
                
                if(!empty($video['Video']['group_id']))
                    $this->set('isGroup',1);
                $this->render('Video.Videos/aj_fetch');
            }
        }
	
	/*
	 * Show add/edit group topic form
	 * @param int $id - topic id to edit
	 */
        public function group_create($id = null) {
            $id = intval($id);
            $this->_checkPermission(array('confirm' => true));
            $this->_checkPermission(array('aco' => 'video_share'));
            
            //$this->set('data',array('id' => $id));
            if (!empty($id)) { // editing
                $video = $this->Video->findById($id);
                $this->_checkExistence($video);
                $this->set('video', $video);
                $this->render('Video.Videos/group_fetch');
            } else {
                if ($this->isApp()) {
                    if(isset($this->request->named['group_id']))  $this->set('group_id', $this->request->named['group_id']);
                }
                $this->render('Video.Videos/create');
            }
        }

        public function fetch($isReturn = true)
	{
            $this->_checkPermission(array('confirm' => true));

            $video = $this->Video->fetchVideo($this->request->data['source'], $this->request->data['url']);

            if (!empty($video)) {
                $this->set('video', $video);
                if (empty($this->request->data['group_id'])) { // public video
                    $cakeEvent = new CakeEvent('Plugin.Controller.Video.fetchPublicVideo', $this);
                    $this->getEventManager()->dispatch($cakeEvent);
                } else {
                    $group_id = $this->request->data['group_id'];
                }
                $this->set('video', $video);

                if($isReturn) {
                    if ($this->isApp()) {
                        if(isset($group_id)) $this->set('isGroup',1);
                        $this->render('Video.Videos/aj_fetch');
                    }
                    else {
                        if(empty($group_id)){
                            $this->render('Video.Videos/aj_fetch');
                        }
                        else {
                            $this->render('Video.Videos/group_fetch');
                        }
                    }
                }
            } else {
                if($isReturn) {
                    $this->autoRender = false;
                    echo '<span style="color:red">' . __( 'Invalid URL. Please try again') . '</span>';
                }
                else {
                    return $error = array(
                        'code' => 400,
                        'message' => __('Invalid URL. Please try again'),
                    );
                }
            }
        }

    public function aj_validate($isReturn = true){
        if($isReturn) $this->autoRender = false;
        $this->_checkPermission( array( 'confirm' => true ) );

        $video = $this->Video->fetchVideo( $this->request->data['source'], $this->request->data['url'] );
        
        if ( isset($video['errorMsg']) && $video['errorMsg'] ){
            if($isReturn) {
                echo json_encode(array('error'=>'<span style="color:red">' . $video['errorMsg'] . '</span>'));
            }
            else {
                return $error = array(
                        'code' => 400,
                        'message' => $video['errorMsg'],
                    );
            }
        }
        if ( empty( $video ) ){
            if($isReturn) {
                echo json_encode(array('error' => '<span style="color:red">' . __('Invalid URL. Please try again') . '</span>' ) );
            }
            else {
                return $error = array(
                        'code' => 400,
                        'message' => __('Invalid URL. Please try again'),
                    );
            }
        }
    }
	
	public function embed()
	{
		$this->autoRender = false;
        
        $w = 400;
        $h = 300;

        $ssl_mode = Configure::read('core.ssl_mode');
        $http = (!empty($ssl_mode)) ? 'https' :  'http';
        switch ( $this->request->data['source'] )
		{
			case 'youtube':
				echo '<div class="video-feed-content"><iframe width="' . $w . '" height="' . $h . '" src="'.$http.'://www.youtube.com/embed/' . h($this->request->data['source_id']) . '?wmode=opaque" frameborder="0" allowfullscreen></iframe></div>';
				break;
				
			case 'vimeo':				
				echo '<div class="video-feed-content"><iframe src="'.$http.'://player.vimeo.com/video/' . h($this->request->data['source_id']) . '" width="' . $w . '" height="' . $h . '" frameborder="0"></iframe></div>';
				break;
		}
	}
	
	public function view( $id = null )
	{
		$id = intval($id);
        
		$this->Video->recursive = 2;
		$video= $this->Video->findById($id);
		if ($video['Category']['id'])
		{
			foreach ($video['Category']['nameTranslation'] as $translate)
			{
				if ($translate['locale'] == Configure::read('Config.language'))
				{
					$video['Category']['name'] = $translate['content'];
					break;
				}
			}
		}
		$this->Video->recursive = 0;
		
		$this->_checkExistence( $video );
        $this->_checkPermission( array('aco' => 'video_view') );    
        $this->_checkPermission( array('user_block' => $video['Video']['user_id']) );
       
		$uid = $this->Auth->user('id');

		MooCore::getInstance()->setSubject($video);
		
		// if it's a group video, redirect to group view
		/*if ( !empty( $video['Video']['group_id'] ) )
		{
			$this->redirect( '/groups/view/' . $video['Video']['group_id'] . '/video_id:' . $id );
			exit;
		}*/
		
		$this->_getVideoDetail( $video );
            
                $cakeEvent = new CakeEvent('Plugin.Controller.Video.view', $this, array('id' => $id));
                $this->getEventManager()->dispatch($cakeEvent);

                        $this->set('title_for_layout', $video['Video']['title']);

                        $description = $this->getDescriptionForMeta($video['Video']['description']);
                if ($description) {
                    $this->set('description_for_layout', $description);
                    $tags = $this->viewVars['tags'];
                    if (count($tags))
                    {
                        $tags = implode(",", $tags).' ';
                    }
                    else
                    {
                        $tags = '';
                    }
                    $this->set('mooPageKeyword', $this->getKeywordsForMeta($tags.$description));
                }

                // set og:image
                if ($video['Video']['thumb']){
                    $mooHelper = MooCore::getInstance()->getHelper('Core_Moo');
                    $this->set('og_image', $mooHelper->getImageUrl($video, array('prefix' => '850')));

                }

                        if (!empty($video['Video']['group_id'])) {
                        $this->loadModel("Group.Group");
                        $group = $this->Group->findById($video['Video']['group_id']);
                        $this->set('group',$group);
                }
            if($this->theme == "mooApp"){ 
                $this->set('videoId',$id);
            }
	}
	
	public function ajax_view( $id = null )
	{
		$id = intval($id);
		$video = $this->Video->findById($id);		
		$this->_checkExistence( $video );
        $this->_checkPermission( array('aco' => 'video_view') );    
        $this->_checkPermission( array('user_block' => $video['Video']['user_id']) );
        //close comment
        $closeCommentModel =  MooCore::getInstance()->getModel('CloseComment');
        $item_close_comment = $closeCommentModel->getCloseComment($video['Video']['id'], $video['Video']['moo_type']);
        $this->set('item_close_comment', $item_close_comment);

		$this->_getVideoDetail( $video );
        $this->render('Video.Videos/aj_view');
    }
	
	private function _getVideoDetail( $video )
	{
        $uid = $this->Auth->user('id');
        $admins = array( $video['Video']['user_id'] );

        $this->_checkPrivacy( $video['Video']['privacy'], $video['User']['id'] );

        $comment_id = 0;
        $reply_id = 0;
        if (!empty( $this->request->named['comment_id'] )) {
            $comment_id = $this->request->named['comment_id'];
        }
        if(!empty( $this->request->named['reply_id'])){
            $reply_id = $this->request->named['reply_id'];
        }

        $cakeEvent = new CakeEvent('Plugin.Controller.Video.getVideoDetail', $this, array('uid' => $uid, 'video' => $video, 'admins' => $admins, 'comment_id' => $comment_id, 'reply_id' => $reply_id));
        $this->getEventManager()->dispatch($cakeEvent);
        $data = $cakeEvent->result['data'];
        $this->loadModel('Like');
        $likes = $this->Like->getLikes($video['Video']['id'], 'Video_Video');
        $this->set('likes', $likes);

        $this->set('video', $video);

        $this->set('data', $data);
	}
	
	/*
	 * Delete video
	 * @param int $id - video id to delete
	 */
	public function delete($id = null,$isRedirect = true)
	{
		$id = intval($id);
		$video = $this->Video->findById($id);
		$this->ajax_delete( $id );
                if($isRedirect) {
                    $this->Session->setFlash( __( 'Video has been deleted') , 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                    if ($video['Video']['group_id']) {
                    	if (!$this->isApp())
                    	{
                        	$this->redirect('/groups/view/'.$video['Video']['group_id'].'/tab:videos');
                        	return;
                    	}
                    	$this->autoRender = true;
                    	return;
                    }
                    $this->redirect( '/videos' );
                }
        }

	public function ajax_delete($id = null)
	{
            $id = intval($id);
            $this->autoRender = false;

            $video = $this->Video->findById($id);
            $this->_checkExistence( $video );
            $cakeEvent = new CakeEvent('Plugin.Controller.Video.beforeDelete', $this, array('video' => $video));
            $this->getEventManager()->dispatch($cakeEvent);
            if(!empty($cakeEvent->result['admins'])){
                $admins = $cakeEvent->result['admins'];
            }

            $this->_checkPermission( array( 'admins' => $admins ) );		
            $this->Video->deleteVideo( $video );

            $cakeEvent = new CakeEvent('Plugin.Controller.Video.afterDeleteVideo', $this, array('item' => $video));
            $this->getEventManager()->dispatch($cakeEvent);
        }
    public function popular(){
        if ($this->request->is('requested')) {
            $num_item_show = $this->request->named['num_item_show'];
            return $this->Video->getPopularVideos( $num_item_show, Configure::read('core.popular_interval') );
        }
    }
    public function _getUserRoleId(){
        return parent::_getUserRoleId();
    }
    
    public function categories_list($isRedirect = true) {
        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();
        $categories = $this->Category->getCategories( 'Video', $role_id);
        if ($this->request->is('requested')) {
            return $categories;
        }
        if($isRedirect && $this->theme == "mooApp") {
            $this->render('/Elements/lists/categories_list');
        }
    }
}

