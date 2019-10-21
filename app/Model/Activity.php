<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');
class Activity extends AppModel
{
	public $mooFields = array('type');

	public $validate = array('user_id' => array( 'rule' => 'notBlank'));

	public $belongsTo = array( 'User' );

	public $hasMany = array( 'ActivityComment' => array(
														 'className' => 'ActivityComment',
													  	 'dependent' => true,
													  	 'order' => 'ActivityComment.id desc'
													),
							 'Like' => 			  array(
										 				 'className' => 'Like',
														 'foreignKey' => 'target_id',
														 'conditions' => array('Like.type' => 'activity'),
														 'dependent'=> true
													)
						);

	public $order = 'Activity.modified desc';

	public $_conditions = array('Activity.status' => 'ok');
    public $actsAs = array(
        'Hashtag' => array(

            'field_created_get_hashtag'=>'message',
            'field_updated_get_hashtag'=>'content',

        ),
        'UserTagging' => array(

        ),

    );
	/*
	 * Get the activities based on $type
	 * @param string $type
	 * @param mixed $param
     * @param mixed #param2
	 * @param int $page
	 * @return array $activities - formated array of activities
	 */

public function getActivities( $type = null, $param = null, $param2 = null, $page = 1, $comment_id = '0', $reply_id = '0' )
	{

		$this->recursive = 2;
		$this->cacheQueries = true;
		$this->ActivityComment->cacheQueries = true;
        $this->User->cacheQueries = true;
		$cond = $this->getConditon($type , $param, $param2);
		//User Block
        $userBlockModal = MooCore::getInstance()->getModel('UserBlock');
        $blockedUsers = $userBlockModal->getBlockedUsers();

        if(!empty($blockedUsers)){
	        $str_blocked_users = implode(',', $blockedUsers);
	        $cond[] = "Activity.user_id NOT IN ($str_blocked_users)";
	        $this->hasMany['ActivityComment']['conditions'] = array( "ActivityComment.user_id NOT IN ($str_blocked_users)");
        }
		$this->unbindModel(
			array('hasMany' => array('Like'))
		);

		$this->ActivityComment->unbindModel(
			array('belongsTo' => array('Activity'))
		);

        $this->User->unbindModel(
            array('belongsTo' => array('Role'))
        );
        
		$plugins = MooCore::getInstance()->getListPluginEnable();
		$plugins[] = '';
		$cond['Activity.Plugin'] = $plugins;

        // Fixed slow query for 31k users
        $this->unbindModel(array('belongsTo'=>'User'));
        $condTmp =  $cond;
        if (isset($condTmp["User.active"])){
            unset($condTmp["User.active"]);
        }
        
        $order = $this->order;
        if ($type != 'profile')
        {
        	if (in_array($type,array('friends','everyone','home')))
     		{
     			$order = array('Activity.activity_pin DESC','Activity.activity_pin_date DESC','Activity.modified desc');
        	}
        	else 
        	{
        		$order = array('Activity.pin DESC','Activity.pin_date DESC','Activity.modified desc');
        	}
        }
        $this->Behaviors->disable('UserTagging');
        $activities = $this->find('all', array(
            'fields' => 'Activity.id',
            'conditions' => $condTmp,
            'limit' => RESULTS_LIMIT,
            'page' => $page,
        	'order' => $order
        )	);
        $this->Behaviors->enable('UserTagging');
        for($i=0;$i<count($activities);$i++){
            $aNameCached = "getActivities.{$activities[$i]["Activity"]["id"]}";
            $activity = Cache::read($aNameCached,"10_min");
            if(empty($activity)){
                $condTmp["Activity.id"] = $activities[$i]["Activity"]["id"];
                $activity = $this->find('first', array(
                    'conditions' => $condTmp,
                )	);
                Cache::write($aNameCached,$activity,"20_min");
            }
            $activities[$i] = $activity;
        }

        // End fixed
								
		App::import('Model', 'Comment');
        $comment = new Comment();
	
		App::import('Model', 'Like');
        $like = new Like();

        if($comment_id){
            $cond = array(
                'Comment.id' => $comment_id
            );
        }else{
            $cond = array();
        }

		// save the items to activities array			
		foreach ( $activities as $key => &$activity )
		{	
			// item activity
			if ( $activity['Activity']['params'] == 'item' )
			{
				$item_type = $activity['Activity']['item_type'];
				list($plugin, $name) = mooPluginSplit($item_type);
				$object = MooCore::getInstance()->getItemByType($item_type,$activity['Activity']['item_id']);
				if (isset($object[$name]['comment_count']))
				{                    
					// get item's comments
                    $activity['ItemCommentCount'] = $comment->find('count', array(  'conditions' => array(
                                'Comment.target_id' => $activity['Activity']['item_id'],
                                'Comment.type'      => $item_type ),
                                'order' => 'Comment.id desc',
                                'limit' => RESULTS_LIMIT
                        )  );
                    if (Configure::read('core.comment_sort_style') == COMMENT_RECENT){
                        $activity['ItemComment'] = $comment->find('all', array(  'conditions' => array_merge($this->addBlockCondition(array(), 'Comment'), array(
                                'Comment.target_id' => $activity['Activity']['item_id'],
                                $cond,
                                'Comment.type'      => $item_type )),
                                'order' => 'Comment.id desc',
                                'limit' => RESULTS_LIMIT
                        )  );

                    }else{
                        $activity['ItemComment'] = $comment->find('all', array(  'conditions' => array_merge($this->addBlockCondition(array(), 'Comment'),array(
                                'Comment.target_id' => $activity['Activity']['item_id'],
                                $cond,
                                'Comment.type'      => $item_type )),
                                'order' => 'Comment.id asc',
                                'limit' => LIMIT_DISPLAY_COMMENT
                        )  );
//                        $activity['ItemComment'] = array_reverse( $activity['ItemComment'] );
                    }

                    if(!empty($activity['ItemComment']) && $reply_id) {
                        $last_comment = $activity['ItemComment'][0];
                        $key = 0;

                        $type = 'comment';
                        if($last_comment['Comment']['count_reply']){
                            $replies = $comment->find('all', array(
                                'conditions' => array(
                                    'Comment.id' => $reply_id
                                ),
                            ));
                            $replies_count = $comment->getCommentsCount( $last_comment['Comment']['id'], $type );
                            $like = new Like();
                            $comment_likes = $like->getCommentLikes( $replies, $last_comment['Comment']['user_id'] );
                            $data['comment_likes'] = $comment_likes;
                            $activity['ItemComment'][$key]['Replies'] = array_reverse($replies);
                            $activity['ItemComment'][$key]['RepliesIsLoadMore'] = ($replies_count - 1) > 0 ? true : false;
                            $activity['ItemComment'][$key]['RepliesCommentLikes'] = $comment_likes;
                        }
                    }
					
					// get items' likes
					$activity['Likes'] = $like->find('list', array( 'conditions' => array(
														'Like.target_id' => $activity['Activity']['item_id'],
														'Like.type'      => $item_type ),
														'fields' => array( 'Like.user_id', 'Like.thumb_up' )
					) );
				}
            }
            //photo comment
            if (($activity['Activity']['item_type'] == 'Photo_Album' && $activity['Activity']['action'] == 'wall_post') ||
              ($activity['Activity']['item_type'] == 'Photo_Photo' && $activity['Activity']['action'] == 'photos_add')
             ) {
                $photo_id = explode(',', $activity['Activity']['items']);
                if (count($photo_id) == 1) {
                    $photo_id = $photo_id[0];

                    if (Configure::read('core.comment_sort_style') == COMMENT_RECENT){
                        $activity['PhotoComment'] = $comment->find('all', array(  'conditions' => array_merge($this->addBlockCondition(array(), 'Comment'),array(
                            'Comment.target_id' => $photo_id,
                            $cond,
                            'Comment.type'      => 'Photo_Photo' )),
                            'order' => 'Comment.id desc',
                            ));

                    }else{
                        $activity['PhotoComment'] = $comment->find('all', array(  'conditions' => array_merge($this->addBlockCondition(array(), 'Comment'),array(
                            'Comment.target_id' => $photo_id,
                            $cond,
                            'Comment.type'      => 'Photo_Photo' )),
                            'order' => 'Comment.id asc',
                            ));
//                        $activity['PhotoComment'] = array_reverse( $activity['PhotoComment'] );
                    }

                    $activity['Likes'] = $like->find('list', array( 'conditions' => array(
														'Like.target_id' => $photo_id,
														'Like.type'      => 'Photo_Photo' ),
														'fields' => array( 'Like.user_id', 'Like.thumb_up' )
					) );

                    if(!empty($activity['PhotoComment']) && $reply_id) {
                        $last_comment = $activity['PhotoComment'][0];
                        $key = 0;

                        $type = 'comment';
                        if($last_comment['Comment']['count_reply']){
                            $replies = $comment->find('all', array(
                                'conditions' => array(
                                    'Comment.id' => $reply_id
                                )
                            ));
                            $replies_count = $comment->getCommentsCount( $last_comment['Comment']['id'], $type );
                            $like = new Like();
                            $comment_likes = $like->getCommentLikes( $replies, $last_comment['Comment']['user_id'] );
                            $data['comment_likes'] = $comment_likes;
                            $activity['PhotoComment'][$key]['Replies'] = array_reverse($replies);
                            $activity['PhotoComment'][$key]['RepliesIsLoadMore'] = ($replies_count - 1) > 0 ? true : false;
                            $activity['PhotoComment'][$key]['RepliesCommentLikes'] = $comment_likes;
                        }
                    }
                }
            }

            if(!empty($activity['ActivityComment'])){
                if($comment_id){
                    foreach ($activity['ActivityComment'] as $comment){
                        if($comment['id'] == $comment_id){
                            $activity['ActivityComment'] = array();
                            $activity['ActivityComment'][0] =  $comment;
                            break;
                        }
                    }

                    $type = 'core_activity_comment';
                    if($activity['ActivityComment'][0]['count_reply'] && $reply_id){
                        App::import('Model', 'Comment');
                        $commentModel = new Comment();

                        $replies = $commentModel->find('all', array(
                            'conditions' => array(
                                'Comment.id' => $reply_id
                            ),
                        ));
                        $replies_count = $commentModel->getCommentsCount( $activity['ActivityComment'][0]['id'], $type );
                        $like = new Like();
                        $comment_likes = $like->getCommentLikes( $replies, $activity['ActivityComment'][0]['user_id'] );
                        $data['comment_likes'] = $comment_likes;
                        $activity['ActivityComment'][0]['Replies'] = array_reverse($replies);
                        $activity['ActivityComment'][0]['RepliesIsLoadMore'] = ($replies_count - 1) > 0 ? true : false;
                        $activity['ActivityComment'][0]['RepliesCommentLikes'] = $comment_likes;
                    }
                }
            }
		}

		return $activities;
	}

