
<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget', 'Controller/Widgets');

class featuredGroupWidget extends Widget {

    public function beforeRender(Controller $controller) {
        
        $data = array(
            'featuredGroup' => array(),
        );
        
        $controller->loadModel('Group.Group');
        
        // get featured groups
        $featuredGroup = $controller->Group->find('all', array('conditions' => array_merge($controller->Group->addBlockCondition(), array('Group.featured' => 1))));
        
        $data['featuredGroup'] = $featuredGroup;
        
        $this->setData('data', $data);
    }

}
