<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('VideoAppModel','Video.Model');
class Video extends VideoAppModel {
    
    public $actsAs = array(
    	'Activity' => array(
            'type' => 'user',
            'action_afterCreated'=>'video_create',
            'item_type'=>'Video_Video',
            'share' => true,
            'query'=>1,
            'params' => 'item',
    		'parent_field' => 'group_id'
        ),
        'MooUpload.Upload' => array(
            'thumb' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}videos{DS}{field}{DS}',
            )
        ),
        'Hashtag'=>array(
            'field_created_get_hashtag'=>'description',
            'field_updated_get_hashtag'=>'description',
        ),
        'Storage.Storage' => array(
            'type' => array('videos' => 'thumb', 'videos', 'uploadVideo'),
        ),
    );
    public $mooFields = array('title', 'href', 'plugin', 'type', 'url', 'thumb', 'privacy');
    public $belongsTo = array('User' => array('counterCache' => true,
            'counterScope' => array('Video.group_id' => 0)
        ),
        'Category' => array('counterCache' => 'item_count',
            'counterScope' => array('Video.privacy' => PRIVACY_EVERYONE,
                'Category.type' => 'Video')),
        'Group' => array('className' => 'Group.Group', 'counterCache' => true)
    );
    public $hasMany = array('Comment' => array(
            'className' => 'Comment',
            'foreignKey' => 'target_id',
            'conditions' => array('Comment.type' => 'Video_Video'),
            'dependent' => true
        ),
        'Like' => array(
            'className' => 'Like',
            'foreignKey' => 'target_id',
            'conditions' => array('Like.type' => 'Video_Video'),
            'dependent' => true
        ),
        'Tag' => array(
            'className' => 'Tag',
            'foreignKey' => 'target_id',
            'conditions' => array('Tag.type' => 'Video_Video'),
            'dependent' => true
        )
    );
    public $order = "Video.id desc";
    public $validate = array(
        'source' => array(
            'rule' => 'notBlank',
            'message' => 'Source is required'
        ),
        'title' => array(
            'rule' => 'notBlank',
            'message' => 'Title is required'
        ),
        'category_id' => array(
            'rule' => 'notBlank',
            'message' => 'Category is required'
        ),
        'tags' => array(
        	'validateTag' => array(
        		'rule' => array('validateTag'),
        		'message' => 'No special characters ( /,?,#,%,...) allowed in Tags',
        	)
        )
    );

    /*
	 * Get videos based on type
	 * @param string $type - possible value: all (default), my, home, friends, user, search
	 * @param mixed $param - could be uid (friends, home, my, user) or a query string (search)
	 * @param int $page - page number
	 * @return array $videos
	 */
	public function getVideos($type = null, $param = null, $page = 1, $limit = RESULTS_LIMIT, $friend_list = '', $role_id = null) {
            $pp = Configure::read('Video.video_item_per_pages');
            
            if (!empty($pp)){
                $limit = $pp;
            }

            $cond = array();
            
            if ($type == 'group')
                $this->unbindModel(array('belongsTo' => array('Category')));
            else
                $this->unbindModel(array('belongsTo' => array('Group')));

            $viewer = MooCore::getInstance()->getViewer();
            $uid = isset($viewer['User']['id']) ? $viewer['User']['id'] : 0;
            
            $friends = array();
            if ($uid){
                App::import('Model', 'Friend');
                $friend = new Friend();
                $friends = $friend->getFriends($uid);
            }
                
            
            switch ($type) {
                case 'category':
                    if (!empty($param)){
                        if ($role_id == ROLE_ADMIN){
                            $cond = array('Video.category_id' => $param, 'Category.type' => 'Video');
                        }
                        else{
                            $cond = array(
                                'OR' => array(
                                    array('Video.category_id' => $param, 'Category.type' => 'Video', 'Video.privacy' => PRIVACY_EVERYONE),
                                    array('Video.user_id' => $uid, 'Video.category_id' => $param, 'Category.type' => 'Video'),
                                    
                                ),
                            );
                            
                            if (count($friends)){
                                $cond['OR'][] =  array('Video.user_id' => $friends, 'Video.category_id' => $param, 'Category.type' => 'Video', 'Video.privacy' => PRIVACY_FRIENDS);
                            }
                            
                        }
                    }
                    break;

                case 'friends':
                    if ($param) {
                        
                        if ($role_id == ROLE_ADMIN)
                            $cond = array('Video.user_id' => $friends, 'Video.group_id' => 0);
                        else
                            $cond = array('Video.user_id' => $friends, 'Video.privacy <> ' . PRIVACY_ME, 'Video.group_id' => 0);
                    }
                    break;

                case 'home':
                case 'my':
                    if ($param)
                        $cond = array('Video.user_id' => $param, 'Video.group_id' => 0);

                    break;

                case 'user':
                    if ($param) {
                        if ($role_id == ROLE_ADMIN) //viewer is admin or owner himself
                            $cond = array('Video.user_id' => $param, 'Video.group_id' => 0);
                        elseif (!empty($friend_list)) //viewer is a friend
                            $cond = array('Video.user_id' => $param, 'Video.group_id' => 0, 'Video.privacy <> ' . PRIVACY_ME);
                        else // normal viewer
                            $cond = array('Video.user_id' => $param, 'Video.group_id' => 0, 'Video.privacy' => PRIVACY_EVERYONE);
                    }
                    break;

                case 'search':
                    if ($role_id == ROLE_ADMIN){
                        if ($param){
                            $cond = array('Video.group_id' => 0, 'MATCH(Video.title, Video.description) AGAINST(? IN BOOLEAN MODE)' => urldecode($param));
                        }else{
                            $cond = array('Video.group_id' => 0);
                        }
                    }
                    else{
                        if ($param){
                            $cond = array(
                                'OR' => array(
                                    array('Video.group_id' => 0, 'MATCH(Video.title, Video.description) AGAINST(? IN BOOLEAN MODE)' => urldecode($param), 'Video.privacy' => PRIVACY_EVERYONE),
                                    array('Video.user_id' => $uid, 'Video.group_id' => 0, 'MATCH(Video.title, Video.description) AGAINST(? IN BOOLEAN MODE)' => urldecode($param), 'Video.privacy' => PRIVACY_ME),
                                    
                                ),
                            );
                            
                            if (count($friends)){
                                $cond['OR'][] =  array('Video.user_id' => $friends, 'Video.group_id' => 0, 'MATCH(Video.title, Video.description) AGAINST(? IN BOOLEAN MODE)' => urldecode($param), 'Video.privacy' => PRIVACY_FRIENDS);
                            }
                        }else {
                            $cond = array(
                                'OR' => array(
                                    array('Video.group_id' => 0, 'Video.privacy' => PRIVACY_EVERYONE),
                                    array('Video.user_id' => $uid, 'Video.group_id' => 0),
                                    array('Video.user_id' => $friends, 'Video.group_id' => 0, 'Video.privacy' => PRIVACY_FRIENDS)
                                ),
                            );
                            
                            if (count($friends)){
                                $cond['OR'][] =  array('Video.user_id' => $friends, 'Video.group_id' => 0, 'Video.privacy' => PRIVACY_FRIENDS);
                            }
                        }
                    }
                    break;

                case 'group':
                    if (!empty($param))
                        $cond = array('Video.group_id' => $param);

                    break;

                default:
                    if ($role_id == ROLE_ADMIN) {
                        $cond = array();
                    } else {
                        $cond = array(
                            'OR' => array(
                                array(
                                    'Video.group_id' => 0,
                                    'Video.privacy' => PRIVACY_EVERYONE,
                                ),
                                array(
                                    'Video.group_id' => 0,
                                    'Video.user_id' => $param
                                ),
                                array(
                                    'Video.group_id' => 0,
                                    'Find_In_Set(Video.user_id,"' . $friend_list . '")',
                                    'Video.privacy' => PRIVACY_FRIENDS
                                )
                            ),
                        );
                    }
            }
            
            // only show video not in queue for converting
            $cond['Video.in_process'] = 0;

            //only get videos of active user
            $cond['User.active'] = 1;
            if ($type != 'group')
                $cond['Video.group_id'] = 0;
            $cond = $this->addBlockCondition($cond);
            $videos = $this->find('all', array('conditions' => $cond, 'limit' => $limit, 'page' => $page));

            return $videos;
        }

        public function fetchVideo($source, $url) {
            $video = array();

            switch ($source) {
                case 'youtube':
                    if (strpos($url, 'http://youtu.be') !== false) {
                        $tmp = explode('/', $url);
                        $id = $tmp[count($tmp) - 1];
                    } else {
                        $url_string = parse_url($url, PHP_URL_QUERY);
                        parse_str($url_string, $args);
                        $id = isset($args['v']) ? $args['v'] : false;
                    }

                    //user regex to get link id
                    if(!$id)
                    {
                        preg_match('~
        # Match non-linked youtube URL in the wild. (Rev:20130823)
        https?://         # Required scheme. Either http or https.
        (?:[0-9A-Z-]+\.)? # Optional subdomain.
        (?:               # Group host alternatives.
          youtu\.be/      # Either youtu.be,
        | youtube         # or youtube.com or
          (?:-nocookie)?  # youtube-nocookie.com
          \.com           # followed by
          \S*             # Allow anything up to VIDEO_ID,
          [^\w\s-]       # but char before ID is non-ID char.
        )                 # End host alternatives.
        ([\w-]{11})      # $1: VIDEO_ID is exactly 11 chars.
        (?=[^\w-]|$)     # Assert next char is non-ID or EOS.
        (?!               # Assert URL is not pre-linked.
          [?=&+%\w.-]*    # Allow URL (query) remainder.
          (?:             # Group pre-linked alternatives.
            [\'"][^<>]*>  # Either inside a start tag,
          | </a>          # or inside <a> element text contents.
          )               # End recognized pre-linked alts.
        )                 # End negative lookahead assertion.
        [?=&+%\w.-]*        # Consume any URL (query) remainder.
        ~ix',
                            $url,
                            $matches_id);
                        if(count($matches_id) >1)
                            $id = $matches_id[1];
                    }
                    $id = explode(" ",$id);
                    $id = $id[0];
                    /*$id = explode("__", $id);
                    $id = $id[0];*/
                    if (!empty($id)) {
                        // Youtube API v2 deprecated on May 2015
                        require_once CAKE_CORE_INCLUDE_PATH . DS . 'Google' . DS . 'Service.php';
                        require_once CAKE_CORE_INCLUDE_PATH . DS . 'Google' . DS . 'Client.php';
                        require_once CAKE_CORE_INCLUDE_PATH . DS . 'Google' . DS . 'Collection.php';
                        require_once CAKE_CORE_INCLUDE_PATH . DS . 'Google' . DS . 'Service' . DS . 'Resource.php';
                        require_once CAKE_CORE_INCLUDE_PATH . DS . 'Google' . DS . 'Service' . DS . 'YouTube.php';
                        $DEVELOPER_KEY = trim(Configure::read('core.google_dev_key'));
                        $client = new Google_Client();
                        $client->setDeveloperKey($DEVELOPER_KEY);
                        // Define an object that will be used to make all API requests.
                        $youtube = new Google_Service_YouTube($client);

                        // Call the API's videos.list method to retrieve the video resource.
                        $listResponse = array();
                        if (empty($DEVELOPER_KEY)){
                            $video['errorMsg'] = __('Your Youtube API key is invalid, missing, or has exceeded its quota. Please contact Admin.');
                        }else{
                            try {
                                $listResponse = $youtube->videos->listVideos("snippet",
                                    array('id' => $id));
                                if (!empty($listResponse)) {
                                    $videoArray = $listResponse[0];
                                    $videoSnippet = $videoArray['snippet'];
                                    $video['Video']['source'] = 'youtube';
                                    $video['Video']['source_id'] = $id;
                                    $video['Video']['title'] = $videoSnippet['title'];
                                    $video['Video']['description'] = $videoSnippet['description'];
                                    $video['Video']['thumb'] = '';
                                    
                                    // high quality thumbnail
                                    if (isset($videoSnippet['modelData']['thumbnails']['high'])){
                                        $video['Video']['thumb'] = $videoSnippet['modelData']['thumbnails']['high']['url'];
                                    }
                                    
                                    // standard quality thumbnail
                                    else if (empty($video['Video']['thumb']) && isset($videoSnippet['modelData']['thumbnails']['standard'])){
                                        $video['Video']['thumb'] = $videoSnippet['modelData']['thumbnails']['standard']['url'];
                                    }
                                    
                                    // medium quality thumbnail
                                    else if (empty($video['Video']['thumb']) && isset($videoSnippet['modelData']['thumbnails']['medium'])){
                                        $video['Video']['thumb'] = $videoSnippet['modelData']['thumbnails']['medium']['url'];
                                    }
                                    
                                    // default quality thumbnail (low-quality)
                                    else if (empty($video['Video']['thumb']) && isset($videoSnippet['modelData']['thumbnails']['thumbnails'])){
                                        $video['Video']['thumb'] = $videoSnippet['modelData']['thumbnails']['thumbnails']['url'];
                                    }
                                } 
                                
                            } catch (Google_ServiceException $e) {
                              $video['errorMsg'] = sprintf('<p>A service error occurred: <code>%s</code></p>',
                                  htmlspecialchars($e->getMessage()));
                            } catch (Google_Exception $e) {
                              $video['errorMsg'] = sprintf('<p>An client error occurred: <code>%s</code></p>',
                                  htmlspecialchars($e->getMessage()));
                            }
                        }
                                    
                    }

                    break;

                case 'vimeo':
                    preg_match('/(\d+)/', $url, $matches);
                    if (!strpos($url, 'vimeo.com')){
                        return false;
                    }
                    if (!empty($matches[0])) {
                        $id = $matches[0];
                        
                        $entry = MooCore::getInstance()->getHtmlContent('http://vimeo.com/api/v2/video/' . $id . '.php');
                        
                        if (!strstr($entry, 'not found')){
                            $entry = unserialize($entry);
                        }
                        
                        if (!empty($entry) && is_array($entry)) {
                            $video['Video']['source'] = 'vimeo';
                            $video['Video']['source_id'] = $id;
                            $video['Video']['title'] = $entry[0]['title'];
                            $video['Video']['description'] = str_replace('<br />', '', $entry[0]['description']);
                            $video['Video']['thumb'] = $entry[0]['thumbnail_medium'];
                        }
                    }

                    break;
            }

            if (!empty($video)) {
                $video['Video']['id'] = '';
                $video['Video']['category_id'] = '';
                $video['Video']['privacy'] = PRIVACY_EVERYONE;
            }

            return $video;
        }

        public function parseStatus( &$activity )
	{
        $source = '';
        $source_url = '';
		if ( strpos( $activity['Activity']['content'], 'youtube.com' ) !== false || strpos( $activity['Activity']['content'], 'youtu.be' ) !== false ){          
			$source = 'youtube';
        }
						
        if ( strpos( $activity['Activity']['content'], 'vimeo.com' ) !== false ){
			$source = 'vimeo';
        }
			
        if( strpos( $activity['Activity']['source_url'], 'youtube.com' ) !== false || strpos( $activity['Activity']['source_url'], 'youtu.be' ) !== false ){          
            $source_url = 'youtube';
        } 

        if(strpos( $activity['Activity']['source_url'], 'vimeo.com' ) !== false){
            $source_url = 'vimeo';
        }

			
		if ( !empty( $source ) || !empty( $source_url ))
		{					
			if(!empty( $source_url)){
			$vid = $this->fetchVideo($source, $activity['Activity']['source_url']);
			}elseif (!empty( $source)) {
               $vid = $this->fetchVideo($source_url, $activity['Activity']['content']); 
           }	
		
			if ( !empty( $vid ) )
				$activity['Content'] = $vid;
		}
	}
	
	public function getPopularVideos( $limit = 5, $days = null )
	{
		$this->unbindModel(	array('belongsTo' => array('Group') ) );
	
		$cond = array('Video.privacy' => PRIVACY_EVERYONE, 'Video.group_id' => 0);
		
		if ( !empty( $days ) )
			$cond['DATE_SUB(CURDATE(),INTERVAL ? DAY) <= Video.created'] = intval($days);
                
                // only show video not in queue for converting
                $cond['Video.in_process'] = 0;

        //only get videos of active user
        $cond['User.active'] = 1;
         $cond = $this->addBlockCondition($cond);
		$videos = $this->find( 'all', array( 'conditions' => $cond, 
											 'order' => 'Video.like_count desc', 
											 'limit' => intval($limit)
							) );
									 
		return $videos;
	}
	
	public function deleteVideo( $video )
	{
		// delete photo
		if ( !empty( $video['Video']['thumb'] ) && file_exists(WWW_ROOT . 'uploads/videos/' . $video['Video']['thumb']) )
			unlink( WWW_ROOT . 'uploads/video/' . $video['Video']['thumb'] );
                
                // delete activity
                $activityModel = MooCore::getInstance()->getModel('Activity');
                $parentActivity = $activityModel->find('list', array('fields' => array('Activity.id') , 'conditions' => 
                    array('Activity.item_type' => 'Video_Video', 'Activity.item_id' => $video['Video']['id'])));

                $activityModel->deleteAll(array( 'Activity.item_type' => 'Video_Video', 'Activity.item_id' => $video['Video']['id'] ), true, true );

                // delete child activity
                $activityModel->deleteAll(array('Activity.item_type' => 'Video_Video', 'Activity.parent_id' => $parentActivity));
			
		$this->delete( $video['Video']['id'] );
	}

    public function countVideoByCategory($category_id){
        $num_videos = $this->find('count',array(
            'conditions' => array_merge($this->addBlockCondition(), array(
                'Video.category_id' => $category_id,
                'Video.in_process' => 0,
                'User.active' => 1
            ))
        ));
        return $num_videos;
    }
    public function countMyVideo($uid = null,$friend_list = '',$cat_id = null)
    {
        $num_videos = $this->find('count',array(
            'conditions' => array(
                'OR' => array(
                    array(
                        'Video.user_id' => $uid,
                        'Video.privacy' => PRIVACY_ME,
                        'Video.category_id' => $cat_id
                    ),
                    array(
                        'Video.user_id' => $uid,
                        'Video.privacy' => PRIVACY_FRIENDS,
                        'Video.category_id' => $cat_id
                    ),
                    array(
                        'Find_In_Set(Video.user_id,"'.$friend_list.'")',
                        'Video.privacy' => PRIVACY_FRIENDS,
                        'Video.category_id' => $cat_id
                    )
                ),
                'Video.in_process' => 0,
                'User.active' => 1
            )
        ));
        return $num_videos;
    }

    public function afterSave($created, $options = array()){
        Cache::clearGroup('video');
        Cache::delete('category.video');
        Cache::delete('category.drop_down.video');
        if($this->field('group_id'))
            Cache::delete('group_detail.'.$this->field('group_id'),'group');
    }
    
    public function getHref($row)
    {
    	$request = Router::getRequest();
    	if (isset($row['id']) && isset($row['title'])){
    		return $request->base.'/videos/view/'.$row['id'].'/'.seoUrl($row['title']);
        }
        else{
            return '';
        }
    	return false;
    }
    
    public function getThumb($row){
        return 'thumb';
    }

    public function getTitle(&$row)
    {
        if (isset($row['title']))
        {
            $row['title'] = htmlspecialchars($row['title']);
            return $row['title'];
        }
        return '';
    }
    
    public function getPrivacy($row){
        if (isset($row['privacy'])){
            return $row['privacy'];
        }
        return false;
    }

    public function getVideoSuggestion($q, $limit = RESULTS_LIMIT,$page = 1){
        $this->unbindModel(	array('belongsTo' => array('Group') ) );
        $cond = array('Video.title LIKE' => $q . "%",'Video.privacy' => PRIVACY_EVERYONE );

        // only show video not in queue for converting
        $cond['Video.in_process'] = 0;
            
        //only get videos of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $videos = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page) );
        return $videos;
    }

    public function getVideoHashtags($qid, $limit = RESULTS_LIMIT,$page = 1){
        $cond = array(
            'Video.id' => $qid,
        );
        
        // only show video not in queue for converting
        $cond['Video.in_process'] = 0;

        //only get videos of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $videos = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
        return $videos;
    }

    public function beforeDelete($cascade = true){
        Cache::clearGroup('video');
        Cache::delete('category.video');
        Cache::delete('category.drop_down.video');
        if($this->field('group_id'))
            Cache::delete('group_detail.'.$this->field('group_id'),'group');
    }

    public function updateCounter($id, $field = 'comment_count',$conditions = '',$model = 'Comment') {
        if(empty($conditions)){
            $conditions = array('Comment.type' => 'Video_Video', 'Comment.target_id' => $id);
        }
        parent::updateCounter($id, $field, $conditions, $model);
        Cache::delete('video.video_view_'.$id,'video');
    }
    
    public function log($msg, $type = LOG_ERR, $scope = null) 
    {
        parent::log($msg,'videos');
    }
    
    public function convert_ffmpeg($aVideo, $disable_activity = false){
        // get current userid
        $uid = MooCore::getInstance()->getViewer(true);
        
        // Start converting process ...
        $iWidth = VIDEO_WIDTH; // default width
        $iHeight = VIDEO_HEIGHT; // default height
        // 'Converting: ' . $sSource
        $aFind = array(
            '{source}',
            '{destination}',
            '{width}',
            '{height}'
        );
        
        $sNewPath = $sSource = WWW_ROOT . 'uploads' . DS . 'videos' . DS . 'thumb' . DS . $aVideo['Video']['id'] . DS . $aVideo['Video']['destination'];
        
        $extension = array_pop(explode('.', $aVideo['Video']['destination']));
        
        // convert to mp4
        if ($extension != 'mp4'){
            $sNewPath1 = str_replace('.' . $extension, '.mp4', $sNewPath);
            $aReplace = array(
                $sSource,
                $sNewPath1,
                $iWidth,
                $iHeight
            );
            
            $params_for_ffmpeg = Configure::read('UploadVideo.video_setting_params_ffmpeg_path') . ' ' . Configure::read('UploadVideo.video_setting_params_ffmpeg_mp4');
            $sFfmpegParams = str_replace($aFind, $aReplace, $params_for_ffmpeg);
            exec($sFfmpegParams . ' 2>&1', $aOutput);
            
            $this->log($aOutput);
        }
        
        // create thumbnail
        $thumbLocation = 'uploads' . DS . 'tmp' . DS . md5(time() . $aVideo['Video']['title'] . mt_rand(1, 1000)) . '.jpg';
        $imgPath = WWW_ROOT . $thumbLocation;
        $sFfmpegParamsForThumbnail = Configure::read('UploadVideo.video_setting_params_ffmpeg_path')." -i " . $sSource . " -ss 00:00:01.000 -vframes 1 " . $imgPath;
        exec($sFfmpegParamsForThumbnail . ' 2>&1', $aOutput);
        
        $new_destination = str_replace('.' . $extension, '.mp4', $aVideo['Video']['destination']);
        $videoModel = MooCore::getInstance()->getModel('Video');
        $videoModel->updateAll(array(
            'id' => $aVideo['Video']['id'],
            'thumb' =>  "'" . $thumbLocation . "'",
            'in_process' => 0,
            'destination' =>  "'" . $new_destination . "'",
            'privacy' => $aVideo['Video']['privacy']
        ), array('Video.id' => $aVideo['Video']['id']));
        
        if (!$disable_activity){
            // create activity feed
            $activityModel = MooCore::getInstance()->getModel('Activity');
            $data = array(
                'type' => 'user',
                'action' => 'video_create',
                'user_id' => $uid,
                'item_type' => 'Video_Video',
                'item_id' => $aVideo['Video']['id'],
                'privacy' => $aVideo['Video']['privacy'],
                'params' => 'item',
                'plugin' => 'Video',
                'share' => true,
            );
            if (!empty($aVideo['Video']['group_id'])){

                // do not enable share feature for PRIVATE group  - issue MOOSOCIAL-2367
                $groupModel = MooCore::getInstance()->getModel('Group.Group');
                $group = $this->Group->findById($aVideo['Video']['group_id']);
                if (!empty($group) && $group['Group']['type'] == PRIVACY_PRIVATE){
                    $data['share'] = false;
                }

                $data['target_id'] = $aVideo['Video']['group_id'];
                $data['type'] = 'Group_Group';
            }
            $activityModel->save($data);
        }
        
        return true;
    }
    
    public function convert_mencoder($aVideo){
        return true;
    }
}
