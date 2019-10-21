<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class recentlyJoinedCoreWidget extends Widget {
    public function beforeRender(Controller $controller) {
        $controller->loadModel('User');
        $users = $controller->User->getLatestUsers( $this->params['num_item_show']);
        $this->setData('recentlyJoinedCoreWidget',array('users'=>$users));
    }
}