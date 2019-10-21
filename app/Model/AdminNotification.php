<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class AdminNotification extends AppModel {	

	public $belongsTo = array( 'User' );
	
	public $validate = array( 'user_id' => array( 'rule' => 'notBlank'),
							  'text' => array( 'rule' => 'notBlank' ),
							  'url' => array( 'rule' => 'notBlank' )
						 );
							  
	public $order = 'AdminNotification.id desc';
	
}