	public function getConditon($type = null, $param = null, $param2 = null,$param3 = null)
	{
		$cond = array();
		$viewer = MooCore::getInstance()->getViewer();
		$uid = MooCore::getInstance()->getViewer(true);
		switch ($type) {
			case 'home':
			case 'everyone':
				if ($uid && isset($viewer['Role']))
				{
					$rules = explode(',',$viewer['Role']['params']);					
					if (in_array('activity_view', $rules))
					{
						if (is_string($param3))
						{
							$cond['Activity.content LIKE'] = '%'.$param3.'%';
						}
						break;
					}
				}
				elseif (!$uid)
				{
					$params = Cache::read('guest_role');
					
					if (empty($params)) {
						$ruleModel = MooCore::getInstance()->getModel("Role");
						$guest_role = $ruleModel->findById(ROLE_GUEST);
					
						$params = explode(',', $guest_role['Role']['params']);
						Cache::write('guest_role', $params);
					}
					if ($params && in_array('activity_view', $params))
					{
						if (is_string($param3))
						{
							$cond['Activity.content LIKE'] = '%'.$param3.'%';
						}
						break;
					}
				}
					
                if (!Configure::read("core.enable_follow")) {
                    $friend = MooCore::getInstance()->getModel('Friend');
                    $friends = $friend->getFriends($param);
                    if ($viewer['Role']['is_admin']) {
                        $cond = array(
                            'OR' => array(
                            		array('Activity.type' => APP_USER, 'Activity.privacy' => PRIVACY_EVERYONE),
                                array('Activity.action' => "friend_add", 'Activity.privacy <>' => PRIVACY_ME)
                            )
                        );
                    } else {
                        $cond = array(
                            'OR' => array(
                                $this->addBlockConditionTarget(array('Activity.type' => APP_USER, 'Activity.privacy' => PRIVACY_EVERYONE)),
                                $this->addBlockConditionTarget(array('Activity.type' => APP_USER, 'Activity.user_id' => $friends, 'Activity.privacy <>' => PRIVACY_ME)),
                            )
                        );
                    }


                    $event = new CakeEvent('Model.Activity.afterSetParamsConditionsOr', $this, array(
                        'param' => $param
                    ));
                    $this->getEventManager()->dispatch($event);
                    if ($event->result && is_array($event->result)) {
                        foreach ($event->result as $result) {
                            $cond['OR'] = array_merge($cond['OR'], $result);
                        }
                    }
                }
                else
                {
                    $followModel = MooCore::getInstance()->getModel('UserFollow');
                    $follows = $followModel->find('all',array('conditions'=>array('UserFollow.user_id'=>$param)));
                    $tmp = array();
                    foreach ($follows as $follow)
                    {
                        $tmp[] = $follow['UserFollow']['user_follow_id'];
                    }

                    $follows = $tmp;
                    
                    $friend = MooCore::getInstance()->getModel('Friend');
                    $friends = $friend->getFriends($param);
                    
                    $follows = array_unique(array_merge($friends,$follows,array($param)));

                    $cond = array(
                        'OR' => array(
                            $this->addBlockConditionTarget(array('Activity.type' => APP_USER, 'Activity.user_id' => $follows, 'Activity.privacy' => PRIVACY_PUBLIC)),
                            $this->addBlockConditionTarget(array ('Activity.user_id' => $param,'Activity.action' => "friend_add", 'Activity.privacy <>' => PRIVACY_ME)),
                        ) );

                    $event = new CakeEvent('Model.Activity.afterSetParamsConditionsOr', $this, array(
                        'param'=>$param
                    ));
                    $this->getEventManager()->dispatch($event);
                    if ($event->result && is_array($event->result))
                    {
                        foreach ($event->result as $result)
                        {
                            foreach ($result as &$tmp)
                                $tmp['Activity.user_id'] = $follows;

                            $cond['OR'] = array_merge($cond['OR'],$result);
                        }
                    }
                }
                if ($uid)
                {
	                $cond['OR'][] = array(
	                	'Activity.type' => 'user',
	                	'Activity.user_id' => $uid
	                );
                }
                if (is_string($param3))
                {
                	$cond['Activity.content LIKE'] = '%'.$param3.'%';
                }
				break;

			case 'friends':
				$friend = MooCore::getInstance()->getModel('Friend');
				$friends = $friend->getFriends( $param );

				$cond = array(
					'OR' => array(
						$this->addBlockConditionTarget(array('Activity.type' => APP_USER, 'Activity.user_id' => $friends, 'Activity.privacy <> ' . PRIVACY_ME)),
						array ('Activity.user_id' => $param,'Activity.action <>' => "friend_add"),
                        $this->addBlockConditionTarget(array ('Activity.user_id' => $param,'Activity.action' => "friend_add", 'Activity.privacy <>' => PRIVACY_ME)),
				) );

				$event = new CakeEvent('Model.Activity.afterSetParamsConditionsOr', $this, array(
					'param'=>$param
				));
				$this->getEventManager()->dispatch($event);
				if ($event->result && is_array($event->result))
				{
					foreach ($event->result as $result)
					{
                        foreach ($result as &$tmp)
                            $tmp['Activity.user_id'] = $friends;

						$cond['OR'] = array_merge($cond['OR'],$result);
					}
				}
				break;

			case 'profile':

                //get activities that this user been tagged
                $userTaggingModel = MooCore::getInstance()->getModel('UserTagging');
                $friend = MooCore::getInstance()->getModel('Friend');
                $blockModel = MooCore::getInstance()->getModel('UserBlock');
                $block_users = $blockModel->getBlockedUsers($param2);

                $taggings = $userTaggingModel->find('all',array('conditions' => array(
                        'UserTagging.users_taggings LIKE "%'.$param.'%"'
                    )
                ));

                if(!empty($taggings))
                {
                    foreach($taggings as $key => $value)
                    {
                        $userIds = explode(',',$value['UserTagging']['users_taggings']);
                        if(!in_array($param,$userIds))
                            unset($taggings[$key]);
                    }
                }
                if(!empty($taggings))
                {
                    $activityIds = Hash::combine($taggings,'{n}.UserTagging.item_id','{n}.UserTagging.item_id');
                    $taggingUserIds = Hash::combine($taggings,'{n}.UserTagging.item_id','{n}.UserTagging.users_taggings');
                    $taggingIds = Hash::combine($taggings,'{n}.UserTagging.item_id','{n}.UserTagging.id');

                    //check privacy of tagging activity
                    $taggingActivities = $this->find('all',array(
                    	'conditions' => array(
                    		'Activity.id'=>$activityIds,
                    		'Activity.type' => 'user',
                    		'NOT' => array('Activity.user_id'=>$block_users)
                    	)
                    ));                    
                    $deleteId = array();
                    if(!empty($taggingActivities))
                    {
                        foreach($taggingActivities as $index => &$activity)
                        {
                            $notTaggedUser = (!in_array($param2,explode(',', $taggingUserIds[$activity['Activity']['id']]) ) )? true: false;
                            $isPostOwner = ($param2 == $activity['Activity']['user_id']) ? true : false;
                            $areFriends = $friend->areFriends($activity['Activity']['user_id'],$param2);

                            $activity['UserTagging']['users_taggings'] = $taggingUserIds[$activity['Activity']['id']];
                            $activity['UserTagging']['id'] = $taggingIds[$activity['Activity']['id']];
                            if($activity['Activity']['target_id'] == $param)
                            {
                                $deleteId[] = $activity['Activity']['id'];
                                continue;
                            }
                            if(!$isPostOwner)
                            {
                                switch($activity['Activity']['privacy'])
                                {
                                    case PRIVACY_FRIENDS:
                                        if(!$areFriends)
                                        {
                                            $deleteId[] = $activity['Activity']['id'];
                                            continue;
                                        }
                                        break;
                                    case PRIVACY_ME:
                                        if($notTaggedUser)
                                        {
                                            $deleteId[] = $activity['Activity']['id'];
                                            continue;
                                        }
                                        break;
                                }
                            }
                        }
                    }
                    $cond3 = array('Activity.id' => array_diff($activityIds,$deleteId) );
                }

				$cond1 = array('Activity.user_id' => $param,'Activity.type = "'. APP_USER.'"');

				if ($param != $param2) // current user != user profile page
                {
					$cond1['Activity.privacy'] = PRIVACY_EVERYONE;

                }
				$cond = array('OR' => array($cond1,
						array('Activity.target_id' => $param,
							'Activity.type' => APP_USER,
							'NOT' => array('Activity.user_id'=>$block_users) 
						)
					)
				);

                $cond2 = array();
                if ($friend->areFriends($param, $param2)){
                    $cond2 = array(
                        'Activity.user_id' => $param,
                    	'Activity.target_id'=> 0,
                        'Activity.privacy' => PRIVACY_FRIENDS
                    );
                }

                if (!empty($cond2)){
                    $cond['OR'] = array_merge($cond['OR'], array($cond2));
                }
                if(!empty($cond3)){
                    $cond['OR'] = array_merge($cond['OR'],array($cond3));
                }
                //debug($cond);die();
				break;
			case 'detail':
				$cond = array('Activity.id' => $param);
				break;

            case 'tagging':
                $cond = array('Activity.id' => $param);

                break;
            case 'Group_Group':
                $cond = array(
                    'OR' => array(
                        array('Activity.type' => $type,'Activity.action' => 'group_join', 'CONCAT(",",Activity.items,",") LIKE "%,'.$param.',%"'),
                        array('Activity.type' => $type, 'Activity.target_id' => $param),
                    )
                );
                break;
			default:
				$cond = array('Activity.type' => $type, 'Activity.target_id' => $param);
		}

		$plugins = MooCore::getInstance()->getListPluginEnable();
		$plugins[] = '';
		$cond['Activity.Plugin'] = $plugins;
                $cond['Activity.status'] = ACTIVITY_OK;
                $cond['User.active'] = true;
        //debug($cond);die();
        $settingFeedModel = MooCore::getInstance()->getModel("UserSettingFeed");
        $setting = $settingFeedModel->getListTypeUnActive();
        if ($setting && count($setting))
        {
            $cond['NOT'][] = array('Activity.action'=>$setting);
        }

		return $cond;
	}

