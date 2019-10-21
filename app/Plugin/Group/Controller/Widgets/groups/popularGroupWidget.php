<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class popularGroupWidget extends Widget {
    public function beforeRender(Controller $controller) {
    	$num_item_show = $this->params['num_item_show'];
    	
    	$popular_groups = Cache::read('group.popular_groups.'.$num_item_show,'group');
		if(!$popular_groups){
		    $controller->loadModel('Group.Group');
            $popular_groups = $controller->Group->getPopularGroups($num_item_show, Configure::read('core.popular_interval'));
		    Cache::write('group.popular_groups.'.$num_item_show,$popular_groups);
		}
		if(is_array($popular_groups) && count($popular_groups)){
                    $controller->loadModel('Group.GroupUser');
                    foreach ($popular_groups as $key=>$val) {
                        $popular_groups[$key]['Group']['group_user_count'] = $controller->GroupUser->getBlockedUserCount($popular_groups[$key]['Group']['id']);
                    }
                }
		$this->setData('popularGroupWidget', $popular_groups);
    }
}