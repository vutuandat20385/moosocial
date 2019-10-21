<?php

MooCache::getInstance()->setCache('menu', array('groups' => array('menu')));

App::uses('MenuListener', 'Menu.Lib');
CakeEventManager::instance()->attach(new MenuListener());
