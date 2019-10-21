<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class FriendRequest extends AppModel {
		
	public $belongsTo = array( 'User' );
							
	/*
	 * Check if there's already a friend request between $uid1 and $uid2
	 */
	public function existRequest( $uid1, $uid2 )
	{
		$count = $this->find( 'count', array( 'conditions' => array( 'FriendRequest.sender_id' => $uid1, 
																	 'FriendRequest.user_id'   => $uid2
											)	)	);
											
		return $count > 0;
	}
	
	/*
	 * Get friend requests of $uid
	 */
	public function getRequests( $uid = null )
	{
		$this->unbindModel(
			array('belongsTo' => array('User'))
		);

		$this->bindModel(
			array('belongsTo' => array(
					'Sender' => array(
						'className' => 'User',
						'foreignKey' => 'sender_id'
					)
				)
			)
		);

		$requests = $this->find('all',array(
			'conditions' => array(
				'FriendRequest.user_id' => $uid,
				'Sender.active' => true
			)
		) );
		
		return $requests;
	}
	
	public function getRequestsList( $uid )
	{
		$requests = $this->find( 'list' , array( 'conditions' => array( 'FriendRequest.sender_id' => $uid ), 
												 'fields' => array( 'user_id' ) 
							) );	
		return $requests;
	}
	
	/*
	 * Get friend request details
	 */
	public function getRequest( $request_id )
	{
		$this->bindModel(
			array('belongsTo' => array(
					'Sender' => array(
						'className' => 'User',
						'foreignKey' => 'sender_id'
					)
				)
			)
		);

		$request = $this->findById( $request_id );
		
		return $request;
	}
	
	public function getRequestByUser($uid1,$uid2)
	{
		$this->unbindModel(
			array('belongsTo' => array('User'))
		);

		$this->bindModel(
			array('belongsTo' => array(
					'Sender' => array(
						'className' => 'User',
						'foreignKey' => 'sender_id'
					)
				)
			)
		);
		
		return $this->find('first',array(
			'conditions' => array(
				'FriendRequest.sender_id' => $uid1,
				'FriendRequest.user_id' => $uid2,
			)
		));
	}
	
	public function afterSave($created, $options = array()) 
	{
		$this->updateCountRequest($this->data['FriendRequest']['user_id']);
	}
	
	public function delete($id = null, $cascade = true)
	{
		$item = $this->findById($id);
		parent::delete($id,$cascade);
		$this->updateCountRequest($item['FriendRequest']['user_id']);
	}
	
	public function updateCountRequest($user_id)
	{
		$prefix = $this->tablePrefix;
		$data = $this->query("SELECT count(*) as count FROM `".$prefix."friend_requests` as a INNER JOIN `".$prefix."users` as b ON a.sender_id = b.id WHERE a.user_id = ".$user_id." and b.active = 1");
		$count = $data[0][0]['count'];
		$this->query("UPDATE ".$prefix."users SET `friend_request_count`= ".$count." WHERE id=" . intval($user_id));
	}
}
 