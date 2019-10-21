<?php	
	App::uses('CronListener','Cron.Lib');
	CakeEventManager::instance()->attach(new CronListener()); 
?>