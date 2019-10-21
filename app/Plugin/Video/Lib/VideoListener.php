<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class VideoListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'Controller.groupDetailMenu' => 'getGroupMenu',
            'Plugin.Controller.Video.index' => 'processEventIndex',
            'Plugin.Controller.Video.edit' => 'processEventEdit',
            'Plugin.Controller.Video.beforeSave' => 'processEventBeforeSave',
            'Plugin.Controller.Video.afterAdd' => 'processEventAfterAdd',
            'Plugin.Controller.Video.afterSave' => 'processEventAfterSave',
            'Plugin.Controller.Video.create' => 'processEventCreate',
            'Plugin.Controller.Video.fetchPublicVideo' => 'processEventFetchPublicVideo',
            'Plugin.Controller.Video.view' => 'processEventView',
            'Plugin.Controller.Video.getVideoDetail' => 'processEventGetVideoDetail',
            'Plugin.Controller.Video.beforeDelete' => 'processEventBeforeDelete',
            'UserController.deleteUserContent' => 'deleteUserContent',
            'Controller.Search.search' => 'search',
            'Controller.Search.suggestion' => 'suggestion',
            'ActivitesController.afterShare' => 'processActivityAfterShare',
            'Controller.Search.hashtags' => 'hashtags',
            'Controller.Search.hashtags_filter' => 'hashtags_filter',
            'Controller.Widgets.tagCoreWidget' => 'hashtagEnable',
            'Plugin.Controller.Group.beforeDelete' => 'processGroupBeforeDelete',
            'Controller.User.deactivate' => 'deactivate',
            'Controller.Comment.afterComment' => 'afterComment',
            'MooView.beforeRender' => 'beforeRender',
            'Controller.User.afterDeactive' => 'afterDeactiveUser',
            'Controller.User.afterEdit' => 'afterEditUser',
            /* S3 Upload Video*/
            'StorageHelper.uploadVideo.getUrl.local' => 'storageGetVideoLocal',
            'StorageHelper.uploadVideo.getUrl.amazon' => 'storageGetVideoAmazon',
            'StorageAmazon.uploadVideo.getFilePath' => 'storageGetVideoFilePath',
            'StorageTaskAwsCronTransfer.execute' => 'storageTaskAwsCronTransfer',
            'StorageAmazon.uploadVideo.putObject.success' => 'storageAmazonPutObjectSuccessCallback',
        );
    }

    public function afterEditUser($event)
    {
        $cuser = $event->data['item'];

        $categoryModel = Moocore::getInstance()->getModel('Category');
        $videoModel = Moocore::getInstance()->getModel('Video.Video');
        $videoCategory = $categoryModel->find('all', array('conditions' => array('Category.type' => 'Video')));

        foreach ($videoCategory as $item) {
            $category_id = $item['Category']['id'];
            $videos_count = $videoModel->find('count', array('conditions' => array(
                'Video.category_id' => $category_id,
                'User.active' => true
            )));
            $categoryModel->updateAll(array('Category.item_count' => $videos_count), array('Category.id' => $category_id));
        }
    }

    public function afterDeactiveUser($event)
    {
        $cuser = $event->data['item'];

        $categoryModel = Moocore::getInstance()->getModel('Category');
        $videoModel = Moocore::getInstance()->getModel('Video.Video');
        $videoCategory = $categoryModel->find('all', array('conditions' => array('Category.type' => 'Video')));

        foreach ($videoCategory as $item) {
            $category_id = $item['Category']['id'];
            $videos_count = $videoModel->find('count', array('conditions' => array(
                'Video.category_id' => $category_id,
                'User.active' => true
            )));
            $categoryModel->updateAll(array('Category.item_count' => $videos_count), array('Category.id' => $category_id));
        }
    }


    public function beforeRender($event)
    {
        $view = $event->subject();
        if ($view instanceof MooView) {
            $view->addPhraseJs(array(
                'are_you_sure_you_want_to_remove_this_video' => __("Are you sure you want to remove this video?")
            ));
        }
    }

    public function afterComment($event)
    {
        $data = $event->data['data'];
        $target_id = isset($data['target_id']) ? $data['target_id'] : null;
        $type = isset($data['type']) ? $data['type'] : '';
        if ($type == 'Video_Video' && !empty($target_id)) {
            $videoModel = MooCore::getInstance()->getModel('Video.Video');
            Cache::clearGroup('video', 'video');
            $videoModel->updateCounter($target_id);
        }
    }

    // delete all video belong to group is deleted
    public function processGroupBeforeDelete($event)
    {
        $group_id = isset($event->data['aGroup']['Group']['id']) ? $event->data['aGroup']['Group']['id'] : '';
        if (!empty($group_id)) {
            $this->Video = ClassRegistry::init('Video.Video');
            $videos = $this->Video->getVideos('group', $group_id, null, null);
            foreach ($videos as $v) {
                $this->Video->deleteVideo($v);
            }
        }
    }


    public function processActivityAfterShare($event)
    {
        $v = $event->subject();
        if (isset($v->request->data['wall_photo']) && $v->request->data['wall_photo']) {
            return;
        }
        $activity = $event->data['activity'];
        $videoModel = MooCore::getInstance()->getModel('Video_Video');
        $videoModel->parseStatus($activity);
        if (!empty($activity['Content']['Video']['source'])) {
            $data = array(
                'action' => 'video_activity',
                'params' => json_encode($activity['Content']['Video']),
                'plugin' => 'Video',

            );
            $data['share'] = false;

            if (!empty($activity['Activity']['type']) && $activity['Activity']['type'] != 'Group_Group') {
                $data['share'] = true;
            }

            if (!empty($activity['Activity']['type']) && $activity['Activity']['type'] == 'Group_Group') {
                $groupModel = MooCore::getInstance()->getModel('Group.Group');
                $group = $groupModel->findById($activity['Activity']['target_id']);
                if (!empty($group) && $group['Group']['type'] == PRIVACY_PUBLIC) {
                    $data['share'] = true;
                }
            }

            $v->Activity->save($data);
        }
    }

    public function getGroupMenu($event)
    {
        $event->result['menu'][] = array(
            'dataUrl' => Router::url('/', true) . 'videos/browse/group/' . $event->data['aGroup']['Group']['id'],
            'id' => 'videos',
            'href' => Router::url('/', true) . 'groups/view/' . $event->data['aGroup']['Group']['id'] . '/tab:videos',
            'icon-class' => 'videocam',
            'name' => __('Videos'),
            'id_count' => 'group_videos_count',
            'item_count' => $event->data['aGroup']['Group']['video_count']
        );
    }

    public function processEventIndex($event)
    {
        $v = $event->subject();
        $this->Tag = ClassRegistry::init('Tag');

        $this->Friend = ClassRegistry::init('Friend');
        $friends_list = $this->Friend->getFriendsList($v->Auth->user('id'));

        $sFriendsList = '';
        $aFriendListId = array_keys($friends_list);
        $sFriendsList = implode(',', $aFriendListId);

        $videoModel = MooCore::getInstance()->getModel('Video_Video');

        $event->result['friends_list'] = $sFriendsList;
        $tags = $this->Tag->getTags('Video_Video', Configure::read('core.popular_interval'));

        $v->set('tags', $tags);
    }

    public function processEventEdit($event)
    {
        // if it's a group video, add group admins to the admins array for permission checking
        if (!empty($video['Video']['group_id'])) {
            $this->GroupUser = ClassRegistry::init('GroupUser');

            $group_admins = $this->GroupUser->getUsersList($event->data['video']['Video']['group_id'], GROUP_USER_ADMIN);
            $event->result['admins'] = array_merge($event->data['admins'], $group_admins);
        }
    }

    public function processEventBeforeSave($event)
    {
        $v = $event->subject();
        // if it's a group video, check if user has permission to create topic in this group
        if (!empty($v->request->data['group_id'])) {
            $this->GroupUser = ClassRegistry::init('Group.GroupUser');
            $this->Group = ClassRegistry::init('Group.Group');
            if (!$this->GroupUser->isMember($event->data['uid'], $v->request->data['group_id']))
                $event->result['notMember'] = 1;

            $group = $this->Group->findById($v->request->data['group_id'], array('type'));
            $event->result['privacy'] = $group['Group']['type'];
        }
    }

    public function processEventAfterAdd($event)
    {
        $v = $event->subject();
        $type = APP_USER;
        $target_id = 0;
        $privacy = $event->data['privacy'];
        if (!empty($v->request->data['group_id'])) {
            $type = 'Group_Group';
            $target_id = $v->request->data['group_id'];

            $this->Group = ClassRegistry::init('Group.Group');
            $group = $this->Group->findById($target_id);

            $privacy = $event->data['privacy'];
            if ($group['Group']['type'] == PRIVACY_PRIVATE)
                $privacy = PRIVACY_ME;
        }
        // insert activity
        if (!empty($v->request->data['group_id'])) {
            $this->Activity = ClassRegistry::init('Activity');

            $this->Activity->save(array('type' => $type,
                'target_id' => $target_id,
                'action' => 'video_create',
                'user_id' => $event->data['uid'],
                'item_type' => 'Video_Video',
                'item_id' => $event->data['video_id'],
                'query' => 1,
                'privacy' => $privacy,
                'params' => 'item',
                'plugin' => 'Video',
                'share' => true,
            ));
        }
    }

    public function processEventAfterSave($event)
    {
        $v = $event->subject();
        if (!empty($v->request->data['tags'])) {
            // save tags
            $this->Tag = ClassRegistry::init('Tag');
            $this->Tag->saveTags($v->request->data['tags'], $event->data['id'], 'Video_Video');
        } else {
            if (!empty($v->request->data['id'])) { //delete all tag of video when edit
                $this->Tag = ClassRegistry::init('Tag');
                $tags = $this->Tag->find('list', array('conditions' => array('Tag.target_id' => $event->data['id'], 'Tag.type' => 'Video_Video'), 'fields' => array('Tag.id')));
                if (!empty($tags)) {
                    foreach ($tags as &$tag_id)
                        $this->Tag->delete($tag_id);
                }

            }
        }

        // load feed model
        $this->Activity = ClassRegistry::init('Activity');

        // find activity which belong to event just created
        $activity = $this->Activity->find('first', array('conditions' => array(
            'Activity.item_type' => 'Video_Video',
            'Activity.item_id' => $event->data['id'],
        )));

        if (!empty($activity)) {
            $share = false;
            // only enable share feature for public event
            if ($event->data['privacy'] == PRIVACY_EVERYONE || $event->data['privacy'] == PRIVACY_FRIENDS) {
                // do not display share feature for item in Group, Event
                if (!empty($activity['Activity']['type']) && $activity['Activity']['type'] != 'Group_Group') {
                    $share = true;
                }

                if (!empty($activity['Activity']['type']) && $activity['Activity']['type'] == 'Group_Group') {
                    $groupModel = MooCore::getInstance()->getModel('Group.Group');
                    $group = $groupModel->findById($activity['Activity']['target_id']);
                    if (!empty($group) && $group['Group']['type'] != PRIVACY_PRIVATE && $group['Group']['type'] != PRIVACY_RESTRICTED) {
                        $share = true;
                    }
                }
            }
            $this->Activity->clear();
            $this->Activity->updateAll(array('Activity.share' => $share), array('Activity.id' => $activity['Activity']['id']));
        }
    }

    public function processEventCreate($event)
    {
        $v = $event->subject();
        $this->Tag = ClassRegistry::init('Tag');
        $tags = $this->Tag->getContentTags($event->data['video_id'], 'Video_Video');

        $this->Category = ClassRegistry::init('Category');
        $role_id = $v->_getUserRoleId();
        $categories = $this->Category->getCategoriesList('Video', $role_id);
        $v->set('tags', $tags);
        $v->set('categories', $categories);
    }

    public function processEventFetchPublicVideo($event)
    {
        $v = $event->subject();
        $this->Category = ClassRegistry::init('Category');
        $role_id = $v->_getUserRoleId();
        $categories = $this->Category->getCategoriesList('Video', $role_id);

        $v->set('categories', $categories);
    }

    public function processEventView($event)
    {
        $v = $event->subject();
        $this->Tag = ClassRegistry::init('Tag');
        $this->Like = ClassRegistry::init('Like');
        $tags = $this->Tag->getContentTags($event->data['id'], 'Video_Video');
        $similar_videos = $this->Tag->getSimilarVideos($event->data['id'], $tags);
        $likes = $this->Like->getLikes($event->data['id'], 'Video_Video');
        $dislikes = $this->Like->getDisLikes($event->data['id'], 'Video_Video');

        $v->set('tags', $tags);
        $v->set('similar_videos', $similar_videos);
        $v->set('likes', $likes);
        $v->set('dislikes', $dislikes);
    }

    public function processEventGetVideoDetail($event)
    {
        $v = $event->subject();
        // if video belongs to a group, check permission
        $admins = $event->data['admins'];
        if (!empty($event->data['video']['Video']['group_id'])) {
            $this->GroupUser = ClassRegistry::init('Group.GroupUser');

            $is_member = $this->GroupUser->isMember($event->data['uid'], $event->data['video']['Video']['group_id']);
            $v->set('is_member', $is_member);

            if ($event->data['video']['Group']['type'] == PRIVACY_PRIVATE) {
                $cuser = $v->_getUser();

                if (!$cuser['Role']['is_admin'] && !$is_member) {
                    $v->Session->setFlash(__("This is a private group video and can only be viewed by the group's members"), 'default', array('class' => 'error-message'));
                    $v->redirect('/pages/no-permission');

                    exit;
                }
            }

            $group_admins = $this->GroupUser->getUsersList($event->data['video']['Video']['group_id'], GROUP_USER_ADMIN);
            $admins = array_merge($event->data['admins'], $group_admins);
        }

        $this->Comment = ClassRegistry::init('Comment');
        $this->Like = ClassRegistry::init('Like');

        $cond= array();
        if (!empty( $event->data['comment_id'] ) && $event->data['comment_id']) {
            $cond['Comment.id'] = $event->data['comment_id'];
            $data['cmt_id'] = $event->data['comment_id'];
            $data['subject'] = $event->data['video'];
        }

        $comments = $this->Comment->getComments( $event->data['video']['Video']['id'], 'Video_Video', 1, $cond );

        if(!empty( $event->data['reply_id']) && !empty($comments[0])){
            $reply = $this->Comment->find('all', array(
                'conditions' => array(
                    'Comment.id' => $event->data['reply_id'],
                )
            ));
            $replies_count = $this->Comment->getCommentsCount( $comments[0]['Comment']['id'], 'comment' );
            $comment_likes = $this->Like->getCommentLikes( $reply, $event->data['uid'] );

            $comments[0]['Replies'] = $reply;
            $comments[0]['RepliesIsLoadMore'] = ($replies_count - 1) > 0 ? true : false;
            $comments[0]['RepliesCommentLikes'] = $comment_likes;
        }

        $comment_count = $event->data['video']['Video']['comment_count'];
        $comment_likes = array();
        // get comment likes
        if (!empty($event->data['uid'])) {
            $comment_likes = $this->Like->getCommentLikes($comments, $event->data['uid']);
            $v->set('comment_likes', $comment_likes);

            $like = $this->Like->getUserLike($event->data['video']['Video']['id'], $event->data['uid'], 'Video_Video');
            $v->set('like', $like);
        }
        //$this->set('comments', $comments);
        //$v->set('comment_count', $comment_count);

        $page = 1;
        $data['bIsCommentloadMore'] = $comment_count - $page * RESULTS_LIMIT;
        $data['more_comments'] = '/comments/browse/Video_Video/' . $event->data['video']['Video']['id'] . '/page:' . ($page + 1);
        $data['admins'] = $admins;
        $data['comments'] = $comments;
        $data['comment_likes'] = $comment_likes;
        $event->result['data'] = $data;
        $v->set('admins', $admins);
    }

    public function processEventBeforeDelete($event)
    {
        $admins = array($event->data['video']['User']['id']); // video creator

        if (!empty($event->data['video']['Video']['group_id'])) // if it's a group video, add group admins to the admins array
        {
            $this->GroupUser = ClassRegistry::init('Group.GroupUser');

            $group_admins = $this->GroupUser->getUsersList($event->data['video']['Video']['group_id'], GROUP_USER_ADMIN);
            $admins = array_merge($admins, $group_admins);
        }
        $event->result['admins'] = $admins;
    }

    public function deleteUserContent($event)
    {
        App::import('Video.Model', 'Video');

        $this->Video = new Video();

        $videos = $this->Video->findAllByUserId($event->data['aUser']['User']['id']);
        foreach ($videos as $video) {
            $this->Video->deleteVideo($video);
        }
    }

    public function search($event)
    {
        $e = $event->subject();
        App::import('Model', 'Video.Video');
        $this->Video = new Video();
        $results = $this->Video->getVideos('search', $e->keyword, 1);
        if (count($results) > 5)
            $results = array_slice($results, 0, 5);
        if (empty($results))
            $results = $this->Video->getVideoSuggestion($e->keyword, 5);
        if (isset($e->plugin) && $e->plugin == 'Video') {
            $e->set('videos', $results);
            $e->render("Video.Elements/lists/videos_list");
        } else {
            $event->result['Video']['header'] = __("Videos");
            $event->result['Video']['icon_class'] = "videocam";
            $event->result['Video']['view'] = "lists/videos_list";
            if (!empty($results))
                $event->result['Video']['notEmpty'] = 1;
            $e->set('videos', $results);
        }
    }

    public function suggestion($event)
    {
        $e = $event->subject();
        App::import('Model', 'Video.Video');
        $this->Video = new Video();

        $event->result['video']['header'] = __('Videos');
        $event->result['video']['icon_class'] = 'videocam';

        //search with filter
        if (isset($event->data['type']) && $event->data['type'] == 'video') {
            $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;
            $videos = $this->Video->getVideos('search', $event->data['searchVal'], $page);
            $more_videos = $this->Video->getVideos('search', $event->data['searchVal'], $page + 1);
            $more_result = 0;
            if (!empty($more_videos))
                $more_result = 1;
            if (empty($videos))
                $videos = $this->Video->getVideoSuggestion($event->data['searchVal'], RESULTS_LIMIT, $page);
            $e->set('videos', $videos);
            $e->set('result', 1);
            $e->set('more_result', $more_result);
            $more_url = isset($e->params['pass'][1]) ? '/search/suggestion/video/' . $e->params['pass'][1] . '/page:' . ($page + 1) : '';
            $e->set('more_url', $more_url);
            $e->set('element_list_path', "Video.lists/videos_list");
        }
        //search all
        if (isset($event->data['type']) && $event->data['type'] == 'all') {
            $event->result['video'] = null;
            $videos = $this->Video->getVideos('search', $event->data['searchVal'], 1, 2);
            if (count($videos) > 2) {
                $videos = array_slice($videos, 0, 2);
            }
            if (empty($videos))
                $videos = $this->Video->getVideoSuggestion($event->data['searchVal'], 2);

            if (!empty($videos)) {
                $event->result['video'] = array(__('Video'));
                $helper = MooCore::getInstance()->getHelper("Video_Video");
                foreach ($videos as $index => &$detail) {
                    $index++;
                    $event->result['video'][$index]['id'] = $detail['Video']['id'];
                    if (!empty($detail['Video']['thumb']))
                    	$event->result['video'][$index]['img'] = $helper->getImage($detail,array('prefix'=>'75_square'));
                    $event->result['video'][$index]['title'] = $detail['Video']['title'];
                    $event->result['video'][$index]['find_name'] = 'Find Videos';
                    $event->result['video'][$index]['icon_class'] = 'videocam';
                    $event->result['video'][$index]['view_link'] = 'videos/view/';

                    $mooHelper = MooCore::getInstance()->getHelper('Core_Moo');

                    $utz = (!is_numeric(Configure::read('core.timezone'))) ? Configure::read('core.timezone') : 'UTC';
                    $cuser = MooCore::getInstance()->getViewer();
                    // user timezone
                    if (!empty($cuser['User']['timezone'])) {
                        $utz = $cuser['User']['timezone'];
                    }

                    $privacy = 'Public';
                    switch ($detail['Video']['privacy']) {
                        case PRIVACY_PUBLIC:
                            $privacy = __('Public');
                            break;

                        case PRIVACY_FRIENDS:
                            $privacy = __('Friend');
                            break;

                        case PRIVACY_PRIVATE:
                            $privacy = __('Private');
                            break;
                    }

                    $event->result['video'][$index]['more_info'] = __n('%s like', '%s likes', $detail['Video']['like_count'], $detail['Video']['like_count']) .
                        ' ' . $mooHelper->getTime($detail['Video']['created'], Configure::read('core.date_format'), $utz) .
                        ' ' . $privacy;
                }
            }
        }
    }

    public function hashtags($event)
    {
        $enable = Configure::read('Video.video_hashtag_enabled');
        $e = $event->subject();
        App::import('Model', 'Video.Video');
        $this->Video = new Video();
        App::import('Model', 'Tag');
        $this->Tag = new Tag();
        $videos = array();
        $uid = CakeSession::read('uid');
        $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;

        if ($enable) {
            if (isset($event->data['type']) && $event->data['type'] == 'videos') {
                $videos = $this->Video->getVideoHashtags($event->data['item_ids'], RESULTS_LIMIT, $page);
                $videos = $this->_filterVideo($videos);

                $e->set('result', 1);
                $e->set('more_url', '/search/hashtags/' . $e->params['pass'][0] . '/videos/page:' . ($page + 1));
                $e->set('element_list_path', "Video.lists/videos_list");
            }
            $table_name = $this->Video->table;
            if (isset($event->data['type']) && $event->data['type'] == 'all' && !empty($event->data['item_groups'][$table_name])) {
                //$event->result['videos'] = null;

                $videos = $this->Video->getVideoHashtags($event->data['item_groups'][$table_name], 5);
                $videos = $this->_filterVideo($videos);

            }
        }

        // get tagged item
        $tag = h(urldecode($event->data['search_keyword']));
        $tags = $this->Tag->find('all', array('conditions' => array(
            'Tag.type' => 'Video_Video',
            'Tag.tag' => $tag
        )));
        $video_ids = Hash::combine($tags, '{n}.Tag.id', '{n}.Tag.target_id');

        $friendModel = MooCore::getInstance()->getModel('Friend');

        $items = $this->Video->find('all', array('conditions' => $this->Video->addBlockCondition(array(
            'Video.id' => $video_ids
        )),
            'limit' => RESULTS_LIMIT,
            'page' => $page
        ));

        $viewer = MooCore::getInstance()->getViewer();

        foreach ($items as $key => $item) {
            $owner_id = $item[key($item)]['user_id'];
            $privacy = isset($item[key($item)]['privacy']) ? $item[key($item)]['privacy'] : 1;
            if (empty($viewer)) { // guest can view only public item
                if ($privacy != PRIVACY_EVERYONE) {
                    unset($items[$key]);
                }
            } else { // viewer
                $aFriendsList = array();
                $aFriendsList = $friendModel->getFriendsList($owner_id);
                if ($privacy == PRIVACY_ME) { // privacy = only_me => only owner and admin can view items
                    if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id) {
                        unset($items[$key]);
                    }
                } else if ($privacy == PRIVACY_FRIENDS) { // privacy = friends => only owner and friendlist of owner can view items
                    if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id && !in_array($viewer['User']['id'], array_keys($aFriendsList))) {
                        unset($items[$key]);
                    }
                } else {

                }
            }
        }
        $videos = array_merge($videos, $items);
        //only display 5 items on All Search Result page
        if (isset($event->data['type']) && $event->data['type'] == 'all') {
            $videos = array_slice($videos, 0, 5);
        }
        $videos = array_map("unserialize", array_unique(array_map("serialize", $videos)));
        if (!empty($videos)) {
            $event->result['videos']['header'] = __('Videos');
            $event->result['videos']['icon_class'] = 'videocam';
            $event->result['videos']['view'] = "Video.lists/videos_list";
            if (isset($event->data['type']) && $event->data['type'] == 'videos') {
                $e->set('result', 1);
                $e->set('more_url', '/search/hashtags/' . $e->params['pass'][0] . '/videos/page:' . ($page + 1));
                $e->set('element_list_path', "Video.lists/videos_list");
            }
            $e->set('videos', $videos);
        }

    }

    public function hashtags_filter($event)
    {

        $e = $event->subject();
        App::import('Model', 'Video.Video');
        $this->Video = new Video();

        if (isset($event->data['type']) && $event->data['type'] == 'videos') {
            $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;
            $videos = $this->Video->getVideoHashtags($event->data['item_ids'], RESULTS_LIMIT, $page);
            $e->set('videos', $videos);
            $e->set('result', 1);
            $e->set('more_url', '/search/hashtags/' . $e->params['pass'][0] . '/videos/page:' . ($page + 1));
            $e->set('element_list_path', "Video.lists/videos_list");
        }
        $table_name = $this->Video->table;
        if (isset($event->data['type']) && $event->data['type'] == 'all' && !empty($event->data['item_groups'][$table_name])) {
            $event->result['videos'] = null;

            $videos = $this->Video->getVideoHashtags($event->data['item_groups'][$table_name], 5);

            if (!empty($videos)) {
                $event->result['videos']['header'] = __('Videos');
                $event->result['videos']['icon_class'] = 'videocam';
                $event->result['videos']['view'] = "Video.lists/videos_list";
                $e->set('videos', $videos);
            }
        }
    }

    private function _filterVideo($videos)
    {
        if (!empty($videos)) {
            $friendModel = MooCore::getInstance()->getModel('Friend');
            $viewer = MooCore::getInstance()->getViewer();
            foreach ($videos as $key => &$video) {
                $owner_id = $video[key($video)]['user_id'];
                $privacy = isset($video[key($video)]['privacy']) ? $video[key($video)]['privacy'] : 1;
                if (empty($viewer)) { // guest can view only public item
                    if ($privacy != PRIVACY_EVERYONE) {
                        unset($videos[$key]);
                    }
                } else { // viewer
                    $aFriendsList = array();
                    $aFriendsList = $friendModel->getFriendsList($owner_id);
                    if ($privacy == PRIVACY_ME) { // privacy = only_me => only owner and admin can view items
                        if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id) {
                            unset($videos[$key]);
                        }
                    } else if ($privacy == PRIVACY_FRIENDS) { // privacy = friends => only owner and friendlist of owner can view items
                        if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id && !in_array($viewer['User']['id'], array_keys($aFriendsList))) {
                            unset($videos[$key]);
                        }
                    } else {

                    }
                }
            }
        }

        return $videos;
    }

    public function hashtagEnable($event)
    {
        $enable = Configure::read('Video.video_hashtag_enabled');
        $event->result['videos']['enable'] = $enable;
    }

    public function deactivate($event)
    {
        $videoModel = MooCore::getInstance()->getModel('Video.Video');
        $videoCategory = $videoModel->find('all', array(
                'conditions' => array('Video.user_id' => $event->data['uid']),
                'group' => array('Video.category_id'),
                'fields' => array('category_id', '(SELECT count(*) FROM ' . $videoModel->tablePrefix . 'videos WHERE category_id=Video.category_id AND user_id = ' . $event->data['uid'] . ') as count')
            )
        );
        $videoCategory = Hash::combine($videoCategory, '{n}.Video.category_id', '{n}.{n}.count');
        $event->result['Video'] = $videoCategory;
    }
    
    /* S3 Upload Video */
    public function storageGetVideoLocal($oEvent) {
        $oEvent->result['url'] = FULL_BASE_LOCAL_URL . $oEvent->subject()->assetUrl($oEvent->data['extra']['path']);
    }

    public function storageGetVideoAmazon($oEvent) {
        $oStorageHelper = $oEvent->subject();
        $sPath = $oEvent->data['extra']['path'];
        $sDirPath = WWW_ROOT . str_replace("/", DS, $sPath);
        $oEvent->result['url'] = $oStorageHelper->getAwsURL($oEvent->data['oid'], 'uploadVideo', $sPath, $sDirPath, array('key' => "webroot/" . $sPath, 'path' => $sPath));
    }

    public function storageGetVideoFilePath($oEvent) {
        $sPath = WWW_ROOT . str_replace("/", DS, $oEvent->data['extra']['path']);
        $oEvent->result['path'] = $sPath;
    }

    public function storageAmazonPutObjectSuccessCallback($oEvent) {
        $sPath = $oEvent->data['path'];
        if (Configure::read('Storage.storage_amazon_delete_image_after_adding') == "1" && file_exists($sPath)) {
            $file = new File($sPath);
            $file->delete();
            $file->close();
        }
    }

    public function storageTaskAwsCronTransfer($oEvent) {
        $oStorageTask = $oEvent->subject();
        $oVideoModel = MooCore::getInstance()->getModel('Video.Video');
        $aVideos = $oVideoModel->find('all', array(
            'conditions' => array("Video.id > " => $oStorageTask->getMaxTransferredItemId("uploadVideo")),
            'fields' => array('Video.id', 'Video.destination'),
            'order' => array('Video.id'),
            'limit' => 1,
        ));

        if ($aVideos) {
            foreach ($aVideos as $aVideo) {
                if (!empty($aVideo["Video"]["destination"])) {
                    $sPath = 'uploads/videos/thumb/' . $aVideo['Video']['id'] . '/' . $aVideo['Video']['destination'];
                    
                    $sDirPath = WWW_ROOT . str_replace("/", DS, $sPath);
                    $oStorageTask->transferObject($aVideo["Video"]['id'], 'uploadVideo', $sPath, $sDirPath, array('key' => "webroot/" . $sPath, 'path' => $sPath));
                }
            }
        }
        /* S3 Upload Video */
    }
}
