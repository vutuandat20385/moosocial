<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

App::uses('ClassRegistry', 'Utility');
App::uses('CakeEvent', 'Event');
App::uses('GroupListener', 'Event');


class GroupsController extends GroupAppController {



    public $paginate = array(
        'order' => array(
            'Group.id' => 'desc'
        ),
        'findType' => 'translated',
    );

    public function beforeFilter() {
        parent::beforeFilter();
        $this->set('uid', $this->Auth->user('id'));
    }

    public function index($cat_id = null) {
        $cat_id = intval($cat_id);
        $uid = $this->Auth->user('id');

        $this->loadModel('Group.GroupUser');
        // caching

        $more_result = 0;
        $role_id = $this->_getUserRoleId();

        if (!empty($cat_id)){
            $groups = $this->Group->getGroups('category', $cat_id,1,null,$role_id,null);
            $more_groups = $this->Group->getGroups('category', $cat_id,2,null,$role_id,null);
        }
        else{
            //get users of this group
            $this->loadModel('Group.GroupUser');
            $groupId = $this->GroupUser->findAllByUserId($uid,array('group_id'));
            if(!empty($groupId)){
                $groupId = implode(',',Hash::extract($groupId,'{n}.GroupUser.group_id'));
            }else
                $groupId = '';


            $groups = $this->Group->getGroups(null,$uid,1,null,$role_id,$groupId);
            $groups = Hash::sort($groups,'{n}.Group.id',' desc');
            $more_groups = $this->Group->getGroups(null,$uid,2,null,$role_id,$groupId);
        }
        if(!empty($more_groups))
            $more_result = 1;
        $this->set('groups', $groups);

        $this->set('cat_id', $cat_id);
        $this->set('title_for_layout', '');
        $this->set('more_result',$more_result);
    }

    /*
     * Browse events based on $type
     * @param string $type - possible value: all (default), my, home, friends, category
     */

    public function browse($type = null, $param = null,$isRedirect = true) {

        if($isRedirect) {
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        }
        else {
            $page = $this->request->query('page') ? $this->request->query('page') : 1;
        }
        $url = (!empty($param) ) ? $type . '/' . $param : $type;
        $uid = $this->Auth->user('id');
        $role_id = $this->_getUserRoleId();
        $more_result = 0;
        switch ($type) {
            case 'home':
            case 'my':
            case 'friends':
                $this->_checkPermission();
                $this->loadModel('Group.GroupUser');
                $groups = $this->GroupUser->getGroups($type, $uid, $page,$role_id);
                $more_groups = $this->GroupUser->getGroups($type, $uid, $page +1,$role_id);
                break;

            case 'search':
                $param = urldecode($param);

                if (!Configure::read('core.guest_search') && empty($uid))
                    $this->_checkPermission();
                else{
                    $groups = $this->Group->getGroups('search', $param, $page,null,$role_id,null);
                    $more_groups = $this->Group->getGroups('search', $param, $page +1,null,$role_id,null);
                }
                break;

            default: // all, category
                if ($type != 'category'){
                    $param = $uid;
                }
                //get users of this group
                $this->loadModel('Group.GroupUser');
                $groupId = $this->GroupUser->findAllByUserId($uid,array('group_id'));
                if(!empty($groupId)){
                    $groupId = implode(',',Hash::extract($groupId,'{n}.GroupUser.group_id'));
                }
                else
                    $groupId = '';

                $groups = $this->Group->getGroups($type, $param, $page,null, $role_id, $groupId);
                $groups = Hash::sort($groups,'{n}.Group.id',' desc');
                $more_groups = $this->Group->getGroups($type, $param, $page +1,null, $role_id, $groupId);
        }

        if(!empty($more_groups))
            $more_result = 1;

        $this->set('groups', $groups);
        $this->set('more_url', '/groups/browse/' . h($url) . '/page:' . ( $page + 1 ));
        $this->set('more_result',$more_result);
        if($isRedirect && $this->theme != "mooApp"){
            if ($page == 1 && $type == 'home'){
                $this->render('/Elements/ajax/home_group');
            }
            else{
                if ($this->request->is('ajax')){
                    $this->render('/Elements/lists/groups_list');
                }
                else {
                    $this->render('/Elements/lists/groups_list_m');
                }
            }
        }
        else {
            if($type == 'category') $this->set('categoryId', $param);
            $this->set('type', $type);
        }
    }

    /*
     * Show add/edit group form
     * @param int $id - group id to edit
     */

    public function create($id = null) {
        $id = intval($id);
        $this->_checkPermission(array('confirm' => true));
        $this->_checkPermission(array('aco' => 'group_create'));

        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();
        $categories = $this->Category->getCategoriesList('Group', $role_id);

        if (!empty($id)) { // editing
            $group = $this->Group->findById($id);
            $this->_checkExistence($group);

            // check edit permission
            //$this->loadModel('Group.GroupUser');
            //$admins_list = $this->GroupUser->getUsersList($id, GROUP_USER_ADMIN);
            $this->_checkPermission(array('admins' => array($group['Group']['user_id'])));

            $this->set('title_for_layout', __( 'Edit Group'));
        } else {
            $group = $this->Group->initFields();
            $this->set('title_for_layout', __( 'Add New Group'));
        }

        $this->set('group', $group);
        $this->set('categories', $categories);
    }

    /*
     * Save add/edit form
     */

