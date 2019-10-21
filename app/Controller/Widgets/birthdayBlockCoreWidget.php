<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class birthdayBlockCoreWidget extends Widget {
    public function beforeRender(Controller $controller) {
    	$uid = MooCore::getInstance()->getViewer(true);
    	$birthday = null;
    	if ($uid)
    	{
    		$controller->loadModel('User');
    		$birthday = $controller->User->getTodayBirthdayFriend($uid,$controller->viewVars['utz']);
    	}
    	$this->setData('birthday', $birthday);
    }
}