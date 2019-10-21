<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class Conversation extends AppModel {

	public $belongsTo = array( 'User', 
							   'LastPoster' => array(
							   		'className' => 'User', 
							   		'foreignKey' => 'lastposter_id'
							)	);
	
	public $hasMany = array( 'Comment' => array( 
											'className' => 'Comment',	
											'foreignKey' => 'target_id',
											'conditions' => array( 'Comment.type' => APP_CONVERSATION ),						
											'dependent'=> true
										),
						  	 'ConversationUser' => array( 
						  					'className' => 'ConversationUser',												  			
						  					'dependent'=> true
										),
						); 
						
	public $validate = array(	
				'subject' => 	array( 	 
					'rule' => 'notBlank',
					'message' => 'Subject is required'
				),
				'message' => 	array( 	 
					'rule' => 'notBlank',
					'message' => 'Message is required'
				)
	);
}
