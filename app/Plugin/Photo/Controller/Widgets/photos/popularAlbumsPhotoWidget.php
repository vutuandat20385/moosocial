<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class popularAlbumsPhotoWidget extends Widget {
    public function beforeRender(Controller $controller) {
    	$num_item_show = $this->params['num_item_show'];
    	$controller->loadModel('Photo.Album');  
         $user_blocks = array();
            $cuser = $controller->_getUser();
            if($cuser){
                $user_blocks = $controller->getBlockedUsers($cuser['id']);  
            }
            if(empty($user_blocks)){
    	$popular_albums = Cache::read('photo.popular_albums.'.$num_item_show, 'photo');
    	if (!$popular_albums)
    	{
        	$popular_albums = $controller->Album->getPopularAlbums($num_item_show, Configure::read('core.popular_interval'));
        	Cache::write('photo.popular_albums.'.$num_item_show, $popular_albums, 'photo');
    	}
            }else{
            $popular_albums = $controller->Album->getPopularAlbums($num_item_show, Configure::read('core.popular_interval'));
        }
        $this->setData('popular_albums', $popular_albums);
    }
}