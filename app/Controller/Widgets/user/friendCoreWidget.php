<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class friendCoreWidget extends Widget {
    public function beforeRender(Controller $controller) {
    	$controller->loadModel('Friend');
    	$num_item_show = $this->params['num_item_show'];
        $id = MooCore::getInstance()->getViewer(true);
        $subject = MooCore::getInstance()->getSubject();
        if (MooCore::getInstance()->getSubjectType() == 'User')
        {
        	$id = $subject['User']['id'];
        }
        $friends = array();
        if ($id)
        {
        	$friends = $controller->Friend->getUserFriends( $id, null, $num_item_show );
        }
		
        $this->setData('friendCoreWidget',$friends);
    }
}