    public function save($isReturn = false) {
        $this->_checkPermission(array('confirm' => true));
        $this->loadModel('Group.GroupUser');

        $this->autoRender = false;
        $uid = $this->Auth->user('id');

        if (!empty($this->request->data['id'])) { // edit group
            // check edit permission			
            //$admins_list = $this->GroupUser->getUsersList($this->request->data['id'], GROUP_USER_ADMIN);
            $group = $this->Group->findById($this->request->data['id']);
            $this->_checkExistence($group);

            $this->_checkPermission(array('admins' => array($group['Group']['user_id'])));
            $this->Group->id = $this->request->data['id'];
        } else
            $this->request->data['user_id'] = $uid;

        $this->Group->set($this->request->data);
        $this->_validateData($this->Group);

        if ($this->Group->save()) {
            if (!empty($this->request->data['photo'])) {
                $newpath = WWW_ROOT . 'uploads' . DS . 'groups';

                if (!file_exists($newpath))
                    mkdir($newpath, 0755, true);

                if (file_exists(WWW_ROOT . 'uploads' . DS . 'tmp' . DS . $this->request->data['photo'])){
                    copy(WWW_ROOT . 'uploads' . DS . 'tmp' . DS . $this->request->data['photo'], $newpath . DS . $this->request->data['photo']);
                    copy(WWW_ROOT . 'uploads' . DS . 'tmp' . DS . 't_' . $this->request->data['photo'], $newpath . DS . 't_' . $this->request->data['photo']);

                    unlink(WWW_ROOT . 'uploads' . DS . 'tmp' . DS . $this->request->data['photo']);
                    unlink(WWW_ROOT . 'uploads' . DS . 'tmp' . DS . 't_' . $this->request->data['photo']);
                }

            }
            $event = new CakeEvent('Plugin.Controller.Group.afterSaveGroup', $this, array(
                'uid' => $uid,
                'id' => $this->Group->id,
                'type' =>$this->request->data['type']
            ));

            $this->getEventManager()->dispatch($event);

            if (empty($this->request->data['id'])) { // add group
                // make the group creator admin
                $this->GroupUser->save(array('group_id' => $this->Group->id,
                    'user_id' => $uid,
                    'status' => GROUP_USER_ADMIN
                ));
            }
            if(!$isReturn) {
                $response['result'] = 1;
                $response['id'] = $this->Group->id;

                echo json_encode($response);
            }
            else {
                return $this->Group->id;
            }
        }
    }