	public function getActivitiesCount( $type = null, $param = null, $param2 = null )
	{
			$this->recursive = 2;
			$this->cacheQueries = true;
			$this->ActivityComment->cacheQueries = true;
			$this->User->cacheQueries = true;
			$cond = $this->getConditon($type , $param, $param2);

			$this->unbindModel(
					array('hasMany' => array('Like'))
			);

                        /*
			$this->ActivityComment->unbindModel(
					array('belongsTo' => array('Activity'))
			);
                         * 
                         */

			$this->User->unbindModel(
					array('belongsTo' => array('Role'))
			);

                        
                              //User Block
                $userBlockModal = MooCore::getInstance()->getModel('UserBlock');
                $blockedUsers = $userBlockModal->getBlockedUsers();

                if(!empty($blockedUsers)){
                    $str_blocked_users = implode(',', $blockedUsers);
                    $cond[] = "Activity.user_id NOT IN ($str_blocked_users)";
                    $this->hasMany['ActivityComment']['conditions'] = array( "ActivityComment.user_id NOT IN ($str_blocked_users)");
                }
            // Fixed slow query for 31k users
            if(isset($cond["User.active"])){
                $this->unbindModel(array('belongsTo' => array('User')));
                unset($cond["User.active"]);
            }

			return $this->find('count', array(
                    'conditions' => $cond,

                )
            );
	}

/*
	 * Get latest activity of $uid for $action within a day
	 * @param string $action
	 * @param int $uid - user id
     * @param string $item_type
	 * @return array $activity
	 */

