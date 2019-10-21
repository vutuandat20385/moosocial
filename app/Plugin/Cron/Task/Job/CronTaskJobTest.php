<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('Cron.Task/Job','CronTaskJobAbstract');
class CronTaskJobTest extends CronTaskJobAbstract
{
	protected function _execute()
  	{
  		echo 'test';
  		$this->_setIsComplete(true);
  	}
}