    public function view($id = null,$group_name = null,$invite_checksum = null) {
        $id = intval($id);

        if($invite_checksum != null)
        {
            $groupUserInviteModel = MooCore::getInstance()->getModel('Group.GroupUserInvite');
            $isExist = $groupUserInviteModel->find('first',array(
                'conditions' => array(
                    'GroupUserInvite.group_id' => $id,
                    'GroupUserInvite.invite_checksum' => $invite_checksum,
                )
            ));
            if(!empty($isExist))
            {
                $this->Session->write('group_invited_user',$id);
                $this->Session->write('group_invited_user_email',$isExist['GroupUserInvite']['email']);
                $this->set('invited_user',1);
            }
        }

        // caching

        $this->Group->recursive = 2;
        $group= $this->Group->findById($id);
        if ($group['Category']['id'])
        {
            foreach ($group['Category']['nameTranslation'] as $translate)
            {
                if ($translate['locale'] == Configure::read('Config.language'))
                {
                    $group['Category']['name'] = $translate['content'];
                    break;
                }
            }
        }
        $this->Group->recursive = 0;

        $this->_checkExistence($group);
        $this->_checkPermission(array('aco' => 'group_view'));
        //$this->_checkPermission( array('user_block' => $group['Group']['user_id']) );
        $members = array();
        $admins = array();

        if (!empty($this->request->named['tab'])) { // open a specific tab
            $this->set('tab', $this->request->named['tab']);
        }
        // get group users
        $this->loadModel('Group.GroupUser');
        // get group users for adminList and memberList widget
        $group['Group']['group_user_count'] = $this->GroupUser->getBlockedUserCount($id);
        if ($this->request->is('requested')) {
            $data = array(
                'groupAdmin' => array(),
                'groupAdminCnt' => 0,
                'groupMembers' => array(),
                'groupMembersCnt' => 0
            );

            $num_group_admin = isset($this->request->named['num_group_admin']) ? $this->request->named['num_group_admin'] : 10;
            $num_group_member = isset($this->request->named['num_group_member']) ? $this->request->named['num_group_member'] : 10;

            // caching
            $group_members = Cache::read('group_' . $id . '_members_widget', 'group');
            if (!$group_members){
                $group_members = $this->GroupUser->getUsers($id, GROUP_USER_MEMBER, null, $num_group_member);
                Cache::write('group_' . $id . '_members_widget', $group_members, 'group');
            }

            $group_admins = Cache::read('group_' . $id . '_admins_widget', 'group');
            if (!$group_admins){
                $group_admins = $this->GroupUser->getUsers($id, GROUP_USER_ADMIN, null, $num_group_admin);
                Cache::write('group_' . $id . '_admins_widget', $group_admins, 'group');
            }

            $admin_count = Cache::read('admin_count_group_' . $id, 'group');
            if (!$admin_count){
                $admin_count = $this->GroupUser->getUserCount($id, GROUP_USER_ADMIN);
                Cache::write('admin_count_group_' . $id, $admin_count, 'group');
            }

            $member_count = $group['Group']['group_user_count'] - $admin_count;

            $data['groupAdmin'] = $group_admins;
            $data['groupAdminCnt'] = $admin_count;
            $data['groupMembers'] = $group_members;
            $data['groupMembersCnt'] = $member_count;

            return $data;
        }

        // caching

        // Update for App view
        $user_blocks = array();
        $cuser = $this->_getUser();
        if($cuser){
            $this->loadModel('UserBlock');
            $user_blocks = $this->UserBlock->getBlockedUsers($cuser['id']);
        }

        if(empty($user_blocks)){
            $group_admins = Cache::read('group_' . $id . '_admins', 'group');
            if (!$group_admins){
                $group_admins = $this->GroupUser->getUsers($id, GROUP_USER_ADMIN, null, 5);
                if (empty($group_admins)) $group_admins = array(0);
                Cache::write('group_' . $id . '_admins', $group_admins, 'group');
            }

            $members = Cache::read('group_' . $id . '_members', 'group');
            if (!$members){
                $members = $this->GroupUser->getUsers($id, GROUP_USER_MEMBER, null, 10);
                if (empty($members)) $members = array(0);
                Cache::write('group_' . $id . '_members', $members, 'group');
            }

        }else{
            $group_admins = $this->GroupUser->getUsers($id, GROUP_USER_ADMIN, null, 5);
            $members = $this->GroupUser->getUsers($id, GROUP_USER_MEMBER, null,10);
        }



        $admin_count = Cache::read('admin_count_group_' . $id, 'group');
        if (!$admin_count){
            $admin_count = $this->GroupUser->getUserCount($id, GROUP_USER_ADMIN);
            Cache::write('admin_count_group_' . $id, $admin_count, 'group');
        }

        $member_count = $group['Group']['group_user_count'] - $admin_count;

        $this->_getGroupDetail($group);

        $this->set('members', $members);
        $this->set('group_admins', $group_admins);
        $this->set('member_count', $member_count);
        $this->set('admin_count', $admin_count);
        $this->loadModel('Photo.Photo');
        $photo_count = $this->Photo->getPhotosCount('Group_Group',$id);
        $this->set('photo_count', $photo_count);

        $this->set('group_id', $id);


        $this->set('group', $group);
        $this->set('title_for_layout', $group['Group']['name']);
        $cuser = $this->_getUser();
        if ($group['Group']['type'] != PRIVACY_PRIVATE || ($cuser && $cuser['Role']['is_admin'])) {
            $description = $this->getDescriptionForMeta($group['Group']['description']);
            if ($description) {
                $this->set('description_for_layout', $description);
                $this->set('mooPageKeyword', $this->getKeywordsForMeta($description));
            }
        }
        // set og:image
        if ($group['Group']['photo']) {
            $mooHelper = MooCore::getInstance()->getHelper('Core_Moo');
            $this->set('og_image', $mooHelper->getImageUrl($group, array('prefix' => '850')));

        }

        $event = new CakeEvent('Controller.groupDetailMenu', $this, array('passParams' => true, 'aGroup' => $group));
        $this->getEventManager()->dispatch($event);
        $this->set('group_menu', $event->result['menu']);

        $groupFeeds = $this->Feeds->get();
        $this->set('groupActivities',$groupFeeds);
    }

    public function details($id = null) {
        $id = intval($id);
        $this->loadModel('Group.GroupUser');

        $group = $this->Group->findById($id);
        $this->_getGroupDetail($group);

        $this->set('group', $group);

        $photo_count = $this->Photo->getPhotosCount('Group_Group',$id);
        $this->set('photo_count', $photo_count);

        $groupFeeds = $this->Feeds->get();
        $this->set('groupActivities',$groupFeeds);

        $this->render('/Elements/ajax/group_detail');

    }

    private function _getGroupDetail($group) {
        MooCore::getInstance()->setSubject($group);
        $uid = $this->Auth->user('id');
        $this->loadModel('Group.GroupUser');
        if ($uid) {
            $my_status = $this->GroupUser->getMyStatus($uid, $group['Group']['id']);

            $is_member = $this->GroupUser->isMember($uid, $group['Group']['id']);

            $this->set('my_status', $my_status);
            $this->set('is_member', $is_member);

            if (!empty($my_status) && $my_status['GroupUser']['status'] == GROUP_USER_ADMIN) {
                $request_count = Cache::read('request_count'.$group['Group']['id'], 'group');
                if (!$request_count){
                    $request_count = $this->GroupUser->find('count', array('conditions' => array('group_id' => $group['Group']['id'],
                        'status' => GROUP_USER_REQUESTED,
                        'User.active' => 1,
                    )
                    ));
                    Cache::write('request_count'.$group['Group']['id'], $request_count, 'group');
                }

                $this->set('request_count', $request_count);
            }
        }

        $this->loadModel('Photo.Photo');

        $photos = Cache::read('photos_group_'.$group['Group']['id'], 'group');

        $limit = Configure::read('Photo.photo_item_per_pages');
        $photos = $this->Photo->getPhotos('Group_Group', $group['Group']['id'], null, $limit);

        $admins = Cache::read('admin_list_group_' . $group['Group']['id'], 'group');
        if (!$admins){
            $admins = $this->GroupUser->getUsersList($group['Group']['id'], GROUP_USER_ADMIN);
            Cache::write('admin_list_group_' . $group['Group']['id'], $admins, 'group');
        }

        $this->set('admins', $admins);
        $this->set('photos', $photos);
    }

