<?php

if (!defined('FULL_BASE_LOCAL_URL')) {
    if (Configure::read('Storage.storage_localcdn_enable') == "1" && Configure::read('Storage.storage_current_type') == "local"  && strpos($_SERVER['REQUEST_URI'], '/admin') === false){
        define('FULL_BASE_LOCAL_URL',Configure::read('Storage.storage_local_cdn_mapping'));
        //Configure::write('App.cssBaseUrl', FULL_BASE_LOCAL_URL.'/css/');
    }else{
        $s = null;
        if (env('HTTPS')) {
            $s = 's';
        }

        $httpHost = env('HTTP_HOST');

        if (isset($httpHost)) {
            define('FULL_BASE_LOCAL_URL', 'http' . $s . '://' . $httpHost);
        }
        unset($httpHost, $s);
    }
}

MooCache::getInstance()->setCache('storage', array('groups' => array('storage'),
		'duration' => '+1 week',
		'probability' => 100,
		'prefix' => 'cake_storage_',
		'path' => CACHE . 'storage' . DS));

MooCache::getInstance()->setCache('storage_short', array('groups' => array('storage'),
		'duration' => '+1 hours',
		'probability' => 100,
		'prefix' => 'cake_storage_short',
		'path' => CACHE . 'storage' . DS));

//if (Configure::read('Chat.event_enabled')) {
App::uses('StorageListener', 'Storage.Lib');
CakeEventManager::instance()->attach(new StorageListener());

App::uses('StorageBlogListener', 'Storage.Lib');
CakeEventManager::instance()->attach(new StorageBlogListener());

App::uses('StorageVideoListener', 'Storage.Lib');
CakeEventManager::instance()->attach(new StorageVideoListener());

App::uses('StorageTopicListener', 'Storage.Lib');
CakeEventManager::instance()->attach(new StorageTopicListener());

App::uses('StorageGroupListener', 'Storage.Lib');
CakeEventManager::instance()->attach(new StorageGroupListener());

App::uses('StorageEventListener', 'Storage.Lib');
CakeEventManager::instance()->attach(new StorageEventListener());

App::uses('StoragePhotoListener', 'Storage.Lib');
CakeEventManager::instance()->attach(new StoragePhotoListener());
//}