	public function getRecentActivity( $action = null, $uid = null, $item_type = null )
	{
		$cond = array( 'Activity.user_id' => $uid,
                       'Activity.action' => $action,
                       'DATE_SUB(CURDATE(),INTERVAL 1 DAY) <= Activity.created'
        );

        if ( !empty( $item_type ) )
            $cond['Activity.item_type'] = $item_type;

		$activity = $this->find( 'first', array( 'conditions' => $cond	)	);

		return $activity;
	}

	/*
	 * Get item activity
	 * @param string $item_type
	 * @param int $item_id
	 * @return array $activity
	 */

	public function getItemActivity( $item_type = null, $item_id = null )
	{
		$activity = $this->find( 'first', array( 'conditions' => array( 'Activity.item_type' => $item_type,
																	    'Activity.item_id' 	 => $item_id,
																	    'Activity.params'	 => 'item',
																	    'Activity.type' 	 => 'user'
								) 	)	);
		return $activity;
	}

	/*
	 * Get comment/like activity of an item
	 * @param string $item_type
	 * @param int $item_id
	 * @return array $activity
	 */

	public function getCommentLikeActivity( $item_type = null, $item_id = null, $action = 'like_add' )
	{
		$activity = $this->find( 'first', array( 'conditions' => array( 'Activity.action' 	 => $action,
																	    'Activity.item_type' => $item_type,
																	    'Activity.item_id' 	 => $item_id
								) 	)	);
		return $activity;
	}