    public function stop_notification($id,$isRedirect = true)
    {
        $this->_checkPermission(array('confirm' => true));
        $uid = MooCore::getInstance()->getViewer(true);
        $this->loadModel("Group.Group");
        $group = $this->Group->findById($id);
        $this->_checkExistence($group);

        $this->loadModel('Group.GroupNotificationSetting');
        $result = $this->GroupNotificationSetting->changeStatus($id,$uid);
        if($isRedirect) {
            if (!$result)
                $this->Session->setFlash(__( 'Turn off notification successfully'));
            else
                $this->Session->setFlash(__( 'Turn on notification successfully'));
            $this->redirect('/groups/view/' . $id);
        }
    }

    public function do_request($id = null,$isRedirect = true) {
        $id = intval($id);
        //This user been invited
        if($this->Session->read('group_invited_user'))
        {
            //check if this user is already login
            $user_id = MooCore::getInstance()->getViewer(true);
            if(!$user_id)
            {
                if($isRedirect) {
                    $this->Session->write('accept_group_invited_user',1);
                    $this->redirect('/users/register');
                }
                else {
                    return $error = array(
                        'code' => 401,
                        'message' => __('Please log in to continue'),
                    );
                }
            }
        }
        $this->_checkPermission(array('confirm' => true));
        $uid = $this->Auth->user('id');
        $this->loadModel('Group.GroupUser');

        $data['user_id'] = $uid;
        $data['group_id'] = $id;

        // check if user has a group_user record
        $my_status = $this->GroupUser->getMyStatus($uid, $id);
        $group = $this->Group->findById($id);
        //$this->_checkPermission( array('user_block' => $group['Group']['user_id']) );
        if (!empty($my_status)) { // user has a record in group_user table
            if ($my_status['GroupUser']['status'] == GROUP_USER_INVITED) { // user was invited
                $data['status'] = GROUP_USER_MEMBER;
                $this->GroupUser->id = $my_status['GroupUser']['id'];
            } else {
                if($isRedirect) {
                    $this->redirect('/pages/error');
                }
                else {
                    return $error = array(
                        'code' => 400,
                        'message' => __('Already sent request.'),
                    );
                }
            }
        }
        else {
            switch ($group['Group']['type']) {
                case PRIVACY_RESTRICTED:
                    $data['status'] = GROUP_USER_REQUESTED;
                    break;

                case PRIVACY_PUBLIC:
                    $data['status'] = GROUP_USER_MEMBER;
                    break;

                case PRIVACY_PRIVATE:
                    if($isRedirect) {
                        $this->Session->setFlash(__( 'This is a private group. You must be invited by a group admin in order to join'), 'default', array('class' => 'error-message'));
                        $this->redirect('/pages/error'); // make sure that user is not trying to join a private group if he was not invited
                    }
                    else {
                        $this->throwErrorCodeException('private_group');
                        return $error = array(
                            'code' => 400,
                            'message' => __('This is a private group. You must be invited by a group admin in order to join'),
                        );
                    }
                    break;
            }
        }

        $this->GroupUser->save($data);

        if (isset($data['status']) && $data['status'] == GROUP_USER_REQUESTED) { // requested
            if($isRedirect) {
                $this->Session->setFlash(__( 'Your join request has been sent'));
            }

            $this->loadModel('Notification');
            $this->Notification->record(array('recipients' => $group['Group']['user_id'],
                'sender_id' => $uid,
                'action' => 'group_request',
                'url' => '/groups/view/' . $id,
                'params' => $group['Group']['name']
            ));
        } else // joined		
            $this->_updateActivity($group['Group'], $uid);

        // clear cache
        Cache::clearGroup('group', 'group');
        if($isRedirect) {
            $this->redirect('/groups/view/' . $id);
        }
    }

    public function request_to_join(){
        $this->autoRender = false;
        $data = $this->request->data;

        $id = intval($data['group_id']);
        //This user been invited
        if ($this->Session->read('group_invited_user')) {
            //check if this user is already login
            $user_id = MooCore::getInstance()->getViewer(true);
            if (!$user_id) {
                $this->Session->write('accept_group_invited_user', 1);
            }
        }
        $this->_checkPermission(array('confirm' => true));
        $uid = $this->Auth->user('id');
        $this->loadModel('Group.GroupUser');

        $data['user_id'] = $uid;
        $data['group_id'] = $id;

        // check if user has a group_user record
        $my_status = $this->GroupUser->getMyStatus($uid, $id);
        $group = $this->Group->findById($id);

        if (!empty($my_status)) { // user has a record in group_user table
            if ($my_status['GroupUser']['status'] == GROUP_USER_INVITED) { // user was invited
                $data['status'] = GROUP_USER_MEMBER;
                $this->GroupUser->id = $my_status['GroupUser']['id'];
            }elseif ($my_status['GroupUser']['status'] == GROUP_USER_REQUESTED) { //user already send request
                return;
            }
        } else {
            switch ($group['Group']['type']) {
                case PRIVACY_RESTRICTED:
                    $data['status'] = GROUP_USER_REQUESTED;
                    break;
                case PRIVACY_PUBLIC:
                    $data['status'] = GROUP_USER_MEMBER;
                    break;
                case PRIVACY_PRIVATE:
                    break;
            }
        }

        $this->GroupUser->save($data);
        if (isset($data['status']) && $data['status'] == GROUP_USER_REQUESTED) { // requested
            $this->loadModel('Notification');
            $this->Notification->record(array('recipients' => $group['Group']['user_id'],
                'sender_id' => $uid,
                'action' => 'group_request',
                'url' => '/groups/view/' . $id,
                'params' => $group['Group']['name']
            ));
        }
    }

