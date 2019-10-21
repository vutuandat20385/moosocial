<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class mutualFriendsCoreWidget extends Widget {
    public function beforeRender(Controller $controller) {
		$uid = MooCore::getInstance()->getViewer(true);
        $mutual_friends = null;
        $subject = MooCore::getInstance()->getSubject();

        if ($subject && $uid)
        {
        	$viewed_id = $subject['User']['id'];
			$controller->loadModel('Friend');
			$num_item_show = $this->params['num_item_show'];
			$mutual_friends = $controller->Friend->getMutualFriends( $uid, $viewed_id, $num_item_show );
        }
		
		$this->setData('mutual_friends', $mutual_friends);
    }
}