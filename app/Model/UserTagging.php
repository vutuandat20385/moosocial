<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');

class UserTagging extends AppModel {

    public $actsAs = array('Notification');
    
    public function getTaggedItem($item_id = null, $type = null){
        $data = array();
        if (!empty($item_id) && !empty($type)){
            $data = $this->find('first', array('conditions' => array('UserTagging.item_id' => $item_id, 'UserTagging.item_table' => $type)));
        }
        
        return $data;
    }
    
    public function isTagged($uid, $item_id, $item_type){
        $userTagging = $this->find('first', array('conditions' => array('UserTagging.item_id' => $item_id, 'UserTagging.item_table' => Inflector::pluralize($item_type))));

        if ($userTagging){
            if (in_array($uid, explode(',', $userTagging['UserTagging']['users_taggings']))){
                return true;
            }
            
            return false;
        }
        
        return false;
    }

}
