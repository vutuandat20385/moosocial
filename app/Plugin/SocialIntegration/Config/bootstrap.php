<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */

App::uses('SocialIntegrationListener', 'SocialIntegration.Event');
App::uses('CakeEventManager', 'Event');

CakeEventManager::instance()->attach(new SocialIntegrationListener());
