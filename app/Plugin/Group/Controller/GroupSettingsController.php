<?php 

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class GroupSettingsController extends GroupAppController{
   public $components = array('QuickSettings');

    public function beforeFilter() {
        parent::beforeFilter();
        $this->loadModel('Setting');
        $this->loadModel('SettingGroup');
        $this->loadModel('Plugin');
        $this->loadModel('Menu.CoreMenuItem');
    }

    public function admin_index($id = null) {

        $group_enabled = Configure::read('Group.group_enabled');
        if (!$group_enabled) {
            $groups_menu = $this->CoreMenuItem->find('first', array(
                'conditions' => array('url' => '/groups', 'type' => 'page')
            ));
            if ($groups_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $groups_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 0));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Groups',
                    'url' => '/groups',
                    'is_active' => 0,
                    'menu_id' => 1,
                    'type' => 'page',
                    'menu_order' => 999
                ));
                $this->CoreMenuItem->save();
            }
            
        } else {
            $groups_menu = $this->CoreMenuItem->find('first', array(
                'conditions' => array('url' => '/groups', 'type' => 'page')
            ));
            if ($groups_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $groups_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 1));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Groups',
                    'url' => '/groups',
                    'is_active' => 1,
                    'menu_id' => 1,
                    'type' => 'page',
                    'menu_order' => 999
                ));
                $this->CoreMenuItem->save();
            }
        }
        
        // clear cache menu
        Cache::clearGroup('menu', 'menu');

        $this->QuickSettings->run($this, array("Group"), $id);
        
        $this->set('title_for_layout', __('Groups Setting'));
    }
}