<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('MooPlugin','Lib');
class MailPlugin implements MooPlugin{
    public function install(){}
    public function uninstall(){}
    public function settingGuide(){}
	public function menu()
    {
        return array(            
            __('Manage Mail Template') => array('plugin' => 'mail', 'controller' => 'mail_plugins', 'action' => 'admin_index'),
        	__('Mail Settings') => array('plugin' => 'mail', 'controller' => 'mail_settings', 'action' => 'admin_index'),
        );
    }
}