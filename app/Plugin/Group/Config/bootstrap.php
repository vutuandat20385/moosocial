<?php

MooCache::getInstance()->setCache('group', array('groups' => array('group')));

if (Configure::read('Group.group_enabled')) {
    App::uses('GroupListener', 'Group.Lib');
    CakeEventManager::instance()->attach(new GroupListener());
    
     App::uses('GroupApiListener','Group.Lib');
    CakeEventManager::instance()->attach(new GroupApiListener());
    
    MooSeo::getInstance()->addSitemapEntity("Group", array(
    	'group'
    ));
}