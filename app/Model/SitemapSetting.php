<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');

class SitemapSetting extends AppModel {
	private $_settings = null;
	public function afterDelete(){
		Cache::delete('sitemap_setting');
	}
	
	public function afterSave($created, $options = array()) 
	{
		Cache::delete('sitemap_setting');
	}
	
	public function getSettings()
	{		
		if ($this->_settings !== null)
			return $this->_settings;
		
		$settings = Cache::read("sitemap_setting"); 
		if (!$settings)
		{
			$settings = $this->find("all");
			$tmp = array();
			foreach ($settings as $setting)
			{
				if ($setting['SitemapSetting']['name'] != 'sitemap_entities')
					$tmp[$setting['SitemapSetting']['name']] = $setting['SitemapSetting']['value'];
				else
					$tmp[$setting['SitemapSetting']['name']] = json_decode($setting['SitemapSetting']['value'],true);
			}
			$settings = $tmp;
			Cache::write("sitemap_setting", $settings);
		}
		$this->_settings = $settings;
		
		return $this->_settings;
	}
	
	public function getConfig($name)
	{
		$settings = $this->getSettings();
		if (isset($settings[$name]))
		{
			return $settings[$name];
		}
		
		return false;
	}
}