    private function _updateActivity($group, $uid) {

        $this->loadModel('Activity');
        $activity = $this->Activity->getRecentActivity('group_join', $uid);

        if (!empty($activity)) {
            // aggregate activities
            $group_ids = explode(',', $activity['Activity']['items']);

            if (!in_array($group['id'], $group_ids))
                $group_ids[] = $group['id'];

            $this->Activity->id = $activity['Activity']['id'];
            $this->Activity->save(array('items' => implode(',', $group_ids)
            ));
        }
        else {
            $data = array('type' => 'Group_Group',
                'action' => 'group_join',
                'user_id' => $uid,
                'item_type' => 'Group_Group',
                'items' => $group['id'],
                'target_id' => $group['id'],
                'plugin' => 'Group',
                'privacy' => $group['type']
            );

            $this->Activity->save($data);
        }

        //activitylog event
        $cakeEvent = new CakeEvent('Controller.Group.afterJoinGroup', $this, array('uid' => $uid,'group_id' => $group['id']));
        $this->getEventManager()->dispatch($cakeEvent);
    }

    public function members($id = null,$isRedirect = true) {
        $id = intval($id);
        $this->loadModel('Group.GroupUser');
        $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        $more_result = 0;

        $members = $this->GroupUser->getUsers($id, array(GROUP_USER_MEMBER, GROUP_USER_ADMIN), $page);
        $more_members = $this->GroupUser->getUsers($id, array(GROUP_USER_MEMBER, GROUP_USER_ADMIN), $page +1);
        if(!empty($more_members))
            $more_result = 1;

        $group = $this->Group->findById($id);
        $admins = $this->GroupUser->getUsersList($id, GROUP_USER_ADMIN);

        $uid = $this->Auth->user('id');
        $is_member = $this->GroupUser->isMember($uid, $id);
        if ($group['Group']['type'] == PRIVACY_PRIVATE) {
            $cuser = $this->_getUser();

            if (!$cuser['Role']['is_admin'] && !$is_member)
            {
                if($isRedirect) {
                    $this->autoRender = false;
                    echo 'Only group members can view members';
                    return;
                }
                else {
                    $this->throwErrorCodeException('private_group');
                    return $error = array(
                        'code' => 400,
                        'message' => __('This is private group. Only group members can view'),
                    );
                }
            }
        }
        $this->set('title_for_layout', __("Members"));
        $this->set('users', $members);
        $this->set('group', $group);
        $this->set('admins', $admins);
        $this->set('more_url', '/groups/members/' . $id . '/page:' . ( $page + 1 ));
        $this->set('more_result', $more_result);
        if($isRedirect) {
            if ($page == 1)
                $this->render('/Elements/ajax/members');
            else
                $this->render('/Elements/lists/users_list');
        }

    }

    /*
     * Show invite form
     * @param int $group_id
     */

    public function ajax_invite($group_id = null) {
        $group_id = intval($group_id);
        $this->_checkPermission(array('confirm' => true));

        $this->set('group_id', $group_id);
    }

    /*
     * Handle invite submission
     */

