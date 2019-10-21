<?php

MooCache::getInstance()->setCache('topic', array('groups' => array('topic')));
App::uses('TopicApiListener','Topic.Lib');
CakeEventManager::instance()->attach(new TopicApiListener());

if (Configure::read('Topic.topic_enabled')) {
    App::uses('TopicListener', 'Topic.Lib');
    CakeEventManager::instance()->attach(new TopicListener());
    
    MooSeo::getInstance()->addSitemapEntity("Topic", array(
    	'topic'
    ));
}