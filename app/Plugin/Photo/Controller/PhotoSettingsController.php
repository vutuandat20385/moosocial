<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class PhotoSettingsController extends PhotoAppController{
    public $components = array('QuickSettings');

    public function beforeFilter() {
        parent::beforeFilter();
        $this->loadModel('Setting');
        $this->loadModel('SettingGroup');
        $this->loadModel('Plugin');
        $this->loadModel('Menu.CoreMenuItem');
    }

    public function admin_index($id = null) {

        $photo_enabled = Configure::read('Photo.photo_enabled');
        if (!$photo_enabled) {
            $photos_menu = $this->CoreMenuItem->find('first', array(
                'conditions' => array('url' => '/photos', 'type' => 'page')
            ));
            if ($photos_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $photos_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 0));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Photos',
                    'url' => '/photos',
                    'is_active' => 0,
                    'menu_id' => 1,
                    'type' => 'page',
                    'menu_order' => 999
                ));
                $this->CoreMenuItem->save();
            }
        } else {
            $photos_menu = $this->CoreMenuItem->find('first', array(
                'conditions' => array('url' => '/photos', 'type' => 'page')
            ));
            if ($photos_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $photos_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 1));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Photos',
                    'url' => '/photos',
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
            
        Cache::clearGroup('photo', 'photo');
        $this->set('title_for_layout', __('Albums Setting'));
        
        $this->QuickSettings->run($this, array("Photo"), $id);
    }
}