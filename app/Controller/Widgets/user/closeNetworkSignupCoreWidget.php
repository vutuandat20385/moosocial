<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class closeNetworkSignupCoreWidget extends Widget {
    public function beforeRender(Controller $controller) {
        // load spam challenge if enabled
        if ( Configure::read('core.enable_spam_challenge') )
        {
            $controller->loadModel('SpamChallenge');
            $challenges = $controller->SpamChallenge->findAllByActive(1);

            if ( !empty( $challenges ) )
            {
                $rand = array_rand( $challenges );

                $controller->Session->write('spam_challenge_id', $challenges[$rand]['SpamChallenge']['id']);
                $controller->set('challenge', $challenges[$rand]);
            }
        }
    }
}