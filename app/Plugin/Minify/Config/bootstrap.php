<?php
App::uses('MinifyListener', 'Minify.Lib');
CakeEventManager::instance()->attach(new MinifyListener());