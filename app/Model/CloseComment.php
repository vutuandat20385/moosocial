<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class CloseComment extends AppModel {
    public $belongsTo = array('User');

    public function getCloseComment($item_id, $item_type){
    	if($item_type == ''){
            $item_type = 'activity';
        }

        $item = $this->find('first', array('conditions' => array('item_type' => $item_type,
                'item_id' => $item_id,
              )
            ));

    	return $item;
    }
}
