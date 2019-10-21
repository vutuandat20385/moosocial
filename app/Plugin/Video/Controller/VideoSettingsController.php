<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class VideoSettingsController extends VideoAppController {

    public $components = array('QuickSettings');

    public function beforeFilter() {
        parent::beforeFilter();
        $this->loadModel('Setting');
        $this->loadModel('SettingGroup');
    }

    public function admin_index() {
        $video_enabled = Configure::read('Video.video_enabled');

        if ($video_enabled == 0) {
            $this->loadModel('Menu.CoreMenuItem');
            $videos_menu = $this->CoreMenuItem->find('first', array(
                'conditions' => array('url' => '/videos', 'type' => 'page')
            ));

            if ($videos_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $videos_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 0));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Videos',
                    'url' => '/videos',
                    'is_active' => 0,
                    'menu_id' => 1,
                    'type' => 'page',
                    'menu_order' => 999
                ));
                $this->CoreMenuItem->save();
            }
        } elseif ($video_enabled == 1) {
            $this->loadModel('Menu.CoreMenuItem');
            $videos_menu = $this->CoreMenuItem->find('first', array(
                'conditions' => array('url' => '/videos', 'type' => 'page')
            ));

            if ($videos_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $videos_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 1));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Videos',
                    'url' => '/videos',
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
        $this->QuickSettings->run($this, array("Video"));
        $this->set('title_for_layout', __('Videos Setting'));
    }

    private function getSettingGuide($key) {
        $settingGuide = '';
        $setupPath = sprintf(PLUGIN_FILE_PATH, $key, $key);
        if (file_exists($setupPath)) {
            require_once($setupPath);
            $classname = $key . 'Plugin';
            if (class_exists($classname)) {
                $cl = new $classname();
                if (method_exists($classname, 'settingGuide')) {
                    $settingGuide = $cl->settingGuide();
                }
            }
        }
        return $settingGuide;
    }

}
