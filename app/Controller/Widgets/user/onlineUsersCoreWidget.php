<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class onlineUsersCoreWidget extends Widget {
    public function beforeRender(Controller $controller) {


        $controller->loadModel('User');

        $online = $controller->User->getOnlineUsers( $this->params['num_item_show']);

        $this->setData('onlineUsersCoreWidget',array('online'=>$online));
    }
}