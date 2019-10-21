<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('MooPlugin', 'Lib');

class SocialIntegrationPlugin implements MooPlugin {

    public function install() {
        
    }

    public function uninstall() {
        
    }

    public function settingGuide() {
        
    }

    public function menu() {
        return array(
            __('Facebook') => array('plugin' => 'social_integration', 'controller' => 'providers', 'action' => 'facebook'),
            __('Google') => array('plugin' => 'social_integration', 'controller' => 'providers', 'action' => 'google'),
        );
    }

}
