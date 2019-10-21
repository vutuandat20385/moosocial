<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ConversationUser extends AppModel 
{
	public $belongsTo = array( 'Conversation',
							   'User'  => array(
							   		'counterCache' => true, 
							   		'counterScope' => array( 'unread' => 1 )
							 )	);
							 
	/*
	 * Get participants list of $msg_id
	 * @param int $msg_id
	 * @return array $users
	 */

	public function getUsersList( $msg_id )
	{
		$users = $this->find( 'list', array( 'conditions' => array( 'ConversationUser.conversation_id' => $msg_id ),
											 'fields' 	  => array( 'user_id' )
							) 	);
		return $users;
	}
}
 