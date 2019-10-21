<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('AuthComponent', 'Controller/Component');
App::uses('MooPasswordHasher', 'Controller/Component/Auth');
class User extends AppModel
{
	// public $useTable = 'lophocs';
	public $belongsTo = array( 'Role' );

    public $actsAs = array(
        'MooUpload.Upload' => array(
            'avatar' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}users{DS}{field}{DS}',
                'thumbnailSizes' => array(
                    'size' => array('50_square','100_square','200_square', '600')
                ),
            ),
            'photo' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}users{DS}{field}{DS}',
                'thumbnailSizes' => array(
                    'size' => array('50_square','100_square','200_square')
                ),
            )
        ),
        'Storage.Storage' => array(
            //'type'=>'users',//array('users','moo_covers'),
            //'field'=>'avatar',
            'type'=>array('users'=>'avatar','moo_covers'=>'cover'),
        ),
    );
    
    public $mooFields = array('title','href','plugin','type','url', 'thumb');
    		
	public $validate = array(  
			'name' => 	array( 	 
				'rule' => 'notBlank',
				'message' => 'Name is required'
			),
			'email' => 	array( 	 			
				'checkEmail' => array(
					'rule' => array('checkEmail'),
					'message' => 'Email must be a valid email'
				),
				'uniqueEmail' => array(
					  'rule' => 'isUnique',
					  'message' => 'Email already exists'
			    ),
			    'required' => array(
					  'rule' => 'notBlank',
					  'required' => true,		  
					  'on' => 'create',
					  'message' => 'Email is required'
				)
			),
			'password' => array( 
				'rule' => array('minLength', 6),
				'allowEmpty' => false,
				'message' => 'Password must have at least 6 characters'
			),
			'password2' => array( 
				'identicalFieldValues' => array(
					  'rule' => array('identicalFieldValues', 'password' ),
					  'message' => 'Passwords do not match'
				)	
			),
			'birthday' => array( 	 
				'required' => array('rule' => 'notBlank',
                                                'message' => 'Birthday is required'),
                                'age' => array(
                                        'rule' => 'checkAge'
                                )
			),
			'gender' =>	array( 	 
				'rule' => 'notBlank',
				'message' => 'Gender is required'
			),
			'username' => 	array(
				'blankUsername' =>	array(
					'rule' => 'notBlank',
					'message' => 'Username is required'
				),
				'lengthUsername' => array(
					'allowEmpty' => true,
					'rule'    => array('between', 5, 50),
					'message' => 'Username must be between 5 and 50 characters long.'
				),
				'checkUserName' => array(
					  'allowEmpty' => true,
					  'rule' => array('checkUserName'),
					  'message' => 'Username must only contain alphanumeric characters (no special chars)'
				),				
				'uniqueUsername' => array(
					  'rule' => 'isUnique',
					  'message' => 'Username already exists'
			    ),
				'checkRestricted' => array(
					'allowEmpty' => true,
					'rule' => array('checkRestricted'),
					'message' => 'Username is restricted'
				),
			),
			'timezone' => 	array(
				 'rule' => 'notBlank',
				 'message' => 'Timezone is required'
			),
			'about' => 	array(
				 'rule' => 'notBlank',
				 'message' => 'About is required'
			),
	);
	private $_users = array();
	
	function __construct($id = false, $table = null, $ds = null)
	{
		if (!Configure::read('core.require_birthday'))
		{
			unset($this->validate['birthday']);
		}
		if (!Configure::read('core.require_gender'))
		{
			unset($this->validate['gender']);
		}

		if (!Configure::read('core.require_username'))
		{
			unset($this->validate['username']['blankUsername']);
		}
		if (!Configure::read('core.require_timezone'))
		{
			unset($this->validate['timezone']);
		}

		if (!Configure::read('core.require_about'))
		{
			unset($this->validate['about']);
		}

		parent::__construct($id, $table, $ds);
	}
	
	public function checkEmail($values)
	{
		if (isset($values['email']) && (filter_var($values['email'], FILTER_VALIDATE_EMAIL)))
		{
			return true;
		}
		return false;
	}
	
	public function checkUserName($values)
	{
		if (!empty($values['username']))
		{
			return !is_numeric( $values['username']) && ctype_alnum( $values['username']);
		}
		return true;
	}
	
	public function afterFind($results, $primary = false) {
		if (count($results))
		{
			$profileModel = MooCore::getInstance()->getModel("ProfileType");
			$ids = array();
			$profiles = array();
			foreach ($results as $result)
			{
				if (isset($result['User']['profile_type_id']))
				{
					$ids[] = $result['User']['profile_type_id'];
				}
			}
			$ids = array_unique($ids);
			if (count($ids))
			{
				$profiles = $profileModel->getProfileTypesForUserModel($ids);
				$tmp = array();
				foreach ($profiles as $profile)
				{
					$tmp[$profile['ProfileType']['id']] = $profile;
				}
				$profiles = $tmp;
			}
			
			foreach ($results as &$result)
			{
				if (isset($result['User']['profile_type_id']))
				{
					if (isset($profiles[$result['User']['profile_type_id']]))
					{
						$result['ProfileType'] = $profiles[$result['User']['profile_type_id']]['ProfileType'];
					}
					else
					{
						$result['ProfileType'] = array('id'=>0);
					}
				}
			}
		}
		return $results;
	}
	

	// Identical field validation rule

	public function checkRestricted($values)
	{
		if (!empty($values['username']))
		{
			$restricted_usernames = Configure::read('core.restricted_usernames');
			if ( !empty($restricted_usernames) )
			{
				$usernames = explode( "\n", $restricted_usernames );

				foreach ( $usernames as $un )
				{
					if ( !empty( $un ) && ( trim($un) == $values['username']) )
					{
						return false;
					}
				}
			}
		}
		return true;
	}
	
	public function identicalFieldValues( $field=array(), $compare_field=null )
    {
        foreach( $field as $key => $value ){
            $v1 = $value;
            $v2 = $this->data[$this->name][ $compare_field ];
            if($v1 !== $v2) {
                return FALSE;
            } else {
                continue;
            }
        }
        return TRUE;
    }

	function unserialize_php($session_data) {
		$return_data = array();
		$offset = 0;
		while ($offset < strlen($session_data)) {
			if (!strstr(substr($session_data, $offset), "|")) {
				throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
			}
			$pos = strpos($session_data, "|", $offset);
			$num = $pos - $offset;
			$varname = substr($session_data, $offset, $num);
			$offset += $num + 1;
			$data = unserialize(substr($session_data, $offset));
			$return_data[$varname] = $data;
			$offset += strlen(serialize($data));
		}
		return $return_data;
	}
	/*
	 * Get current online users
	 * @param int $interval - interval to check
	 * @return array $res
	 */
	public function getOnlineUsers( $limit = 12, $interval = 1200 )
        {
            $userids = array();
            $guests = 0;
            $time = time() - intval($interval);
            $session = $this->query('SELECT DISTINCT user_id FROM ' . $this->tablePrefix . 'cake_sessions WHERE expires > ' . $time);

            $userBlockModal = MooCore::getInstance()->getModel('UserBlock');               
            $blockedUsers = $userBlockModal->getBlockedUsers();
            foreach ($session as $session) {
				/*
                $data = $session[$this->tablePrefix.'cake_sessions']['data'];


                $data = $this->unserialize_php($data);

                if (empty($data["Auth"]['User']['id'])){
                    $guests++;
                }else{
                    if (!in_array($data["Auth"]['User']['id'],$userids)){
                        $userids[] = $data["Auth"]['User']['id'];
                    }
                }
				*/
				$userid = $session[$this->tablePrefix.'cake_sessions']['user_id'];
				if (is_numeric($userid) && !in_array($userid, $blockedUsers)){
					$userids[] =  $userid ;
				}else{
					$guests++;
				}
            }


            $members = array();
            if (!empty($userids)) {               
                $members = $this->find('all', array('conditions' => array_merge(array('User.id' => $userids, 'User.hide_online' => 0), $this->addBlockCondition()), 'limit' => intval($limit)));
            }

            $total = $guests + count($userids);
            
            $res = array('guests' => $guests,
                'members' => $members,
                'total' => $total,
                'userids' => $userids
            );
            
            return $res;
        }

       /*
	 * Get array of users based on $conditions
	 * @param int $page
	 * @param array $conditions
	 * @return array $users
	 */
	public function getUsers( $page = 1, $conditions = null, $limit = RESULTS_LIMIT )
	{
		if ( empty( $conditions ) )
			$conditions = array( 'User.active' => 1 );
		$joins = array();
		if (isset($conditions['joins']))
		{
			$joins = $conditions['joins'];
			unset($conditions['joins']);
		}
		$conditions = $this->addBlockCondition($conditions);	
		$users = $this->find('all', array( 'conditions' => $conditions,
										   'joins' => $joins,
										   'limit' 		=> $limit,
										   'page'  		=> $page,
										   'order' 		=> 'User.id desc'
								)	);
		return $users;
	}

    /*
	 * Get all users
	 */

    public function getAllUser($q = '',$notIn = array(),$limit = RESULTS_LIMIT,$page = 1)
    {
        $users = $this->find( 'all', array( 'conditions' =>array_merge( array(

                'User.active' => 1,
        		'User.name LIKE' => $q . "%",
                'NOT' => array('User.id' => $notIn),
            ), $this->addBlockCondition()),
            'limit' => $limit,
            'page' => $page,
            'order'      => 'User.name asc'

        )   );

        return $users;

    }
	
	
	/*
	 * Remove user's avatar files
	 * @param object $user
	 */
	public function removeAvatarFiles( $user )
	{
		$path = WWW_ROOT . 'uploads' . DS . 'avatars';
		
		if ($user['photo'] && file_exists($path . DS .$user['photo']))
			unlink($path . DS . $user['photo']);
			
		if ($user['avatar'] && file_exists($path . DS .$user['avatar']))
			unlink($path . DS . $user['avatar']);
	}
    
    /*
     * Remove user's cover file
     * @param object $user
     */
    public function removeCoverFile( $user )
    {
        $path = WWW_ROOT . 'uploads' . DS . 'covers';
        
        if ($user['cover'] && file_exists($path . DS .$user['cover']))
            unlink($path . DS . $user['cover']);
    }
	
	public function getTodayBirthday()
	{
		$birthday_users = Cache::read('birthday_users');
        
        if ( !is_array( $birthday_users ) ) 
        {
            $today = date('m-d');
            $ids = $this->find( 'list',
                array(
                    'conditions' => array( 'active' => 1, 'SUBSTRING(birthday, 6)' => $today ),
                    'fields' => array('User.id'),
                    'recursive' => -1,
                    )
            );
            $birthday_users = $this->getAllUser($ids);
            Cache::write('birthday_users', $birthday_users);
        }
		
		return $birthday_users;
	}

    public function getTodayBirthdayFriend($userId,$timezone)
    {
        //date_default_timezone_set($timezone);
        $today = new DateTime('now', new DateTimeZone($timezone));
        $today = $today->format('m-d');
        $todayBirthdayFriend_cache = "UserModel.getTodayBirthdayFriend.{$userId}";
        $birthday_users = Cache::read($todayBirthdayFriend_cache,"1_hour");
        if(empty($birthday_users))
        {
            $ids = $this->find('list', array(
                'joins' => array(
                    array(
                        'table' => 'friends',
                        'alias' => 'Friend',
                        'type' => 'INNER',
                        'conditions' => array(
                            'User.id = Friend.friend_id'
                        )
                    )
                ),
                'conditions' => array(
                    'Friend.user_id' => $userId,
                    'active' => 1,
                    'SUBSTRING(birthday, 6)' => $today,
                ),
                'fields' => array('User.id'),
                'recursive' => -1,
            ));
            $birthday_users = $this->getAllUsers($ids);
            Cache::write($todayBirthdayFriend_cache,$birthday_users,"1_hour");
        }

        return $birthday_users;
    }
	
	public function getLatestUsers( $limit = 10 )
	{
        $latestUsers_cache = "UserModel.getLatestUsers";
        $users = Cache::read($latestUsers_cache,"1_day");

        if(empty($users)){
            $condition = array();
            $ids = $this->find( 'list', array( 'conditions' => array_merge(array( 'active' => 1 ),$this->addBlockCondition()),
                'order' => 'User.id desc',
                'limit' => $limit,
                'fields' => array('User.id'),
                'recursive' => -1,
            )	);
            $users = $this->getAllUsers($ids);
            Cache::write($latestUsers_cache,$users,"1_day");
        }


        return $users;
	}
	
	public function getFeaturedUsers($limit = 10)
	{

        $ids = $this->find( 'list', array( 'conditions' => array_merge(array( 'active' => 1, 'featured' => 1 ),$this->addBlockCondition()),
            'order' => 'User.id desc',
            'limit' => $limit,
            'fields' => array('User.id'),
            'recursive' => -1,
        )	);

		return $this->getAllUsers($ids);
	}


    /**
     * @param $ids
     * @return array of users with format [[User => array]]
     */
    public function getAllUsers($ids){
        $user = array();
        if (count($ids) > 0){
            foreach($ids as $id){
                array_push($user,$this->findById($id));
            }
        }
        return $user;
    }
	public function afterSave($created, $options = array()) {
        $cakeEvent = new CakeEvent('Model.User.afterSave', $this, array('id' => $this->field('id'),'created' => $created));
        $this->getEventManager()->dispatch($cakeEvent);
        //delete cache when user update something
        if(!$created){
            Cache::clearGroup('blog');
            Cache::clearGroup('event');
            Cache::clearGroup('group');
            Cache::clearGroup('photo');
            Cache::clearGroup('video');
        }

        if(isset($this->id)){
            $uId = $this->id;
        }
        if(isset($this->data['User']['id'])){
            $uId = $this->data['User']['id'];
        }
        if ( !empty( $uId)){
            Cache::delete("user.findById.".$uId);
        }
    }

    public function getHref($row)
    {
    	$request = Router::getRequest();
    	if (!empty( $row['username']))
    		return $request->base . '/-' . $row['username'];
    	elseif (!empty($row['id']))
    		return $request->base.'/users/view/'.$row['id'];
    		
    	return false;
    }
    
    public function getTitle(&$row)
    {
    	if (isset($row['name']))
    	{
    		$row['name'] = htmlspecialchars($row['name']);
    		return $row['name'];
    	}
    }
    
    public function getThumb($row)
    {
        return 'avatar';
    }
    
     public function checkAge($check) {
        $viewer = MooCore::getInstance()->getViewer();
        if (!empty($viewer) && $viewer['Role']['is_admin']) {
            return true;
        }
        
        $min_age = Configure::read('core.min_age_restriction');   
        $max_age = Configure::read('core.max_age_restriction');
        
        $dt = new DateTime($check['birthday']);
        $dt1 = new DateTime($check['birthday']);
        $current_date = new DateTime(date('Y-m-d'));
        if(!empty($min_age)){           
            date_add($dt, date_interval_create_from_date_string("$min_age years"));
            if ($current_date <= $dt){ 
                return __('In order to have an account, your age must be above %s.', $min_age);                
            }
        }
        
        if(!empty($max_age)){   		
            date_add($dt1, date_interval_create_from_date_string("$max_age years"));
            if ($current_date >= $dt1){ 
                return __('In order to have an account, your age must be below %s.', $max_age);                
            }
        }
       
        return true;
    }

	public function checkSettingNotification($uid,$type)
	{
		if (!$uid)
			return false;

		$user = null;

		if (isset($this->_users[$uid]))
		{
			$user = $this->_users[$uid];
		}
		else
		{
			$user = $this->findById($uid);
			$this->_users[$uid] = $user;
		}

		if (!$user)
			return false;

		$notification_setting = json_decode($user['User']['notification_setting'],true);

		if (!isset($notification_setting[$type]))
			return true;

		return $notification_setting[$type];
	}

    public function beforeSave($options = array())
    {
    	Cache::delete("UserModel.getLatestUsers","1_day");
        if ( !empty( $this->data['User']['password'] ) ){
            $salt = $this->getSalt();
            $find = $this->find('count',array('condition'=>array('User.salt'=>$salt)));
            while (!$find)
            {
                $salt = $this->getSalt();
                $find = $this->find('count',array('condition'=>array('User.salt'=>$salt)));
            }

            $passwordHasher = new MooPasswordHasher();
            $this->data['User']['password'] = $passwordHasher->hash(
                $this->data['User']['password'],$salt
            );

            $this->data['User']['salt'] = $salt;
        }

        return true;
        if ( !empty( $this->data['User']['password'] ) )
            $this->data['User']['password'] = md5( $this->data['User']['password'] . Configure::read('Security.salt') );

        return true;
    }

    // Hash the password before saving user data

	public function getSalt()
	{
		$random = md5(time().rand());
		return substr($random,0,4);
	}

    public function beforeDelete($cascade = true){
        Cache::delete("user.findById.".$this->id);
    }
	
	protected $_users_cache = array();
	
    public function __call($name, $arguments)
    {

        if ($name == "findById"){
			if (!isset($this->_users_cache[$arguments[0]]))
			{
				$this->_users_cache[$arguments[0]] = parent::__call($name, $arguments);
			}
			return $this->_users_cache[$arguments[0]];
            /*$keyCached = "user.findById.".$arguments[0];
            $result = Cache::read($keyCached);
            if (!$result) {
                $result = parent::__call($name, $arguments);
                Cache::write($keyCached,$result);
            }
            return $result;
			*/
        }
        return parent::__call($name, $arguments);
    }
}