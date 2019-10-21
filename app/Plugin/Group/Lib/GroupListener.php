<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class GroupListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'UserController.deleteUserContent' => 'deleteUserContent',
            'Controller.Search.search' => 'search',
            'Model.Activity.afterSetParamsConditionsOr' => 'afterSetParamsConditionsOr',
            'Controller.Search.suggestion' => 'suggestion',
            'Controller.Search.hashtags' => 'hashtags',
            'Controller.Search.hashtags_filter' => 'hashtags_filter',
            'Controller.Widgets.tagCoreWidget' => 'hashtagEnable',
            'Controller.User.deactivate' => 'deactivate',
            'Plugin.Controller.Group.afterSaveGroup' => 'processEventAfterSave',
            'UserController.doSaveUser' => 'doSaveUser',
            'MooView.beforeRender' => 'beforeRender',
            'Controller.User.afterDeactive' => 'afterDeactiveUser',
            'Controller.User.afterEdit' => 'afterEditUser',
            'Model.afterSave' => 'afterSaveModel',
        	'MooApp.Lib.MooAppListener.getListActionNotifyCronjob' => 'getListActionNotifyCronjob'
        );
    }
    
    public function getListActionNotifyCronjob($event)
    {
    	$cron_action = &$event->data['cron_action'];
    	$cron_action = array_merge($cron_action,array('group_status','group_status_photo','group_video_create','group_topic_create'));
    }

    public function afterSaveModel($event)
    {
        $model = $event->subject();
        $type = ($model->plugin) ? $model->plugin . '_' : '' . get_class($model);
        if ($type == 'Activity') {
            $created = $event->data[0];
            if ($created) {
                if (strtolower($model->data['Activity']['type']) == 'group_group' && $model->data['Activity']['action'] != 'group_join') {
                    $group_id = $model->data['Activity']['target_id'];
                    $user_id = $model->data['Activity']['user_id'];
                    $groupUserModel = MooCore::getInstance()->getModel("Group.GroupUser");
                    $notificationModel = MooCore::getInstance()->getModel("Notification");
                    $groupModel = MooCore::getInstance()->getModel("Group.Group");
                    $group = $groupModel->findById($group_id);

                    $users = $groupUserModel->getUsersList($group_id, array(GROUP_USER_MEMBER, GROUP_USER_ADMIN));

                    $url = '/users/view/' . $user_id . '/activity_id:' . $model->id;
                    $action = 'group_status';
                    switch ($model->data['Activity']['action']) {
                        case 'photos_add':
                            $action = 'group_status_photo';
                            break;
                        case 'video_create':
                            $action = 'group_video_create';
                            $videoModel = MooCore::getInstance()->getModel('Video.Video');
                            $video = $videoModel->findById($model->data['Activity']['item_id']);
                            $url = $video['Video']['moo_url'];
                            $videoModel->id = $model->data['Activity']['item_id'];
                            break;

                        case 'topic_create':
                            $action = 'group_topic_create';
                            $topicModel = MooCore::getInstance()->getModel('Topic.Topic');
                            $topic = $topicModel->findById($model->data['Activity']['item_id']);
                            $url = $topic['Topic']['moo_url'];
                            $topicModel->id = $model->data['Activity']['item_id'];
                            break;
                    }
                    $settingModel = MooCore::getInstance()->getModel("Group.GroupNotificationSetting");
                    $userBlockModel = MooCore::getInstance()->getModel("UserBlock");
                    $user_blocks = $userBlockModel->getBlockedUsers($user_id);
                    foreach ($users as $member_id) {
                        if ($member_id != $user_id) {
                            if (in_array($member_id, $user_blocks)) {
                                continue;
                            }
                            if ($settingModel->getStatus($group_id, $member_id)) {
                                $notificationModel->clear();
                                $notificationModel->save(array(
                                    'user_id' => $member_id,
                                    'sender_id' => $user_id,
                                    'action' => $action,
                                    'url' => $url,
                                    'params' => $group_id,
                                    'plugin' => 'Group',
                                ));
                            }
                        }
                    }
                }
            }
        }
    }

    public function afterEditUser($event)
    {
        $cuser = $event->data['item'];

        $categoryModel = Moocore::getInstance()->getModel('Category');
        $groupModel = Moocore::getInstance()->getModel('Group.Group');
        $groupCategory = $categoryModel->find('all', array('conditions' => array('Category.type' => 'Group')));

        foreach ($groupCategory as $item) {
            $category_id = $item['Category']['id'];
            $groups_count = $groupModel->find('count', array('conditions' => array(
                'Group.category_id' => $category_id,
                'User.active' => true
            )));
            $categoryModel->updateAll(array('Category.item_count' => $groups_count), array('Category.id' => $category_id));
        }
    }

    public function afterDeactiveUser($event)
    {
        $cuser = $event->data['item'];

        $categoryModel = Moocore::getInstance()->getModel('Category');
        $groupModel = Moocore::getInstance()->getModel('Group.Group');
        $groupCategory = $categoryModel->find('all', array('conditions' => array('Category.type' => 'Group')));

        foreach ($groupCategory as $item) {
            $category_id = $item['Category']['id'];
            $groups_count = $groupModel->find('count', array('conditions' => array(
                'Group.category_id' => $category_id,
                'User.active' => true
            )));
            $categoryModel->updateAll(array('Category.item_count' => $groups_count), array('Category.id' => $category_id));
        }
    }


    public function beforeRender($event)
    {
        $view = $event->subject();
        if ($view instanceof MooView) {
            $view->addPhraseJs(array(
                'drag_or_click_here_to_upload_photo' => __("Drag or click here to upload photo"),
                'are_you_sure_you_want_to_remove_this_member' => __('Are you sure you want to remove this member?'),
                'are_you_sure_you_want_to_demote_this_group_admin' => __('Are you sure you want to demote this group admin?'),
                'are_you_sure_you_want_to_remove_this_video' => __('Are you sure you want to remove this video?'),
                'are_you_sure_you_want_to_make_this_member_a_group_admin' => __('Are you sure you want to make this member a group admin?'),
                'are_you_sure_you_want_to_leave_this_group' => __('Are you sure you want to leave this group?'),
                'are_you_sure_you_want_to_remove_this_group_br_all_group_contents_will_also_be_deleted' => __('Are you sure you want to remove this group?<br />All group contents will also be deleted!')
            ));
        }
    }

    public function doSaveUser($event)
    {
        $controller = $event->subject();
        $data = isset($event->data['data']) ? $event->data['data'] : '';
        //if user already have some group's invites
        $groupUserInviteModel = MooCore::getInstance()->getModel('Group.GroupUserInvite');
        $groups = $groupUserInviteModel->find('all', array(
            'conditions' => array(
                'email' => $data['email']
            ),
            'group' => '`GroupUserInvite`.`group_id`'
        ));
        if (!empty($groups)) {
            foreach ($groups as $group) {
                $user_data[] = array('group_id' => $group['GroupUserInvite']['group_id'], 'user_id' => $controller->User->id, 'status' => GROUP_USER_INVITED);
            }
        }
        if (!empty($user_data)) {
            $groupUserModel = MooCore::getInstance()->getModel('Group.GroupUser');
            $groupUserModel->saveAll($user_data);
            $groupUserInviteModel->deleteAll(array('GroupUserInvite.email' => $data['email']));
            if ($controller->Session->read('accept_group_invited_user')) {
                $group_id = $controller->Session->read('group_invited_user');
                $groupUserModel->clear();
                $groupUserModel->updateAll(array('GroupUser.status' => 1), array('GroupUser.group_id' => $group_id, 'GroupUser.user_id' => $controller->User->id));
            }
            $controller->Session->delete('accept_group_invited_user');
            $controller->Session->delete('group_invited_user');
            $controller->Session->delete('group_invited_user_email');
        }
        //Auto add this user to group if this user was invited via email and sign up after join group
    }

    public function processEventAfterSave($event)
    {
        $v = $event->subject();

        // load feed model
        $this->Activity = ClassRegistry::init('Activity');

        // find activity which belong to event just created
        $activity = $this->Activity->find('first', array('conditions' => array(
            'Activity.item_type' => 'Group_Group',
            'Activity.item_id' => $event->data['id'],
        )));

        if (!empty($activity)) {
            $share = false;
            // only enable share feature for public event
            if ($event->data['type'] == PRIVACY_EVERYONE) {
                $share = true;
            }
            $this->Activity->clear();
            $this->Activity->updateAll(array('Activity.share' => $share), array('Activity.id' => $activity['Activity']['id']));
        }

        //update video type

        $this->Video = ClassRegistry::init('Video.Video');
        $videos = $this->Video->findAllByGroupId($event->data['id']);
        if (!empty($videos)) {
            foreach ($videos as &$video) {
                $this->Video->clear();
                $privacy = $event->data['type'];
                if ($privacy != PRIVACY_EVERYONE) {
                    $privacy = ($privacy == PRIVACY_RESTRICTED) ? PRIVACY_FRIENDS : PRIVACY_ME;
                }
                $this->Video->updateAll(array('Video.privacy' => $privacy), array('Video.id' => $video['Video']['id']));
            }
        }

    }

    function afterSetParamsConditionsOr($event)
    {
        $groupModel = MooCore::getInstance()->getModel("Group.Group");        

        $groupUserModel = MooCore::getInstance()->getModel("Group.GroupUser");
        $groups = $groupUserModel->getMyGroupsList($event->data['param']);
        if (!count($groups))
        	$groups = array(0);

        $data = array(array('Activity.type' => 'Group_Group', 'Activity.action' => 'group_join'),
            array('Activity.type' => 'Group_Group', 'Activity.target_id' => $groups));
        $event->result[] = $data;
    }

    function deleteUserContent($event)
    {
        App::import('Group.Model', 'Group');
        App::import('Group.Model', 'GroupUser');

        $this->Group = new Group();
        $this->GroupUser = new GroupUser();

        $groups = $this->Group->findAllByUserId($event->data['aUser']['User']['id']);
        foreach ($groups as $group) {
            $this->Group->delete($group['Group']['id']);
        }

        $this->GroupUser->deleteAll(array('GroupUser.user_id' => $event->data['aUser']['User']['id']), true, true);
    }

    public function search($event)
    {
        $e = $event->subject();
        App::import('Model', 'Group.Group');
        $this->Group = new Group();
        $results = $this->Group->getGroups('search', $e->keyword, 1);
        if (count($results) > 5)
            $results = array_slice($results, 0, 5);
        if (empty($results))
            $results = $this->Group->getGroupSuggestion($e->keyword, 5);
        if (isset($e->plugin) && $e->plugin == 'Group') {
            $e->set('groups', $results);
            if (!empty($results))
                $e->set('notEmpty', 1);
            $e->render("Group.Elements/lists/groups_list");
        } else {
            $event->result['Group']['header'] = __("Groups");
            $event->result['Group']['icon_class'] = "group_work";
            $event->result['Group']['view'] = "lists/groups_list";
            if (!empty($results))
                $event->result['Group']['notEmpty'] = 1;
            $e->set('groups', $results);
        }
    }

    public function suggestion($event)
    {
        $e = $event->subject();
        App::import('Model', 'Group.Group');
        $this->Group = new Group();

        $event->result['group']['header'] = __('Groups');
        $event->result['group']['icon_class'] = 'group_work';

        if (isset($event->data['type']) && $event->data['type'] == 'group') {
            $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;
            $groups = $this->Group->getGroups('search', $event->data['searchVal'], $page);
            $more_groups = $this->Group->getGroups('search', $event->data['searchVal'], $page + 1);
            $more_result = 0;
            if (!empty($more_groups))
                $more_result = 1;
            if (empty($groups))
                $groups = $this->Group->getGroupSuggestion($event->data['searchVal'], RESULTS_LIMIT, $page);

            $e->set('groups', $groups);
            $e->set('result', 1);
            $more_url = isset($e->params['pass'][1]) ? '/search/suggestion/group/' . $e->params['pass'][1] . '/page:' . ($page + 1) : '';
            $e->set('more_url', $more_url);
            $e->set('element_list_path', "Group.lists/groups_list");
            $e->set('more_result', $more_result);
        }
        if (isset($event->data['type']) && $event->data['type'] == 'all') {
            $event->result['group'] = null;
            $groups = $this->Group->getGroups('search', $event->data['searchVal'], 1, 2);
            if (count($groups) > 2) {
                $groups = array_slice($groups, 0, 2);
            }
            if (empty($groups))
                $groups = $this->Group->getGroupSuggestion($event->data['searchVal'], 2);

            if (!empty($groups)) {
                $event->result['group'] = array(__('Group'));
                $helper = MooCore::getInstance()->getHelper("Group_Group");
                foreach ($groups as $index => &$detail) {
                    $index++;
                    $event->result['group'][$index]['id'] = $detail['Group']['id'];
                    if (!empty($detail['Group']['photo']))
                    	$event->result['group'][$index]['img'] = $helper->getImage($detail,array('prefix'=>'75_square'));
                    $event->result['group'][$index]['title'] = $detail['Group']['name'];
                    $event->result['group'][$index]['find_name'] = 'Find Groups';
                    $event->result['group'][$index]['icon_class'] = 'group_work';
                    $event->result['group'][$index]['view_link'] = 'groups/view/';

                    $privacy = 'Public';
                    switch ($detail['Group']['type']) {
                        case PRIVACY_PUBLIC:
                            $privacy = __('Public');
                            break;

                        case PRIVACY_RESTRICTED:
                            $privacy = __('Restricted');
                            break;

                        case PRIVACY_PRIVATE:
                            $privacy = __('Private');
                            break;
                    }
                    $event->result['group'][$index]['more_info'] = __n('%s member', '%s members', $detail['Group']['group_user_count'], $detail['Group']['group_user_count']) .
                        ' ' . $privacy;
                }
            }
        }
    }

    public function hashtags($event)
    {
        $enable = Configure::read('Group.group_hashtag_enabled');
        $e = $event->subject();
        App::import('Model', 'Group.Group');
        $this->Group = new Group();
        App::import('Model', 'Tag');
        $this->Tag = new Tag();
        $groups = array();
        $uid = CakeSession::read('uid');
        $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;

        if ($enable) {
            if (isset($event->data['type']) && $event->data['type'] == 'groups') {
                $groups = $this->Group->getGroupHashtags($event->data['item_ids'], RESULTS_LIMIT, $page);
                $groups = $this->_filterGroup($groups);
            }
            $table_name = $this->Group->table;
            if (isset($event->data['type']) && $event->data['type'] == 'all' && !empty($event->data['item_groups'][$table_name])) {
                $groups = $this->Group->getGroupHashtags($event->data['item_groups'][$table_name], 5);
                $groups = $this->_filterGroup($groups);
            }
        }

        // get tagged item
        $tag = h(urldecode($event->data['search_keyword']));
        $tags = $this->Tag->find('all', array('conditions' => array(
            'Tag.type' => 'Group_Group',
            'Tag.tag' => $tag
        )));
        $group_ids = Hash::combine($tags, '{n}.Tag.id', '{n}.Tag.target_id');

        $friendModel = MooCore::getInstance()->getModel('Friend');


        $items = $this->Group->find('all', array('conditions' => array(
            'Group.id' => $group_ids
        ),
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
        $groups = array_merge($groups, $items);
        //only display 5 items on All Search Result page
        if (isset($event->data['type']) && $event->data['type'] == 'all') {
            $groups = array_slice($groups, 0, 5);
        }
        $groups = array_map("unserialize", array_unique(array_map("serialize", $groups)));
        if (!empty($groups)) {
            $event->result['groups']['header'] = __('Groups');
            $event->result['groups']['icon_class'] = 'group_work';
            $event->result['groups']['view'] = "Group.lists/groups_list";

            if (isset($event->data['type']) && $event->data['type'] == 'groups') {
                $e->set('result', 1);
                $e->set('more_url', '/search/hashtags/' . $e->params['pass'][0] . '/groups/page:' . ($page + 1));
                $e->set('element_list_path', "Group.lists/groups_list");
            }
            $e->set('groups', $groups);

        }

    }

    public function hashtags_filter($event)
    {
        $e = $event->subject();
        App::import('Model', 'Group.Group');
        $this->Group = new Group();

        if (isset($event->data['type']) && $event->data['type'] == 'groups') {
            $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;
            $groups = $this->Group->getGroupHashtags($event->data['item_ids'], RESULTS_LIMIT, $page);
            $e->set('groups', $groups);
            $e->set('result', 1);
            $e->set('more_url', '/search/hashtags/' . $e->params['pass'][0] . '/groups/page:' . ($page + 1));
            $e->set('element_list_path', "Group.lists/groups_list");
        }
        $table_name = $this->Group->table;
        if (isset($event->data['type']) && $event->data['type'] == 'all' && !empty($event->data['item_groups'][$table_name])) {
            $event->result['groups'] = null;

            $groups = $this->Group->getGroupHashtags($event->data['item_groups'][$table_name], 5);

            if (!empty($groups)) {
                $event->result['groups']['header'] = __('Groups');
                $event->result['groups']['icon_class'] = 'group_work';
                $event->result['groups']['view'] = "Group.lists/groups_list";
                $e->set('groups', $groups);

            }
        }
    }

    private function _filterGroup($groups)
    {
        if (!empty($groups)) {
            $groupUserModel = MooCore::getInstance()->getModel('Group.GroupUser');
            $viewer = MooCore::getInstance()->getViewer();
            foreach ($groups as $key => &$group) {
                $owner_id = $group[key($group)]['user_id'];
                $privacy = isset($group[key($group)]['type']) ? $group[key($group)]['type'] : 1;
                if (empty($viewer)) { // guest can view only public item
                    if ($privacy != PRIVACY_ME) {
                        unset($groups[$key]);
                    }
                } else { // viewer
                    $myStatus = $groupUserModel->getMyStatus($viewer['User']['id'], $group[key($group)]['id']);
                    if ($privacy == PRIVACY_FRIENDS) { // privacy = me => only owner member group can view items
                        if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id && empty($myStatus)) {
                            unset($groups[$key]);
                        }
                    } else {

                    }
                }
            }
        }
        return $groups;
    }

    public function hashtagEnable($event)
    {
        $enable = Configure::read('Group.group_hashtag_enabled');
        $event->result['groups']['enable'] = $enable;
    }

    public function deactivate($event)
    {
        $groupModel = MooCore::getInstance()->getModel('Group.Group');
        $groupCategory = $groupModel->find('all', array(
                'condition' => array('Group.user_id' => $event->data['uid']),
                'group' => array('Group.category_id'),
                'fields' => array('category_id', '(SELECT count(*) FROM ' . $groupModel->tablePrefix . 'groups WHERE category_id=Group.category_id AND user_id = ' . $event->data['uid'] . ') as count')
            )
        );
        $groupCategory = Hash::combine($groupCategory, '{n}.Group.category_id', '{n}.{n}.count');
        $event->result['Group'] = $groupCategory;
    }


}
