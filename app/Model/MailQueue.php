<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class MailQueue extends AppModel {	
	
	public $validate = array(  
			'subject' => array( 	 
				'rule' => 'notBlank'
			),
			'email' => 	array( 	 
				'email' => array(
					  'rule' => 'email',
					  'allowEmpty' => false
				)
			)
	);
}
