<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

App::uses('Controller', 'Controller');

class SitemapController extends AppController
{
	public function index()
	{
		$buid = MooSeo::getInstance()->getConfig('sitemap_last_build');
		$part = isset($this->request->query['part']) ? $this->request->query['part'] : '';
		
		$sitemap = APP . 'webroot' . DS .'uploads' . DS .'sitemap' . DS . $buid . DS . 'sitemap'.$part.'.xml';		
		
		if ( file_exists($sitemap) )
		{
			header('Content-Type: text/xml; charset=utf-8');
		
			echo file_get_contents($sitemap);
			exit;
		}
		
		throw new NotFoundException();
	}
	
	public function admin_setting_save($id = null)
	{
		$sitemap_schedule_update = $this->request->data['sitemap_schedule_update'];
		MooSeo::getInstance()->updateConfig("sitemap_schedule_update", $sitemap_schedule_update);
		
		$sitemap_enable = $this->request->data['sitemap_enable'];
		MooSeo::getInstance()->updateConfig("sitemap_enable", $sitemap_enable);
		
		$sitemap_ignore_key = $this->request->data['sitemap_ignore_key'];
		$lists = MooSeo::getInstance()->getSitemapEntities();
		$values = array();
		foreach ($lists as $key=>$list)
		{			
			if (!in_array($key, $sitemap_ignore_key))
			{
				$values[] = $key;
			}
		}
		MooSeo::getInstance()->updateConfig("sitemap_ignore_key", implode(",", $values));
		
		$sitemap_schedule_update = $this->request->data['sitemap_schedule_update'];
		$this->Session->setFlash(__('Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
		$this->redirect( $this->referer() );
	}
}