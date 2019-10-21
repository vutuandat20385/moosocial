<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget', 'Controller/Widgets');

class memberListGroupWidget extends Widget {

    public function beforeRender(Controller $controller) {
        $data = array(
            'groupMembers' => array(),
            'groupMembersCnt' => 0
        );
        $subject = MooCore::getInstance()->getSubject();
        if ($subject) {
            $id = $subject['Group']['id'];
            $num_group_member = $this->params['num_item_show'];
            $controller->loadModel('Group.GroupUser');
            $user_blocks = array();
            $cuser = $controller->_getUser();
            if($cuser){
                $user_blocks = $controller->getBlockedUsers($cuser['id']);
            }

            if(empty($user_blocks)){
                // caching
                $group_members = Cache::read('group_' . $id . '_members_widget', 'group');
                if (!$group_members) {
                    $group_members = $controller->GroupUser->getUsers($id, GROUP_USER_MEMBER, null, $num_group_member);
                    Cache::write('group_' . $id . '_members_widget', $group_members, 'group');
                }
            }else{
                $group_members = $controller->GroupUser->getUsers($id, GROUP_USER_MEMBER, null, $num_group_member);
            }

            $member_count = $subject['Group']['group_user_count'];

            $data['groupMembers'] = $group_members;
            $data['groupMembersCnt'] = $member_count;
        }

        $this->setData('data', $data);
    }

}