    public function ajax_sendInvite($isRedirect = true) {
        $this->autoRender = false;
        $this->_checkPermission(array('confirm' => true));
        $cuser = $this->_getUser();
        $this->loadModel('Group.GroupUser');
        if (!empty($this->request->data['friends']) || !empty($this->request->data['emails'])) {
            $group = $this->Group->findById($this->request->data['group_id']);
            $admins_list = $this->GroupUser->getUsersList($this->request->data['group_id'], GROUP_USER_ADMIN);

            // check if user can invite
            if ($group['Group']['type'] == PRIVACY_PRIVATE && (!in_array($cuser['id'], $admins_list) )){
                if($isRedirect) {
                    $response = array();
                    $response['result'] = 0;
                    $response['msg'] = __( 'Only owner of this group can invite.');
                    echo json_encode($response);
                    return;
                }
                else {
                    return $error = array(
                        'code' => 400,
                        'message' => __('Only owner of this group can invite.'),
                    );
                }
            }
        }

        if ($this->request->data['invite_type_group'] == 1)
        {
            if (!empty($this->request->data['friends']))
            {
                $data = array();
                $friends = explode(',', $this->request->data['friends']);
                $group_users = $this->GroupUser->getUsersList($this->request->data['group_id']);

                foreach ($friends as $friend_id)
                    if (!in_array($friend_id, $group_users))
                        $data[] = array('group_id' => $this->request->data['group_id'], 'user_id' => $friend_id, 'status' => GROUP_USER_INVITED);

                if (!empty($data)) {
                    $this->GroupUser->saveAll($data);

                    $this->loadModel('Notification');
                    $this->Notification->record(array('recipients' => $friends,
                        'sender_id' => $cuser['id'],
                        'action' => 'group_invite',
                        'url' => '/groups/view/' . $this->request->data['group_id'],
                        'params' => $group['Group']['name']
                    ));
                }
            }
            else
            {
                if(!$isRedirect) {
                    return $error = array(
                        'code' => 400,
                        'message' => __( 'Recipient is required'),
                    );
                }else{
                    return	$this->_jsonError(__( 'Recipient is required'));
                }
            }
        }
        else
        {
            if (!empty($this->request->data['emails'])) {
                // check captcha
                $checkRecaptcha = MooCore::getInstance()->isRecaptchaEnabled();
                $recaptcha_privatekey = Configure::read('core.recaptcha_privatekey');
                $is_mobile = $this->viewVars['isMobile'];
                if ( $checkRecaptcha && !$is_mobile)
                {
                    App::import('Vendor', 'recaptchalib');
                    $reCaptcha = new ReCaptcha($recaptcha_privatekey);
                    $resp = $reCaptcha->verifyResponse(
                        $_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"]
                    );

                    if ($resp != null && !$resp->success) {
                        return	$this->_jsonError(__( 'Invalid security code'));
                    }
                }
                $emails = explode(',', $this->request->data['emails']);

                $i = 1;

                $userModel = MooCore::getInstance()->getModel('user');
                $friends = $userModel->findAllByEmail($emails);
                $friends = Hash::extract($friends,'{n}.User.id');
                $group_users = $this->GroupUser->getUsersList($this->request->data['group_id']);

                foreach ($friends as $friend_id)
                    if (!in_array($friend_id, $group_users))
                        $data[] = array('group_id' => $this->request->data['group_id'], 'user_id' => $friend_id, 'status' => GROUP_USER_INVITED);

                if (!empty($data))
                    $this->GroupUser->saveAll($data);

                foreach ($emails as $email) {
                    if ($i <= 10) {
                        if (Validation::email(trim($email))) {

                            //find this user base on email
                            $user = $userModel->findByEmail($email);
                            //this user does not exist
                            $invite_checksum = '';
                            if(empty($user))
                            {
                                $invite_checksum = uniqid();
                                $groupUserInvitedModel = MooCore::getInstance()->getModel('Group.GroupUserInvite');
                                $groupUserInvitedModel->create();
                                $groupUserInvitedModel->set(array('group_id' => $group['Group']['id'],'email' => $email,'invite_checksum' => $invite_checksum));
                                $groupUserInvitedModel->save();
                            }
                            $ssl_mode = Configure::read('core.ssl_mode');
                            $http = (!empty($ssl_mode)) ? 'https' :  'http';
                            $this->MooMail->send(trim($email),'group_invite_none_member',
                                array(
                                    'group_title' => $group['Group']['moo_title'],
                                    'group_link' => $http.'://'.$_SERVER['SERVER_NAME'].$group['Group']['moo_href'].'/'.$invite_checksum,
                                    'email' => trim($email),
                                    'sender_title' => $cuser['name'],
                                    'sender_link' => $http.'://'.$_SERVER['SERVER_NAME'].$cuser['moo_href'],
                                )
                            );
                        }
                    }
                    $i++;
                }
            }
            else
            {
                if(!$isRedirect) {
                    return $error = array(
                        'code' => 400,
                        'message' => __( 'Recipient is required'),
                    );
                }else{
                    return	$this->_jsonError(__( 'Recipient is required'));
                }
            }
        }
        if($isRedirect) {
            $response = array();
            $response['result'] = 1;
            if($this->theme != "mooApp"){
                $response['msg'] = __( 'Your invitations have been sent.') . ' <a href="javascript:void(0)" onclick="$(\'#themeModal .modal-content\').load(\''.$this->request->base.'/groups/ajax_invite/'.$this->request->data['group_id'].'\');">' . __( 'Invite more friends') . '</a>';
            }
            else {
                $response['msg'] = __('Your invitations have been sent.');
            }
            echo json_encode($response);
        }
    }

    /*
     * Remove member from group
     * @param int $id - id of group_user record
     */

    public function ajax_remove_member() {
        $this->autoRender = false;
        $this->loadModel('Group.GroupUser');

        if (empty($this->request->data['id']))
            return;

        $group_user = $this->GroupUser->findById($this->request->data['id']);
        $admins_list = $this->GroupUser->getUsersList($group_user['GroupUser']['group_id'], GROUP_USER_ADMIN);

        $this->_checkPermission(array('admins' => $admins_list));
        $this->GroupUser->delete($this->request->data['id']);
        Cache::delete('admin_count_group_' . $group_user['GroupUser']['group_id'], 'group');
    }

    /*
     * Promote/demote group admin
     * @param int $id - id of group_user record
     * @param string $type - make => make admin
     */

    public function ajax_change_admin() {
        $this->autoRender = false;
        $this->loadModel('Group.GroupUser');

        if (empty($this->request->data['id']) || empty($this->request->data['type']))
            return;

        $this->GroupUser->id = $this->request->data['id'];
        $group_user = $this->GroupUser->findById($this->request->data['id']);
        $admins_list = $this->GroupUser->getUsersList($group_user['GroupUser']['group_id'], GROUP_USER_ADMIN);

        $this->_checkPermission(array('admins' => $admins_list));
        $group_user['GroupUser']['status'] = ( $this->request->data['type'] == 'make' ) ? GROUP_USER_ADMIN : GROUP_USER_MEMBER;

        $this->GroupUser->save($group_user['GroupUser']);

        Cache::delete('admin_count_group_' . $group_user['GroupUser']['group_id'], 'group');
    }

