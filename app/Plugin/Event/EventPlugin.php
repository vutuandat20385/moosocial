<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('MooPlugin','Lib');
class EventPlugin implements MooPlugin{
    public function install(){}
    public function uninstall(){}
    public function settingGuide(){}
    public function menu()
    {
        return array(
            __('General') => array('plugin' => 'event', 'controller' => 'event_plugins', 'action' => 'admin_index'),
            __('Settings') => array('plugin' => 'event', 'controller' => 'event_settings', 'action' => 'admin_index'),
            __('Categories') => array('plugin' => 'event', 'controller' => 'event_categories', 'action' => 'admin_index'),
        );
    }
}