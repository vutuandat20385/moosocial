<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class ConnectController extends SocialIntegrationAppController {

    public function beforeFilter() {
        parent::beforeFilter();
    }

    public function index() {

        $uid = $this->Auth->user('id');
        if (!$uid) {
            $this->redirect('/');
        }

        $this->loadModel('SocialIntegration.SocialUser');
        $provider = array(
            array(
                'provider' => 'facebook',
                'connect' => $this->SocialUser->connect('facebook')
            ),
            array(
                'provider' => 'google',
                'connect' => $this->SocialUser->connect('google')
            )
        );
        $this->set('providers', $provider);
    }

}
