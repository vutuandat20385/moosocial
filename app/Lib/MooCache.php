<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class MooCache
{
	private $_settings;
	public static function getInstance()
	{
		static $instance = null;
		if (null === $instance) {
			$instance = new MooCache();
		}
		
		return $instance;
	}
	
	public function __construct()
	{
		$duration = '+999 days';
		if (Configure::read('debug') >= 1) {
			$duration = '+10 seconds';
		}
		
		$this->_settings = array(
			'engine' => 'File',
			'serialize' => true,
			'duration' => $duration
		);
		
		
		if ( file_exists( APP . 'Config/cache.php' ) )
		{
			$config = include (APP.'Config/cache.php');
			
			$this->_settings = $config;
			$this->_settings['duration'] = $duration;
			
			switch ($this->_settings['engine'])
			{
				case 'File':
					$this->_settings['serialize'] = true;
					break;
				case 'Memcached':
					$link = $this->_settings['host'].(($this->_settings['port']) ? ':'.$this->_settings['port']: 0 );
					$this->_settings['servers'] = array($link);
					if (!$this->_settings['login'])
					{
						unset($this->_settings['login']);
						unset($this->_settings['password']);
					}
					break;
				case 'Redis':
					$this->_settings['server'] = $this->_settings['host'];
					if (!$this->_settings['port'])
						$this->_settings['port'] = 0;
						
					break;
			}
		}
	}
	
	public function setCache($name,$params = array())
	{
		Cache::config($name, array_merge($this->_settings,$params));
	}
}
?>