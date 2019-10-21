<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class SocialIntegrationListener implements CakeEventListener {

    public function implementedEvents() {
        // TODO
        return array(
            'Controller.Facebook.login' => 'processLogin',
            'AppController.doBeforeFilter' => 'doBeforeFilter',
        );
    }

    public function processLogin(CakeEvent $event) {
        $this->User = ClassRegistry::init('User');
        $this->SocialUser = ClassRegistry::init('SocialIntegration.SocialUser');

        $provider_user = $event->data['data'];
        $user = $this->User->findByEmail($provider_user['email']);

        if (!empty($user)) {
            $social_user = $this->SocialUser->findByUserId($user['User']['id']);

            if (!empty($social_user)) {
                
            } else {
                // Go to login page    
            }
        } else {
            // Go to sign up step    
        }
    }
    // MOOSOCIAL-1369
    public function doBeforeFilter($event)
    {
        $e = $event->subject();
        // set session Facebook SDK version
        $e->Session->write('facebook_sdk_version', Configure::read('FacebookIntegration.facebook_sdk_version'));

    }
}
