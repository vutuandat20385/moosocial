<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('GroupAppModel', 'Group.Model');
class GroupNotificationSetting extends GroupAppModel {
    public function getStatus($group_id,$user_id)
    {
        $item = $this->getItem($group_id,$user_id);
        if ($item)
        {
            return $item['GroupNotificationSetting']['status'];
        }

        return Configure::read("Group.group_enable_send_notification");
    }

    public function getItem($group_id,$user_id)
    {
        return $this->find("first",array("conditions"=>array(
            "group_id"=>$group_id,
            "user_id"=>$user_id
        )));
    }

    public function changeStatus($group_id,$user_id)
    {
        $item = $this->getItem($group_id,$user_id);
        if ($item)
        {
            $this->id = $item['GroupNotificationSetting']['id'];
            $this->save(array('status'=>!$item['GroupNotificationSetting']['status']));

            return !$item['GroupNotificationSetting']['status'];
        }
        else
        {
            $this->clear();
            $this->save(array(
                'group_id' => $group_id,
                'user_id' => $user_id,
                'status' => !Configure::read("Group.group_enable_send_notification")
            ));

            return !Configure::read("Group.group_enable_send_notification");
        }
    }
}
