<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('AppController', 'Controller');
App::uses('BlogHelper', 'View/Helper');

class BlogAppController extends AppController {

    public function beforeFilter() {
    	if (Configure::read("Blog.blog_consider_force"))
    	{
    		$this->check_force_login = false;
    	}
    	
        parent::beforeFilter();
    }

}
