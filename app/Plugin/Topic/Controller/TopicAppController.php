<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('AppController', 'Controller');

class TopicAppController extends AppController {
	public function beforeFilter() {
		if (Configure::read("Topic.topic_consider_force"))
		{
			$this->check_force_login = false;
		}
		
		parent::beforeFilter();
	}
}
