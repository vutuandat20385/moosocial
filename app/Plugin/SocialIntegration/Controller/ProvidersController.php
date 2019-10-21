<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class ProvidersController extends SocialIntegrationAppController {

    public $uses = array('SocialIntegration.SocialProvider');
    public $components = array('QuickSettings');

    public function admin_facebook($id = null) {
        $this->set('title_for_layout', __('Social Integration'));

        $settingModel = MooCore::getInstance()->getModel('Setting');
        $facebook_settings = $settingModel->find('first', array(
            'conditions' => array('Setting.name' => 'facebook_sdk_version')
        ));

        //auto select facebook sdk
        $phpVersion = floatval(phpversion());
        if($phpVersion >= 5.4)
        {
            $settingModel->id = $facebook_settings['Setting']['id'];
            $settingModel->save(array('value_actual' => '[{"name":"5.0.0","value":"5.0.0","select":1},{"name":"3.2.3","value":"3.2.3","select":0}]'));
        }
        else
        {
            $settingModel->id = $facebook_settings['Setting']['id'];
            $settingModel->save(array('value_actual' => '[{"name":"5.0.0","value":"5.0.0","select":0},{"name":"3.2.3","value":"3.2.3","select":1}]'));
        }

        $this->QuickSettings->run($this, array("FacebookIntegration"), $id);
        $this->set('url', '/admin/social/facebook/');
    }

    public function admin_google($id = null) {
        $this->set('title_for_layout', __('Social Integration'));

        $this->QuickSettings->run($this, array("GoogleIntegration"), $id);
        $this->set('url', '/admin/social/google/');
    }

    public function admin_linkedin($id = null) {

        $this->set('title_for_layout', __('Social Integration'));

        $this->QuickSettings->run($this, array("LinkedinIntegration"), $id);
        $this->set('url', '/admin/social/linkedin/');
    }

    public function admin_twitter($id = null) {
        $this->set('title_for_layout', __('Social Integration'));

        $this->QuickSettings->run($this, array("TwitterIntegration"), $id);
        $this->set('url', '/admin/social/twitter/');
    }

    public function admin_yahoo($id = null) {
        $this->set('title_for_layout', __('Social Integration'));

        $this->QuickSettings->run($this, array("YahooIntegration"), $id);
        $this->set('url', '/admin/social/yahoo/');
    }

}
