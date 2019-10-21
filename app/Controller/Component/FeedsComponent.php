<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Component', 'Controller');
class FeedsComponent extends Component {
    public $c = null;
    private function _removeGroupJoinActivities($activities)
    {
        foreach ($activities as $key => &$activity) {
            $aActivity = $activity['Activity'];
            if ($aActivity['action'] == 'group_join') {
                $viewer = MooCore::getInstance()->getViewer();
                $groupIds = explode(',', $aActivity['items']);
                $groupModel = MooCore::getInstance()->getModel('Group.Group');

                $privateGroupIds = $groupModel->findPrivateGroup($groupIds, $viewer);
                $groupIds = array_diff($groupIds, $privateGroupIds);

                if (empty($groupIds)) {
                    unset($activities[$key]);
                } else {
                    $activity['Activity']['items'] = implode(',', $groupIds);
                    $activity['Activity']['target_id'] = 0;
                }
            }
        }
        return $activities;
    }
    public function get() {
        $c = $this->_Collection->getController();
        if(empty($c)) return null;
        $subject = MooCore::getInstance()->getSubject();
        $c->loadModel('Activity');
        $cookieComponent = $c->Cookie;
        $activities = array();
        $activity_likes = array();
        $activities_count = 0;
        $viewer = MooCore::getInstance()->getViewer();
        $uid = MooCore::getInstance()->getViewer(true);
        $check_post_status = false;
        $text = '';
        $admins = array();
        $url_more = '';
        $class = '';
        $activity_feed = '';
        $activityParams = true;
        if (!$subject)
        {
            $type = 'User';
            $class = 'home_user';
            $target_id = 0;
            if ( !empty( $uid ) || ( empty( $uid ) && !Configure::read('core.hide_activites') ) )
            {
                $activity_feed = Configure::read('core.default_feed');

                if(empty($uid))
                    $activity_feed = 'everyone';

                // save activity feed that you selected
                if ( !empty( $uid ) && Configure::read('core.feed_selection') && $cookieComponent->read('activity_feed') )
                    $activity_feed = $cookieComponent->read('activity_feed');

                if (!in_array($activity_feed, array('everyone','friends')))
                {
                    $activity_feed = Configure::read('core.default_feed');
                }

                $activities = $c->Activity->getActivities( $activity_feed, $uid );
                $activities_count = $c->Activity->getActivitiesCount($activity_feed, $uid) ;

                //do not display activity when joined private group
                $activities = $this->_removeGroupJoinActivities($activities);

                $check_post_status = $uid;
                $text = __("Share what's new");
                $target_id = 0;
             
                if(!isset($activities['page'])){
                    $url_more = '/activities/browse/'.$activity_feed.'/page:2';
                }else{
                    $url_more = '/activities/browse/'.$activity_feed.'/page:'.($activities['page'] + 1);
                    unset($activities['page']);
                }
            }
            else
            {
                $activityParams = false;
            }
        }
        else
        {
            $subject_type = key($subject);
            $class = 'profile_'.strtolower($subject_type);
            $target_id = $subject[$subject_type]['id'];
            $type = $subject[$subject_type]['moo_type'];
            if ($subject_type == 'User')
            {
                $activities = $c->Activity->getActivities( 'profile', $subject['User']['id'], $uid );
                $activities_count = $c->Activity->getActivitiesCount( 'profile', $subject['User']['id'], $uid );

                //do not display activity when joined private group
                $activities = $this->_removeGroupJoinActivities($activities);


                $check_post_status = $uid;
                if ( $subject['User']['id'] == $uid){
                    $admins[] = $uid;
                }
               
                if(!isset($activities['page'])){
                    $url_more = '/activities/browse/profile/' . $subject['User']['id'] . '/page:2';
                }else{
                    $url_more = '/activities/browse/profile/' . $subject['User']['id'] . '/page:' . ($activities['page'] + 1);
                    unset($activities['page']);
                }
                if ($uid == $subject['User']['id'])
                {
                    $text = __("What's on your mind?");
                    $target_id = 0;
                }
                else
                {
                    $text = __("Write something...");
                    $target_id = $subject[$subject_type]['id'];
                }
            }
            else
            {
                $activities = $c->Activity->getActivities( $subject[$subject_type]['moo_type'], $subject[$subject_type]['id'] );
                $activities_count = $c->Activity->getActivitiesCount($subject[$subject_type]['moo_type'], $subject[$subject_type]['id']);

                //filter group_join activity
                if($subject_type == 'Group')
                {
                    foreach($activities as $index => &$activity)
                    {
                        if($activity['Activity']['action'] == 'group_join')
                        {
                            $aItem = explode(',',$activity['Activity']['items']);
                            if(!in_array($subject[$subject_type]['id'],$aItem))
                            {
                                unset($activities[$index]);
                               
                            }
                            else
                            {
                                $activity['Activity']['items'] = $subject[$subject_type]['id'];
                                $activity['Activity']['target_id'] = $subject[$subject_type]['id'];
                            }
                        }
                    }
                }
                list($plugin, $name) = mooPluginSplit($subject[$subject_type]['moo_type']);
                $helper = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
                $check_post_status = $helper->checkPostStatus($subject,$uid);
                $check_see_activity = $helper->checkSeeActivity($subject,$uid);
                if (!$check_see_activity)
                    $activityParams = false;

                $admins = $helper->getAdminList($subject);
                $text = __("Write something...");
                $target_id = $subject[$subject_type]['id'];

                $url_more = '/activities/browse/'.$subject[$subject_type]['moo_type'].'/' . $subject[$subject_type]['id'] . '/page:2';
            }
        }

        if ( !empty( $uid ) )
        {
            $c->loadModel('Like');
            $activity_likes = $c->Like->getActivityLikes( $activities, $uid );
        }
        
        $CategoryModel = MooCore::getInstance()->getModel('Category');
        $video_categories = $CategoryModel->getCategoriesList('Video');
        
        $bIsACtivityloadMore = $activities_count - count($activities);
        if($activityParams)
            $activityParams = array(
                'activities'=>$activities,
                'activity_likes' => $activity_likes,
                'check_post_status' => $check_post_status,
                'text' => $text,
                'admins' => $admins,
                'url_more' => $url_more,
                'bIsACtivityloadMore' => $bIsACtivityloadMore,
                'class_feed' => $class,
                'target_id' => $target_id,
                'subject_type' => $type,
                'video_categories' => $video_categories
            );
        return $activityParams;
    }
}