<?php

MooCache::getInstance()->setCache('photo', array('groups' => array('photo')));

if (Configure::read('Photo.photo_enabled')) {
    App::uses('PhotoListener', 'Photo.Lib');
    CakeEventManager::instance()->attach(new PhotoListener());

    App::uses('PhotoApiListener','Photo.Lib');
    CakeEventManager::instance()->attach(new PhotoApiListener());

    MooSeo::getInstance()->addSitemapEntity("Photo", array(
    	'album'
    ));
}