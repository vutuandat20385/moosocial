<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class NotificationStop extends AppModel {
    public function isNotificationStop($item_id, $item_type, $viewer_id){
    	if($item_type == ''){
            $item_type = 'activity';
        }
        //hack one photo
        if ($item_type == 'activity')
        {        	
            $activity = MooCore::getInstance()->getItemByType('Activity', $item_id);            
            if (($activity['Activity']['item_type'] == 'Photo_Album' && $activity['Activity']['action'] == 'wall_post') ||
              ($activity['Activity']['item_type'] == 'Photo_Photo' && $activity['Activity']['action'] == 'photos_add')) 
            {
            	 $photo_id = explode(',', $activity['Activity']['items']);
            	 if (count($photo_id) == 1)
            	 {
            	 	$item_type = 'Photo_Photo';
            	 	$item_id = $photo_id[0];
            	 }
            }
        }
        $count = $this->find('count', array('conditions' => array('item_type' => $item_type,
                'item_id' => $item_id,
                'user_id' => $viewer_id)
            ));

        if ($count){
            return true;
        }

        return false;
    }
}
