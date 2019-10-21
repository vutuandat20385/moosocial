<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class suggestionsCoreWidget extends Widget {
    public function beforeRender(Controller $controller) {
    	$uid = MooCore::getInstance()->getViewer(true);
		$num_item_show = $this->params['num_item_show'];
		$friend_suggestions = null;
		if (!empty($uid)) {
			$controller->loadModel('Friend');
			$friend_suggestions = $controller->Friend->getFriendSuggestions($uid, false, $num_item_show);
		}
		$this->setData('friend_suggestions', $friend_suggestions);
    }
}