    public function ajax_requests($group_id = null) {
        $this->loadModel('Group.GroupUser');

        $admins_list = $this->GroupUser->getUsersList($group_id, GROUP_USER_ADMIN);
        $this->_checkPermission(array('admins' => $admins_list));

        $page = !empty($this->request->named['page']) ? $this->request->named['page'] : 1;

        $requests = $this->GroupUser->getUsers($group_id, GROUP_USER_REQUESTED, $page);
        $more_requests = $this->GroupUser->getUsers($group_id, GROUP_USER_REQUESTED, $page+1);

        $this->set('requests', $requests);
        $this->set('more_requests', $more_requests);
        $this->set('more_url', '/groups/ajax_requests/' . $group_id . '/page:' . ( $page + 1 ));

        if ($page > 1){
            $this->render('/Elements/lists/requests_list');
        }
    }

    public function ajax_respond($isRedirect = true) {
        $this->autoRender = false;
        $this->loadModel('Group.GroupUser');

        $this->GroupUser->id = $this->request->data['id'];
        $group_user = $this->GroupUser->read();
        $admins_list = $this->GroupUser->getUsersList($group_user['GroupUser']['group_id'], GROUP_USER_ADMIN);

        $this->_checkPermission(array('admins' => $admins_list));

        if (!empty($this->request->data['status'])) { // accept
            $this->GroupUser->save(array('status' => GROUP_USER_MEMBER));

            $this->_updateActivity($group_user['Group'], $group_user['GroupUser']['user_id']);
            if($isRedirect) {
                echo ' <a href="' . $this->request->base . '/users/view/' . $group_user['GroupUser']['user_id'] . '">' . $group_user['User']['name'] . '</a> ' . __( 'is now a member of this group');
            }
            Cache::clearGroup('group');
        } else {
            $this->GroupUser->delete($this->request->data['id']);
            if($isRedirect) {
                echo __( 'You have deleted the request. The sender will not be notified');
            }
            Cache::clearGroup('group');
        }

    }

    /*
     * Leave group
     * @param int $id - id of group
     */

    public function do_leave($id,$isRedirect = true) {
        $id = intval($id);
        $this->_checkPermission();
        $uid = $this->Auth->user('id');

        $this->loadModel('Group.GroupUser');
        $my_status = $this->GroupUser->getMyStatus($uid, $id);

        if (!empty($my_status) && ( $uid != $my_status['Group']['user_id'] )) {
            $this->GroupUser->delete($my_status['GroupUser']['id']);

            // remove associated activity
            if ($my_status['Group']['type'] != PRIVACY_PRIVATE) {
                $this->loadModel('Activity');
                $activity = $this->Activity->getRecentActivity('group_join', $uid);

                if ($activity) {
                    $items = array_filter(explode(',',$activity['Activity']['items']));
                    $items = array_diff($items,array($id));

                    if (!count($items))
                    {
                        $this->Activity->delete($activity['Activity']['id']);
                    }
                    else
                    {
                        $this->Activity->id = $activity['Activity']['id'];
                        $this->Activity->save(
                            array('items' => implode(',',$items))
                        );
                    }
                }
            }

            //activitylog event
            $cakeEvent = new CakeEvent('Controller.Group.afterLeaveGroup', $this, array('uid' => $uid, 'group_id' => $id));
            $this->getEventManager()->dispatch($cakeEvent);

            $this->Session->setFlash(__( 'You have successfully left this group'));
        }

        // clear cache
        Cache::clearGroup('group', 'group');
        if($isRedirect) {
            $this->redirect('/groups/view/' . $id);
        }
        else {
            if (empty($my_status)) {
                return $error = array(
                    'code' => 404,
                    'message' => __('You not in this group.'),
                );
            }
            if ($uid == $my_status['Group']['user_id']) {
                return $error = array(
                    'code' => 400,
                    'message' => __('You are group admin so can not leave this group.'),
                );
            }
        }
    }

    public function do_feature($id = null,$isRedirect = true) {
        $id = intval($id);
        $group = $this->Group->findById($id);
        $this->_checkExistence($group);

        $this->_checkPermission(array('is_admin' => true));

        // MOOSOCIAL-2528 - @sangpq
        if ($group['Group']['type'] == PRIVACY_PRIVATE){
            if($isRedirect) {
                $this->autoRender = false;
                echo __('You are not allowed feature private group.');
                exit;
            }
            else {
                $this->throwErrorCodeException('private_group');
                return $error = array(
                    'code' => 400,
                    'message' => __('You are not allowed feature private group.'),
                );
            }
        }

        $this->Group->id = $id;
        $this->Group->save(array('featured' => 1));
        if($isRedirect) {
            $this->Session->setFlash(__( 'Group has been featured'));
            $this->redirect($this->referer());
        }
    }

    public function do_unfeature($id = null,$isRedirect = true) {
        $group = $this->Group->findById($id);
        $this->_checkExistence($group);

        $this->_checkPermission(array('is_admin' => true));

        $this->Group->id = $id;
        $this->Group->save(array('featured' => 0));

        if($isRedirect) {
            $this->Session->setFlash(__( 'Group has been unfeatured'));
            $this->redirect($this->referer());
        }
    }

    /*
     * Delete group
     * @param int $id - group id to delete
     */

