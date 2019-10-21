<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class UserBlock extends AppModel {
		
    public $belongsTo = array( 'User'  => array('counterCache' => true	));
    public $listUserBlocks = array();

	/*
	 * Return a list of user_blockss for dropdown list
	 * @param int $uid
	 * @param array $excludes an array of user ids to exclude
	 */
	public function getUserBlocksList( $uid, $excludes = array() )
	{
		$this->unbindModel(
			array('belongsTo' => array('User'))
		);

		$this->bindModel(
			array('belongsTo' => array(
					'User' => array(
						'className' => 'User',
						'foreignKey' => 'object_id'
					)
				)
			)
		);

		$cond = array( 'UserBlock.user_id' => $uid, 'User.active' => 1 );
		
		if ( !empty( $excludes ) )
			$cond['NOT'] = array( 'UserBlock.object_id' => $excludes );
		
		$user_blockss = $this->find( 'all', array( 'conditions' => $cond, 
											  'fields' 	   => array( 'User.id', 'User.name', 'User.avatar' ),
											  'order'	   => 'User.name asc'
							) 	); // have to do this because find(list) does not work with bindModel
		$user_blocks_options = array();

		foreach ($user_blockss as $user_blocks)
			$user_blocks_options[$user_blocks['User']['id']] = $user_blocks['User']['name'];

		return $user_blocks_options;
	}
        
        /*
	 * Return an array of user_blocks ids
	 */
	public function getUserBlocks( $uid )
	{
        if (isset($this->listUserBlocks[$uid]))
            return $this->listUserBlocks[$uid];

		$user_blockss = $this->find( 'list' , array( 'conditions' => array( 'UserBlock.user_id' => $uid ), 
												'fields' => array( 'object_id' ) 
							) );
        $this->listUserBlocks[$uid] = $user_blockss;
		return $user_blockss;
	}
	
	/*
	 * Return a list of user_blockss for displaying
	 */
	public function getUserUserBlocks( $uid, $page = 1, $limit = RESULTS_LIMIT )
	{
		$this->unbindModel(
			array('belongsTo' => array('User'))
		);

		$this->bindModel(
			array('belongsTo' => array(
					'User' => array(
						'className' => 'User',
						'foreignKey' => 'object_id'
					)
				)
			)
		);

		$user_blockss = $this->find('all', array( 'conditions' => array( 'UserBlock.user_id' => $uid, 'User.active' => 1 ), 
		                                     'order' => 'User.name asc',
											 'limit' => $limit, 
											 'page' => $page)
		);

		return $user_blockss;
	}
    
    /*
     * Return a list of user_blockss for searching
     */
    public function searchUserBlocks( $uid, $q )
    {
        $this->unbindModel(
            array('belongsTo' => array('User'))
        );

        $this->bindModel(
            array('belongsTo' => array(
                    'User' => array(
                        'className' => 'User',
                        'foreignKey' => 'object_id'
                    )
                )
            )
        );
        
        $user_blockss = $this->find( 'all', array( 'conditions' =>  array( 'UserBlock.user_id' => $uid, 
                                                                      'User.active' => 1,
                                                                      'User.name LIKE "%' . $q . '%"' ), 
                                              //'fields'     => array( 'User.id', 'User.name', 'User.avatar' ),
                                              'order'      => 'User.name asc'
                            )   ); 

        return $user_blockss;
    }
	
    public function getBlockedUsers( $uid = null)
    {
       if(empty($uid)){
           $uid = MooCore::getInstance()->getViewer(true);
       }
       $blocked_users = array();
       if($uid){
            $blocked_users = Cache::read('blocked_users_' . $uid);         
            if (!$blocked_users){
                 $blocked_users_arr = $this->find( 'all' , array( 'conditions' => array(
                                                    'OR' => array(
                                                        array( 'UserBlock.user_id' => $uid), 
                                                        array( 'UserBlock.object_id' => $uid )
                                                    )) ) );          
                                foreach ($blocked_users_arr as $usr) {
                                    if($usr['UserBlock']['user_id'] == $uid){
                                        $blocked_users[] = $usr['UserBlock']['object_id'];
                                    }else{
                                        $blocked_users[] = $usr['UserBlock']['user_id'];
                                    }
                                }
                  if(!$blocked_users){
                      $blocked_users = array();
                  }       
                 Cache::write('blocked_users_' . $uid, $blocked_users);
             }
       }
        return $blocked_users;
    }        

	public function areUserBlocks( $uid1, $uid2 )
	{
		$this->cacheQueries = true;
		
		$count = $this->find( 'count', array( 'conditions' => array( 'UserBlock.user_id' => $uid1, 'UserBlock.object_id' => $uid2 ) ) );
		return $count;		
	}
        
        public function getPairBlockUser ($user_ids){
            $pair_users = $this->find('list', array( 'conditions' => array( 'UserBlock.user_id' => $user_ids, 'UserBlock.object_id' => $user_ids),
                                                    'limit' => 1,
                                                    'fields' => array( 'UserBlock.user_id', 'UserBlock.object_id' )                                                    
					) );
            return $pair_users;
        }
        
    public function afterSave($created, $options = array()){
        Cache::delete('blocked_users_'.$this->data['UserBlock']['object_id']);
        Cache::delete('blocked_users_'.$this->data['UserBlock']['user_id']);
        $this->getEventManager()->dispatch(new CakeEvent('Model.UserBlock.afterSave', $this));
    }
    public function beforeDelete($cascade = true){
        Cache::delete('blocked_users_'.$this->field('object_id'));
        Cache::delete('blocked_users_'.$this->field('user_id'));
        $this->getEventManager()->dispatch(new CakeEvent('Model.UserBlock.beforeDelete', $this));
    }  

}
 