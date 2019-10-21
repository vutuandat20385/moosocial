<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class RatingSetting extends AppModel{
    public function getRatingEnableList(){
        $ratingSettings = $this->find('all');
        $ratingSettings = Hash::combine($ratingSettings,'{n}.RatingSetting.name','{n}.RatingSetting.value');
        $enableRating = json_decode($ratingSettings['enable_rating'],true);
        $enableRatingList = array_keys(array_filter($enableRating));

        $event = new CakeEvent('View.Adm.Layout.adminGetContentInfo',$this);
        $result = $this->getEventManager()->dispatch($event);
        if(!empty($result->result['rating']['enable']) && is_array($result->result['rating']['enable']) ){
            $enableResultList = array_keys(array_filter($result->result['rating']['enable']));
            foreach($enableResultList as &$value){
                //this item does not exist before
                if(!array_key_exists($value,$enableRating)){
                    $enableRatingList[] = $value;
                }
            }
        }
        return $enableRatingList;
    }
}