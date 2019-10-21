<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class popularVideoWidget extends Widget {
    public function beforeRender(Controller $controller) {
        $num_item_show = $this->params['num_item_show'];
        $controller->loadModel('Video.Video');
        $user_blocks = array();
            $cuser = $controller->_getUser();
            if($cuser){
                $user_blocks = $controller->getBlockedUsers($cuser['id']);  
            }
            
            if(empty($user_blocks)){
        $popular_videos = Cache::read('video.popular_video.'.$num_item_show,'video');
        if (!$popular_videos)
        {
            $popular_videos = $controller->Video->getPopularVideos( $num_item_show, Configure::read('core.popular_interval') );
            Cache::write('video.popular_video.'.$num_item_show,$popular_videos,'video');
        }
        }else{
                $popular_videos = $controller->Video->getPopularVideos( $num_item_show, Configure::read('core.popular_interval') );
            }

        $this->setData('popular_videos', $popular_videos);

    }
}