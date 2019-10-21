<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('AppController', 'Controller');
class SubscriptionAppController extends AppController {
	public function beforeFilter() {
        parent::beforeFilter();
		if(isset($this->params['prefix']) && $this->params['prefix'] == 'admin')
		{
			$this->_checkPermission(array('super_admin' => 1));
			
			$this->set('title_for_layout', __('Subscription'));
		}
    }
}
