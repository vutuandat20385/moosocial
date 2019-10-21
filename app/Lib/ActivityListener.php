<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class ActivityListener implements CakeEventListener {

    public function implementedEvents() {
        return array(
            'Plugin.Controller.Blog.afterDeleteBlog' => 'afterDeleteBlog',
            'Plugin.Controller.Video.afterDeleteVideo' => 'afterDeleteVideo',
            'Plugin.Controller.Topic.afterDeleteTopic' => 'afterDeleteTopic',
            'Plugin.Controller.Group.afterDeleteGroup' => 'afterDeleteGroup',
            'Plugin.Controller.Event.afterDeleteEvent' => 'afterDeleteEvent',
            'Plugin.Controller.Group.afterDeletePhoto' => 'afterDeletePhoto',
            'Plugin.Controller.Album.afterDeleteAlbum' => 'afterDeleteAlbum',
            'ActivitesController.processVideoUpload' => 'processVideoUpload',
        );
    }

    public function afterDeleteBlog($event) {

        $item = $event->data['item'];

        $activityModel = MooCore::getInstance()->getModel('Activity');

        // delete shared feed
        if (!empty($item['Blog'])) {
            $activityModel->deleteAll(array('Activity.item_type' => 'Blog_Blog', 'Activity.parent_id' => $item['Blog']['id']));
        }
    }

    public function processVideoUpload($event) {
        $item = $event->data['item'];

        if (!Configure::read('UploadVideo.uploadvideo_enabled')) {
            return;
        }

        if (!empty($item)) {
            // update activity set to waiting status, will enable when finish convert video
            $activityModel = MooCore::getInstance()->getModel('Activity');

            $activityModel->updateAll(array('Activity.status' => "'" . ACTIVITY_WAITING . "'"), array('Activity.id' => $item['Activity']['id']));
        }
    }

    public function afterDeleteVideo($event) {
        $item = $event->data['item'];

        $activityModel = MooCore::getInstance()->getModel('Activity');

        // delete shared feed
        if (!empty($item['Video'])) {
            $activityModel->deleteAll(array('Activity.item_type' => 'Video_Video', 'Activity.parent_id' => $item['Video']['id']));
        }
    }

    public function afterDeleteTopic($event) {
        $item = $event->data['item'];

        $activityModel = MooCore::getInstance()->getModel('Activity');

        // delete shared feed
        if (!empty($item['Topic'])) {
            $activityModel->deleteAll(array('Activity.item_type' => 'Topic_Topic', 'Activity.parent_id' => $item['Topic']['id']));
        }
    }

    public function afterDeleteGroup($event) {
        $item = $event->data['item'];

        $activityModel = MooCore::getInstance()->getModel('Activity');

        // delete shared feed
        if (!empty($item['Group'])) {
            $activityModel->deleteAll(array('Activity.item_type' => 'Group_Group', 'Activity.parent_id' => $item['Group']['id']));
        }
    }

    public function afterDeleteEvent($event) {
        $item = $event->data['item'];

        $activityModel = MooCore::getInstance()->getModel('Activity');

        // delete shared feed
        if (!empty($item['Event'])) {
            $activityModel->deleteAll(array('Activity.item_type' => 'Event_Event', 'Activity.parent_id' => $item['Event']['id']));
        }
    }

    public function afterDeletePhoto($event) {
        $item = $event->data['item'];

        $activityModel = MooCore::getInstance()->getModel('Activity');

        // delete shared feed
        if (!empty($item['Photo'])) {
            $activityModel->deleteAll(array('Activity.item_type' => 'Photo_Photo', 'Activity.parent_id' => $item['Photo']['id']));
        }
    }

    public function afterDeleteAlbum($event) {
        $item = $event->data['item'];

        $activityModel = MooCore::getInstance()->getModel('Activity');

        // delete shared feed
        if (!empty($item['Album'])) {
            $activityModel->deleteAll(array('Activity.item_type' => 'Photo_Album', 'Activity.parent_id' => $item['Album']['id']));
        }
    }

}