    public function parseLink( &$data) {
        App::uses('Validation', 'Utility');

        if(isset($data['source_url']) && !empty($data['source_url'])){
            $text = trim( $data['source_url'] );
        }else{
            $text = trim( $data['content'] );
        }
		$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,5}(\/\S*)?/";

		// Check if there is a url in the text
		preg_match($reg_exUrl, $text, $url);

		if (!is_array($url))
			return;
		$url = isset($url[0]) ? $url[0] : false;
        if($url){
            if ( strpos( $url, 'http' ) === false )
                $url = 'http://' . $url;
			
            $url = filter_var($url, FILTER_SANITIZE_URL);
            if(filter_var($url, FILTER_VALIDATE_URL) !== false){
            	//check youtube
            	if (strpos($url,'https://www.youtube.com') !== false || strpos($url,'https://youtu.be') !== false || strpos($url,'https://m.youtube.com') !== false)
            	{
            		$videoModel = MooCore::getInstance()->getModel("Video.Video");
            		$video = $videoModel->fetchVideo( 'youtube', $url);
            		if ($video && empty($video['errorMsg']))
            		{
            			$link = array(
            				'url' => $url,
            				'link' => $url,
            				'title' => $video['Video']['title'],            					
            				'description' => nl2br(CakeText::truncate(strip_tags($video['Video']['description']), 400, array('ellipsis' => '', 'html' => true))),
            				'image' => $video['Video']['thumb'] 
            			);
            			
            			$data['params'] = serialize( $link );
            			$data['action'] = 'wall_post_link';
            			return;
            		}
            	}
                //check image link
                $headers = get_headers($url, 1);
                if(!empty($headers['Content-Type']) && !is_array($headers['Content-Type'])) {
                    if (strpos($headers['Content-Type'], 'image/') !== false) {
                        $link = array(
                            'image' => $url,
                            'type' => 'img',
                        );
                        $tmp = explode('.', $url);
                        $ext = strtolower( array_pop($tmp) );
                        
                        if ( in_array($ext, array( 'jpg', 'jpeg', 'gif', 'png' ) ) )
                        {
                        	$photoModel = MooCore::getInstance()->getModel("Photo.Photo");
                        	$uid = MooCore::getInstance()->getViewer(true);
                        	$photoModel->create();
                        	$photoModel->set(array(
                        			'target_id' => 0,
                        			'type' => 'ShareLink',
                        			'user_id' => $uid,
                        			'thumbnail' => $url
                        	));
                        	
                        	$photoConfig = Configure::read('core.photo_image_sizes');
                        	Configure::write('core.photo_image_sizes','');
                        	$photoModel->save();
                        	
                        	Configure::write('core.photo_image_sizes',$photoConfig);
                        	
                        	$photo = $photoModel->read();
                        	
                        	$mooHelper = MooCore::getInstance()->getHelper('Core_Moo');
                        	$link['image'] = $mooHelper->getImageUrl($photo, array(),true);
                        	$data['photo_link_id'] = $photo['Photo']['id'];
                        }
                        $data['params'] = serialize($link);
                        $data['action'] = 'wall_post_link';
                        return;
                    }
                }
            	
                $sContent = MooCore::getInstance()->getUrlContent($url, array(), 'GET');
                if( function_exists('mb_convert_encoding') )
                {
                    $sContent = mb_convert_encoding($sContent, 'HTML-ENTITIES', "UTF-8");
                }
                error_reporting(0);
                $oDoc = new DOMDocument();
                $oDoc->loadHTML($sContent);
                error_reporting(E_ALL);
                if (($oTitle = $oDoc->getElementsByTagName('title')->item(0)) && !empty($oTitle->nodeValue))
                {
                    $link['title'] = strip_tags($oTitle->nodeValue);
                }
                $oXpath = new DOMXPath($oDoc);
                $oMeta = $oXpath->query("//meta[@name='description']")->item(0);
                if (method_exists($oMeta, 'getAttribute'))
                {
                    $sMeta = $oMeta->getAttribute('content');
                    if (!empty($sMeta))
                    {
                        $link['description'] = strip_tags($sMeta);
                    }
                }
            	if (!isset($link['description']))
                {
	                $oMeta = $oXpath->query("//meta[@property='og:description']")->item(0);
	                if (method_exists($oMeta, 'getAttribute'))
	                {
	                    $sMeta = $oMeta->getAttribute('content');
	                    if (!empty($sMeta))
	                    {
	                        $link['description'] = strip_tags($sMeta);
	                    }
	                }
                }

                $oMeta = $oXpath->query("//meta[@property='og:image']")->item(0);
                if (method_exists($oMeta, 'getAttribute'))
                {
                    $default_image = strip_tags($oMeta->getAttribute('content'));
                }
                $oMeta = $oXpath->query("//link[@rel='image_src']")->item(0);
                if (method_exists($oMeta, 'getAttribute'))
                {
                    if (empty($default_image))
                    {
                        $default_image = strip_tags($oMeta->getAttribute('href'));
                    }
                }
                if (!isset($default_image))
                {
                    $oImages = $oDoc->getElementsByTagName('img');
                    $iIteration = 0;
                    foreach ($oImages as $oImage)
                    {
                        $sImageSrc = $oImage->getAttribute('src');

                        if (substr($sImageSrc, 0, 7) != 'http://' && substr($sImageSrc, 0, 1) != '/')
                        {
                            continue;
                        }

                        if (substr($sImageSrc, 0, 2) == '//')
            {
                            continue;
                        }

                        $iIteration++;

                        if (substr($sImageSrc, 0, 1) == '/')
                        {
                            $aParts = parse_url($url);
                            if (!isset($aParts['host']))
                            {
                                continue;
                            }
                            $sImageSrc = 'http://' . $aParts['host'] . $sImageSrc;
                        }

                        if ($iIteration === 1 && empty($aReturn['default_image']))
                        {
                            $default_image = strip_tags($sImageSrc);
                            break;
                        }

                        if ($iIteration > 10)
                {
                            break;
                        }

                    }
                }                
                if ( isset($default_image) ){
                	if(!filter_var($default_image, FILTER_VALIDATE_URL)){
                		$default_image = 'http:' . $default_image;
                	}
                	
                	$tmp = explode('.', $default_image);
                	$ext = strtolower( array_pop($tmp) );
                	
                	if ( in_array($ext, array( 'jpg', 'jpeg', 'gif', 'png' ) ) )
                	{
                		$image_name = md5( time() ) . '.' . $ext;
                		
                		$image = MooCore::getInstance()->getHtmlContent($default_image);
                		$image_loc = WWW_ROOT . 'uploads/links/' . $image_name;
                		if ($image)
                		{
                			file_put_contents($image_loc, $image);
                			// resize image
                			if(exif_imagetype($image_loc)) {
                				App::import('Vendor', 'phpThumb', array('file' => 'phpThumb/ThumbLib.inc.php'));
                				$thumb = PhpThumbFactory::create($image_loc, array('jpegQuality' => 100));
                				$thumb->resize(650, 650)->save($image_loc);
                				
                				$link['image'] = $image_name;
                			}else{
                				$link['image'] = $default_image;
                			}
                		}
                	}
                	
                }
		$link['url'] = $url;
                $link['link'] = $url;
                $data['params'] = serialize( $link );
                $data['action'] = 'wall_post_link';
            }
            
        }
        }

    public function getActivityHashtags($qid,$uid ,$limit = RESULTS_LIMIT,$page = 1){

        $this->recursive = 2;
        $this->cacheQueries = true;
        $this->ActivityComment->cacheQueries = true;
        $this->User->cacheQueries = true;
        $cond = $this->getConditon('everyone',$uid);

        $this->unbindModel(
            array('hasMany' => array('Like'))
        );

        $this->ActivityComment->unbindModel(
            array('belongsTo' => array('Activity'))
        );

        $this->User->unbindModel(
            array('belongsTo' => array('Role'))
        );

        $plugins = MooCore::getInstance()->getListPluginEnable();
        $plugins[] = '';

        $cond['Activity.id'] = $qid;
        $cond['Activity.privacy'] = PRIVACY_EVERYONE ;
        $cond['Activity.Plugin'] = $plugins;
        $activities = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );

        App::import('Model', 'Comment');
        $comment = new Comment();

        App::import('Model', 'Like');
        $like = new Like();

        // save the items to activities array
        foreach ( $activities as $key => &$activity )
        {
            // item activity
            if ( $activity['Activity']['params'] == 'item' )
            {
                $item_type = $activity['Activity']['item_type'];

                // get item's comments
                $activity['ItemComment'] = $comment->find('all', array(  'conditions' => array(
                    'Comment.target_id' => $activity['Activity']['item_id'],
                    'Comment.type'      => $item_type ),
                    'order' => 'Comment.id desc',
                    'limit' => RESULTS_LIMIT
                )  );


                // get items' likes
                $activity['Likes'] = $like->find('list', array( 'conditions' => array(
                    'Like.target_id' => $activity['Activity']['item_id'],
                    'Like.type'      => $item_type ),
                    'fields' => array( 'Like.user_id', 'Like.thumb_up' )
                ) );
            }
        }

        return $activities;
    }
    
    
    public function getActivitySearch($uid, $text ='' ,$limit = RESULTS_LIMIT,$page = 1){
    	
    	$this->recursive = 2;
    	$this->cacheQueries = true;
    	$this->ActivityComment->cacheQueries = true;
    	$this->User->cacheQueries = true;
    	$cond = $this->getConditon('everyone',$uid,null,$text);

    	$this->unbindModel(
    			array('hasMany' => array('Like'))
    			);
    	
    	$this->ActivityComment->unbindModel(
    			array('belongsTo' => array('Activity'))
    			);
    	
    	$this->User->unbindModel(
    			array('belongsTo' => array('Role'))
    			);
    	
    	$plugins = MooCore::getInstance()->getListPluginEnable();
    	$plugins[] = '';
    	
    	$cond['Activity.privacy'] = PRIVACY_EVERYONE ;
    	$cond['Activity.Plugin'] = $plugins;
    	$activities = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
    	
    	App::import('Model', 'Comment');
    	$comment = new Comment();
    	
    	App::import('Model', 'Like');
    	$like = new Like();
    	
    	// save the items to activities array
    	foreach ( $activities as $key => &$activity )
    	{
    		// item activity
    		if ( $activity['Activity']['params'] == 'item' )
    		{
    			$item_type = $activity['Activity']['item_type'];
    			
    			// get item's comments
    			$activity['ItemComment'] = $comment->find('all', array(  'conditions' => array(
    					'Comment.target_id' => $activity['Activity']['item_id'],
    					'Comment.type'      => $item_type ),
    					'order' => 'Comment.id desc',
    					'limit' => RESULTS_LIMIT
    			)  );
    			
    			
    			// get items' likes
    			$activity['Likes'] = $like->find('list', array( 'conditions' => array(
    					'Like.target_id' => $activity['Activity']['item_id'],
    					'Like.type'      => $item_type ),
    					'fields' => array( 'Like.user_id', 'Like.thumb_up' )
    			) );
    		}
    	}
    	
    	return $activities;
    }
    
    public function isMentioned($uid,$activity_id){
        $activity = $this->findById($activity_id);
        preg_match_all(REGEX_MENTION,$activity['Activity']['content'],$matches);
        if(!empty($matches)){
            if(in_array($uid, $matches[1]))
                return true;
        }
        return false;
    }
    
	public function addBlockConditionTarget($cond = array()) {
		$userBlockModal = MooCore::getInstance()->getModel('UserBlock');               
		$blockedUsers = $userBlockModal->getBlockedUsers();
		if(!empty($blockedUsers)){
			$str_blocked_users = implode(',', $blockedUsers);
			$cond[] = "Activity.target_id NOT IN ($str_blocked_users)";
		}
		
		return $cond;
    }
}
 
