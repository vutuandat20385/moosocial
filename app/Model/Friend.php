<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class Friend extends AppModel {
		
	public $belongsTo = array( 'User'  => array('counterCache' => true	));
    public $listFriends = array();

	/*
	 * Return a list of friends for dropdown list
	 * @param int $uid
	 * @param array $excludes an array of user ids to exclude
	 */
	public function getFriendsList( $uid, $excludes = array() )
	{
		$this->unbindModel(
			array('belongsTo' => array('User'))
		);

		$this->bindModel(
			array('belongsTo' => array(
					'User' => array(
						'className' => 'User',
						'foreignKey' => 'friend_id'
					)
				)
			)
		);

		$cond = array( 'Friend.user_id' => $uid, 'User.active' => 1 );
		
		if ( !empty( $excludes ) )
			$cond['NOT'] = array( 'Friend.friend_id' => $excludes );
		
		$friends = $this->find( 'all', array( 'conditions' => $cond, 
											  'fields' 	   => array( 'User.id', 'User.name', 'User.avatar' ),
											  'order'	   => 'User.name asc'
							) 	); // have to do this because find(list) does not work with bindModel
		$friend_options = array();

		foreach ($friends as $friend)
			$friend_options[$friend['User']['id']] = $friend['User']['name'];

		return $friend_options;
	}
        
        public function getFriendListAsString($uid){
            $sFriendsList = '';
            $aFriendListId =  array_keys($this->getFriendsList($uid));
            $sFriendsList = implode(',',$aFriendListId);
            return $sFriendsList;
        }

        /*
	 * Return an array of friend ids
	 */
	public function getFriends( $uid )
	{
        if (isset($this->listFriends[$uid]))
            return $this->listFriends[$uid];
        $friends_cache = "FriendModel.getFriends.{$uid}";
        $friends = Cache::read($friends_cache,"10_min");
        if(empty($friends)){
		    $friends = $this->find( 'list' , array( 'conditions' => array( 'Friend.user_id' => $uid ),
												'fields' => array( 'friend_id' ) 
							) );
            Cache::write($friends_cache,$friends,"10_min");
        }
        $this->listFriends[$uid] = $friends;
		return $friends;
	}
	
	/*
	 * Return a list of friends for displaying
	 */
	public function getUserFriends( $uid, $page = 1, $limit = RESULTS_LIMIT )
	{
		$this->unbindModel(
			array('belongsTo' => array('User'))
		);

		$this->bindModel(
			array('belongsTo' => array(
					'User' => array(
						'className' => 'User',
						'foreignKey' => 'friend_id'
					)
				)
			)
		);
                $userBlockModal = MooCore::getInstance()->getModel('UserBlock');               
                $blockedUsers = $userBlockModal->getBlockedUsers();
		$friends = $this->find('all', array( 'conditions' => array( 'Friend.user_id' => $uid, 'User.active' => 1, 'NOT' => array('Friend.friend_id' => $blockedUsers)), 
		                                     'order' => 'User.name asc',
											 'limit' => $limit, 
											 'page' => $page)
		);

		return $friends;
	}
    
    /*
     * Return a list of friends for searching
     */
    public function searchFriends( $uid, $q , $page = 0)
    {
        $this->unbindModel(
            array('belongsTo' => array('User'))
        );

        $this->bindModel(
            array('belongsTo' => array(
                    'User' => array(
                        'className' => 'User',
                        'foreignKey' => 'friend_id'
                    )
                )
            )
        );
        if (!$page)
        {
	        $friends = $this->find( 'all', array( 'conditions' =>  array( 'Friend.user_id' => $uid, 
	                                                                      'User.active' => 1,
	        															  'User.name LIKE' => "%" . $q . "%"), 
	                                              //'fields'     => array( 'User.id', 'User.name', 'User.avatar' ),
	                                              'order'      => 'User.name asc'
	                            )   ); 
        }
        else
        {
        	$userBlockModal = MooCore::getInstance()->getModel('UserBlock');               
            $blockedUsers = $userBlockModal->getBlockedUsers();
        	$friends = $this->find( 'all', array( 'conditions' =>  array( 'Friend.user_id' => $uid, 
	                                                                      'User.active' => 1,
        																 'NOT' => array('Friend.friend_id' => $blockedUsers),
        																  'User.name LIKE' => "%" . $q . "%"), 
	                                              'order'      => 'User.name asc',
        									'limit' => RESULTS_LIMIT, 
											 'page' => $page
	                            )   );
        }
        return $friends;
    }
	
	/*
	 * Get friend suggestions of $uid (mutual friends)
	 * @param int $uid
	 * @param boolean $bigList - view all list or not (right column block)
	 * @return array $suggestions
	 */
	
	public function getFriendSuggestions($uid, $bigList = false, $limit = 2) {
            // get friends of current user
            $friends = $this->getFriends($uid);
            // Fixed with admin users with more than 14k friend then
            // get query 10s with amazon RDS 2 core 4g ram
            if (count($friends) > FRIEND_LIMIT ){
                return null;
             }
            $suggestions = array();

            if (!empty($friends)) {
                App::import('Model', 'FriendRequest');

                // get friend requests of current users
                $req = new FriendRequest();
                $requests = $req->find('list', array('conditions' => array('FriendRequest.sender_id' => $uid),
                    'fields' => array('FriendRequest.user_id')
                        ));
                $be_requests = $req->find('list', array('conditions' => array('FriendRequest.user_id' => $uid),
                    'fields' => array('FriendRequest.sender_id')
                        ));
                
                // merge with friends list
                $userBlockModal = MooCore::getInstance()->getModel('UserBlock');               
                $blockedUsers = $userBlockModal->getBlockedUsers();
                $not_in = array_merge($friends, $requests, $be_requests,$blockedUsers);
                $not_in[] = $uid;
                
                $this->unbindModel(
                        array('belongsTo' => array('User'))
                );

                $this->bindModel(
                        array('belongsTo' => array(
                                'User' => array(
                                    'className' => 'User',
                                    'foreignKey' => 'friend_id'
                                )
                            )
                        )
                );
                $suggestions_cache = "suggestions.{$uid}";
                $suggestions = Cache::read($suggestions_cache,"10_min");
                if(empty($suggestions)){
                    if ($bigList) {
                        $suggestions = $this->find('all', array('conditions' => array('Friend.user_id' => $friends,
                            'User.active' => 1,
                            'NOT' => array('Friend.friend_id' => $not_in)
                        ),
                            //'fields' => array('DISTINCT User.id', 'User.*',
                            'fields' => array('DISTINCT User.id', 'User.name','User.privacy','User.avatar','User.gender',
                                //'(SELECT count(*) FROM ' . $this->tablePrefix . 'friends WHERE user_id = User.id AND friend_id IN (' . implode(',', $friends) . ') ) as count'
                            ),
                            'order' => 'count desc',
                            'limit' => RESULTS_LIMIT * 2));
                    } else {
                        $suggestions = $this->find('all', array('conditions' => array('Friend.user_id' => $friends,
                            'User.active' => 1,
                            'NOT' => array('Friend.friend_id' => $not_in),
                        ),
                            //'fields' => array('DISTINCT User.id', 'User.*',
                            'fields' => array('DISTINCT User.id', 'User.name','User.privacy','User.avatar','User.gender',
                                //'(SELECT count(*) FROM ' . $this->tablePrefix . 'friends WHERE user_id = User.id AND friend_id IN (' . implode(',', $friends) . ') ) as count'
                            ),
                            'limit' => $limit,
                            'order' => 'rand()'
                        ));
                    }
                    Cache::write($suggestions_cache,$suggestions,"10_min");
                }

                // Fixed slow query for 31k users
                for($i=0;$i<count($suggestions);$i++){
                    $mutual_cache = "mutual.{$uid}.{$suggestions[$i]['User']['id']}";
                    $count = Cache::read($mutual_cache,"20_min");
                    if(empty($count)){
                        $count = $this->find('count',array(
                            'conditions' => array(
                                'Friend.user_id' => $suggestions[$i]['User']['id'],
                                'Friend.friend_id' => $friends,
                            )
                        ));
                        Cache::write($mutual_cache,$count,"20_min");
                    }
                    $suggestions[$i][0]['count'] = $count;
                }
            }

            return $suggestions;
        }

        public function getMutualFriends( $uid1, $uid2, $limit = RESULTS_LIMIT, $page = 1 )
	{
		// get friends of the first user
		$friends = $this->getFriends( $uid1 );
		$mutual_friends = array();			
		
		if ( !empty( $friends ) )
		{			
			$this->unbindModel(
				array('belongsTo' => array('User'))
			);
	
			$this->bindModel(
				array('belongsTo' => array(
						'User' => array(
							'className' => 'User',
							'foreignKey' => 'friend_id'
						)
					)
				)
			);	
                        $userBlockModal = MooCore::getInstance()->getModel('UserBlock');               
                        $blockedUsers = $userBlockModal->getBlockedUsers();
			$mutual_friends = $this->find('all', array('conditions' => array( 'Friend.user_id' => $uid2, 
																		   	  'User.active' => 1, 
																		  	  'Friend.friend_id' => $friends, 'NOT' => array('Friend.friend_id' => $blockedUsers)																		   	  
																		 ), 																		 
													   'fields' => array( 'DISTINCT User.id', 'User.name', 'User.avatar', 'User.friend_count', 'User.photo_count', 'User.gender','User.*'),
													   'limit' => $limit,
													   'page' => $page
			)	);
		}
		
		return $mutual_friends;
	}
        
    // auto add friendList to user uid
    public function autoFriends($uid = null, $friendList = array()) {

        if (!$uid || empty($friendList)) {
            return false;
        }

        foreach ($friendList as $friend_id) {
            if (count($this->getFriends($friend_id)) > FRIEND_LIMIT) { continue;}
            $friendModel = MooCore::getInstance()->getModel('Friend');
            
            // insert to friends table
            $friendModel->create();
            $friendModel->save(array('user_id' => $uid, 'friend_id' => $friend_id));
            $friendModel->create();
            $friendModel->save(array('user_id' => $friend_id, 'friend_id' => $uid));

            // insert into activity feed
            $activityModel = MooCore::getInstance()->getModel('Activity'); 
            $activity = $activityModel->getRecentActivity('friend_add', $uid);

            if (!empty($activity)) {
                // aggregate activities
                $user_ids = explode(',', $activity['Activity']['items']);

                if (!in_array($friend_id, $user_ids)){
                    $user_ids[] = $friend_id;
                }
                    
                $activityModel->id = $activity['Activity']['id'];
                $activityModel->save(array('items' => implode(',', $user_ids),
                    'params' => '',
                    'privacy' => 1,
                    'query' => 1
                ));
            }
            else {
                $activityModel->create();
                $activityModel->save(array('type' => 'user',
                    'action' => 'friend_add',
                    'user_id' => $uid,
                    'item_type' => APP_USER,
                    'items' => $friend_id
                ));
            }
        }
    }

    /*
	 * Are we friends?
	 */
	public function areFriends( $uid1, $uid2 )
	{
		$this->cacheQueries = true;
		
		$count = $this->find( 'count', array( 'conditions' => array( 'Friend.user_id' => $uid1, 'Friend.friend_id' => $uid2 ) ) );
		return $count;		
	}
    public function afterSave($created, $options = array()){
        Cache::delete('user_friends_'.$this->data['Friend']['user_id']);
        Cache::delete('user_friend_prefetch_'.$this->data['Friend']['user_id']);
        $friends = $this->findAllByUserId($this->data['Friend']['user_id']);
        foreach($friends as &$friend)
        {
            Cache::delete('mutual_friends_'.$this->data['Friend']['user_id'].'_'.$friend['Friend']['friend_id']);
            Cache::delete('mutual_friends_'.$friend['Friend']['friend_id'].'_'.$this->data['Friend']['user_id']);
        }

        if ($created)
        {
            $followModel = MooCore::getInstance()->getModel("UserFollow");
            $followModel->add($this->data['Friend']['user_id'],$this->data['Friend']['friend_id']);
        }
        $this->getEventManager()->dispatch(new CakeEvent('Model.Friend.afterSave', $this));
    }
    public function beforeDelete($cascade = true){
        $uid = $this->field('user_id');
        Cache::delete('user_friends_'.$this->field('user_id'));
        Cache::delete("suggestions.{$uid}","10_min");
        Cache::delete("FriendModel.getFriends.{$uid}","10_min");

        $friends = $this->findAllByUserId($this->field('user_id'));
        foreach($friends as &$friend)
        {
            Cache::delete('mutual_friends_'.$this->field('user_id').'_'.$friend['Friend']['friend_id']);
            Cache::delete('mutual_friends_'.$friend['Friend']['friend_id'].'_'.$this->field('user_id'));
        }
        $this->getEventManager()->dispatch(new CakeEvent('Model.Friend.beforeDelete', $this));   }

    // get friend list from $user_id
}
 