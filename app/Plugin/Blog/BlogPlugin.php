<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('MooPlugin','Lib');
class BlogPlugin implements MooPlugin{
    public function install(){}
    public function uninstall(){}
    public function settingGuide(){}
    public function menu()
    {
        return array(
            __('General') => array('plugin' => 'blog', 'controller' => 'blog_plugins', 'action' => 'admin_index'),
            __('Settings') => array('plugin' => 'blog', 'controller' => 'blog_settings', 'action' => 'admin_index'),
            __('Categories') => array('plugin' => 'blog', 'controller' => 'blog_categories', 'action' => 'admin_index')
        );
    }
}