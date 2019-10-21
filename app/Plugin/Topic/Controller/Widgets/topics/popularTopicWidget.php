<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class popularTopicWidget extends Widget {
    public function beforeRender(Controller $controller) {
    	$num_item_show = $this->params['num_item_show'];
    	$controller->loadModel('Topic.Topic');
         $user_blocks = array();
            $cuser = $controller->_getUser();
            if($cuser){
                $user_blocks = $controller->getBlockedUsers($cuser['id']);  
            }
            
            if(empty($user_blocks)){
    	$popular_topics = Cache::read('topic.popular_topics.'.$num_item_show,'topic');
    	if (!$popular_topics)
    	{
    		$popular_topics = $controller->Topic->getPopularTopics( $num_item_show, Configure::read('core.popular_interval') );
    		Cache::write('topic.popular_topics.'.$num_item_show, $popular_topics,'topic');
    	}
    	 }else{
                $popular_topics = $controller->Topic->getPopularTopics( $num_item_show, Configure::read('core.popular_interval') );
            }
    	$this->setData('popular_topics', $popular_topics);
    }
}