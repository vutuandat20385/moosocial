<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class MenuListener implements CakeEventListener {

    public function implementedEvents() {
        return array(
            'Controller.Role.afterSave' => 'processRoleAfterSave',
            'Controller.Role.afterDelete' => 'processRoleAfterDelete'
        );
    }

    public function processRoleAfterSave($event) {
        // update role access of core menu item whenever new role created
        $roleModel = MooCore::getInstance()->getModel('Role');
        $coreMenuItemModel = MooCore::getInstance()->getModel('Menu.CoreMenuItem');

        $coreMenuItems = $coreMenuItemModel->find('all');
        $roles = $roleModel->find('list', array('fields' => 'id'));

        foreach ($coreMenuItems as $menuItems) {
            $roleAccess = $menuItems['CoreMenuItem']['role_access'];
            $roleAccessAfter = array_merge(json_decode($roleAccess,true), array($event->data['role_id']));

            // only update core menu item which has full access
            if (array_values($roles) == $roleAccessAfter) {
                $coreMenuItemModel->updateAll(array(
                    'CoreMenuItem.role_access' => "'" . json_encode(array_values($roles)) . "'"
                        ), array(
                    'CoreMenuItem.id' => $menuItems['CoreMenuItem']['id']
                ));
            }
        }
    }

    public function processRoleAfterDelete($event) {
        // update role access of core menu item whenever new role created
        $roleModel = MooCore::getInstance()->getModel('Role');
        $coreMenuItemModel = MooCore::getInstance()->getModel('Menu.CoreMenuItem');

        $coreMenuItems = $coreMenuItemModel->find('all');
        $roles = $roleModel->find('list', array('fields' => 'id'));

        $roleIds = $event->data['roleIds'];

        foreach ($coreMenuItems as $menuItems) {
            $roleAccess = $menuItems['CoreMenuItem']['role_access'];
            $roleAccessArr = json_decode($roleAccess, true);

            // only update core menu item which has role access contain this role_id
            foreach ($roleIds as $role_id) {
                if (($key = array_search($role_id, $roleAccessArr)) !== false) {
                    unset($roleAccessArr[$key]);
                }
                $coreMenuItemModel->updateAll(array(
                    'CoreMenuItem.role_access' => "'" . json_encode(array_values($roleAccessArr)) . "'"
                        ), array(
                    'CoreMenuItem.id' => $menuItems['CoreMenuItem']['id']
                ));
            }
        }
    }

}
