<?php
MooCache::getInstance()->setCache('subscription', array('groups' => array('subscription')));
App::uses('SubListener','Subscription.Lib');
CakeEventManager::instance()->attach(new SubListener());

define('SUBSCRIPTION_ONE_TIME',1);
define('SUBSCRIPTION_RECURRING',2);
define('SUBSCRIPTION_TRIAL_RECURRING',3);
define('SUBSCRIPTION_TRIAL_ONE_TIME',4);