<?php
MooCache::getInstance()->setCache('event', array('groups' => array('event')));

if (Configure::read('Event.event_enabled')) {	
    App::uses('EventListener', 'Event.Lib');
    CakeEventManager::instance()->attach(new EventListener());
    
    App::uses('EventApiListener','Event.Lib');
    CakeEventManager::instance()->attach(new EventApiListener());
    
    MooSeo::getInstance()->addSitemapEntity("Event", array(
    	'event'
    ));
}