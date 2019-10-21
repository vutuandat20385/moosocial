<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class Home_activityCoreWidget extends Widget {
    public function beforeRender(Controller $controller) {
        $controller->set('homeActivityWidgetParams',$controller->Feeds->get());

    }
}