    public function do_delete($id = null,$isRedirect = true) {
        $id = intval($id);
        $group = $this->Group->findById($id);
        $this->_checkExistence($group);

        $this->_checkPermission(array('admins' => array($group['Group']['user_id'])));

        $cakeEvent = new CakeEvent('Plugin.Controller.Group.beforeDelete', $this, array('aGroup' => $group));
        $this->getEventManager()->dispatch($cakeEvent);

        // delete activity
        $activityModel = MooCore::getInstance()->getModel('Activity');
        $parentActivity = $activityModel->find('list', array('fields' => array('Activity.id') , 'conditions' =>
            array('Activity.item_type' => 'Group_Group', 'Activity.item_id' => $group['Group']['id'])));

        $activityModel->deleteAll(array('Activity.item_type' => 'Group_Group', 'Activity.item_id' => $group['Group']['id']), true, true);

        // delete child activity
        $activityModel->deleteAll(array('Activity.item_type' => 'Group_Group', 'Activity.parent_id' => $parentActivity));

        $this->Group->delete($id);
        $cakeEvent = new CakeEvent('Plugin.Controller.Group.afterDeleteGroup', $this, array('item' => $group));
        $this->getEventManager()->dispatch($cakeEvent);

        if($isRedirect) {
            $this->Session->setFlash(__( 'Group has been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect('/groups');
        }
    }

    public function admin_index() {
        if (!empty($this->request->data['keyword']))
            $this->redirect('/admin/groups/index/keyword:' . $this->request->data['keyword']);

        $cond = array();
        if (!empty($this->request->named['keyword']))
            $cond['MATCH(Group.name) AGAINST(? IN BOOLEAN MODE)'] = $this->request->named['keyword'];

        $groups = $this->paginate('Group', $cond);

        $this->loadModel('Category');
        $categories = $this->Category->getCategoriesListItem('Group');

        $this->set('groups', $groups);
        $this->set('categories', $categories);
        $this->set('title_for_layout', __('Groups Manager'));
    }

    public function admin_delete() {
        $this->_checkPermission(array('super_admin' => 1));

        if (!empty($_POST['groups'])) {
            $groups = $this->Group->findAllById($_POST['groups']);

            foreach ($groups as $group){
                $cakeEvent = new CakeEvent('Plugin.Controller.Group.beforeDelete', $this, array('aGroup' => $group));
                $this->getEventManager()->dispatch($cakeEvent);

                $this->Group->delete($group['Group']['id']);

                $cakeEvent = new CakeEvent('Plugin.Controller.Group.afterDeleteGroup', $this, array('item' => $group));
                $this->getEventManager()->dispatch($cakeEvent);
            }

            $this->Session->setFlash(__('Groups have been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }

        $this->redirect($this->referer());
    }

    public function popular() {
        if ($this->request->is('requested')) {
            $num_item_show = $this->request->named['num_item_show'];

            $this->loadModel('Group.Group');
            return $this->Group->getPopularGroups($num_item_show, Configure::read('core.popular_interval'));
        }
    }

    public function joined_group() {
        if ($this->request->is('requested')) {
            $uid = $this->Auth->user('id');
            $num_joined_groups = $this->request->named['num_joined_groups'];
            $this->loadModel('Group.GroupUser');
            return $this->GroupUser->getJoinedGroups($uid, $num_joined_groups);
            ;
        }
    }

    /*
     * @return: list of admin of group_id
     */

    public function admin_list() {

    }

    /*
     * @return: list of member of group_id
     */

    public function member_list() {

    }

    public function ajax_group_joined(){
        $activity_id = $this->request->named['activity_id'];
        $this->loadModel('Activity');
        $activity = $this->Activity->findById($activity_id);
        if (!empty($activity)){
            $items = $activity['Activity']['items'];
            $ids = explode(',', $items);
            $this->loadModel('Group.Group');
            $groups = $this->Group->find('all', array('conditions' => array(
                'Group.id' => $ids
            )));
            $this->set(compact('groups'));
        }
        $this->render('/Elements/ajax/ajax_group_joined');
    }

    public function categories_list($isRedirect = true) {
        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();
        $categories = $this->Category->getCategories('Group', $role_id);
        if ($this->request->is('requested')) {
            return $categories;
        }
        if($isRedirect && $this->theme == "mooApp") {
            $this->render('/Elements/lists/categories_list');
        }
    }

    // for suggestion
    public function my_joined_group() {
        $uid = $this->Auth->user('id');
        $this->loadModel('Group.GroupUser');
        $groups = $this->GroupUser->getJoinedGroups($uid, 500);
        $this->set(compact('groups'));
    }

    public function profile_user_group($uid = null,$isRedirect=true){
        $uid = intval($uid);

        if($isRedirect) {
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        }
        else {
            $page = $this->request->query('page') ? $this->request->query('page') : 1;
        }

        $groups = $this->Group->getGroups('user', $uid, $page, RESULTS_LIMIT);

        $more_groups = $this->Group->getGroups('user', $uid, $page+1, RESULTS_LIMIT);

        $more_result = 0;
        if(!empty($more_groups))
            $more_result = 1;

        $this->set('groups', $groups);
        $this->set('more_url', '/groups/profile_user_group/' . $uid . '/page:' . ( $page + 1 ));
        $this->set('user_id', $uid);
        $this->set('user_group', true);
        $this->set('more_result',$more_result);

        if($isRedirect && $this->theme != "mooApp") {
            if ($page > 1)
                $this->render('/Elements/lists/groups_list');
            else
                $this->render('Group.Groups/profile_user_group');
        }
    }


}

?>
