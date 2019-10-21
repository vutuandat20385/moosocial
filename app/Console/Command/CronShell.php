<?php
/**
 * 
 * Socialloft
 * Tungnv
 * 
 */

class CronShell extends AppShell {
	public function run()
	{
		$params = array();
		if (isset($this->args['0']))
			$params['key'] = $this->args['0'];
		$this->loadModel('Cron.Task');
		
		$this->Task->run($params); 		
	}
}
