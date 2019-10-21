<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooActivityHelper extends AppHelper {
    public function wall($params,$autoRender = true){
        $wall = $this->_View->element('activities_widget',array(
            'activities'=>$params['activities'],
            'activity_likes' => $params['activity_likes'],
            'check_post_status' => $params['check_post_status'],
            'text' => $params['text'],
            'admins' => $params['admins'],
            'url_more' => $params['url_more'],
            'bIsACtivityloadMore' => $params['bIsACtivityloadMore'],
            'class_feed' => $params['class_feed'],
            'video_categories' => $params['video_categories'],
            'target_id' => $params['target_id'],
            'subject_type' => $params['subject_type'],
        ));
        if($autoRender){
            echo $wall;
        }else{
            return $wall;
        }
    }
}