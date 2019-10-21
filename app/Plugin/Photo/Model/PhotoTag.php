<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('PhotoAppModel','Photo.Model');
class PhotoTag extends PhotoAppModel {

    public $belongsTo = array('Photo'=>array(
            'className' => 'Photo.Photo',            
        ), 
    	'User');

    public function getPhotos($user_id = null, $page = 1 , $limit = null) {
    	if (!$limit)
    		$limit = Configure::read('Photo.photo_item_per_pages');
    		
        $photos = $this->find('all', array('conditions' => array('PhotoTag.user_id' => $user_id),
                'order' => 'PhotoTag.id desc',
                'limit' => $limit,
                'page' => $page
                    ));
        return $photos;
    }
    
    public function getPhotosCount($uid = null){
        if (empty($uid)){
            exit;
        }
        $cond = array('PhotoTag.user_id' => $uid);
        $count = $this->find('count', array('conditions' => $cond));
        
        return $count;
    }

}
