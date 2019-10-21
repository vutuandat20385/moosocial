<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class SpamChallenge extends AppModel {

	public $order = 'SpamChallenge.id desc';
						
	public $validate = array(	
							'question' => 	array( 	 
								'rule' => 'notBlank',
								'message' => 'Question is required'
							),
							'answers' => 	array( 	 
								'rule' => 'notBlank',
								'message' => 'Answers is required'
							)
	);
	

}
 