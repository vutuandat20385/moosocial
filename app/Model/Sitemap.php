<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');

class Sitemap extends AppModel {
	
	public function generateSitemap()
	{		
		$isFetched = true;
		
		if ( !MooSeo::getInstance()->getConfig('sitemap_build_in_progress') )
		{
			$entities = MooSeo::getInstance()->getSitemapEntities();
			$sitemap_ignore_key = MooSeo::getInstance()->getConfig("sitemap_ignore_key");
			$sitemap_ignore_key = explode(",", $sitemap_ignore_key);
			$tmp = array();
			foreach ($entities as $key => $value)
			{
				if (!in_array($key, $sitemap_ignore_key))
				{
					$tmp[$key] = $value;
				}
			}
			
			$entities = $tmp;
			
			$setting_entities = MooSeo::getInstance()->getConfig("sitemap_entities");
			foreach ($entities as $plugin => $entitie)
			{
				if (isset($setting_entities[$plugin]['items']))
				{
					$entities[$plugin]['items'] = $setting_entities[$plugin]['items'];
				}
			}
			
			$maxCount = MooSeo::getInstance()->getConfig('sitemap_entitites_max_count');
			$limit = MooSeo::getInstance()->getConfig('sitemap_entitites_limit');
			
			if ( $entities )
			{
				foreach ( $entities as $plugin => $data )
				{
					if ( !$data['enabled'] )
					{
						continue;
					}
				
					foreach ( $data['items'] as $key=>$item )
					{
						if ( $item['data_fetched'] )
						{
							continue;
						}
						
						if ( $item['urls_count'] + $limit > $maxCount )
						{
							$limit = $maxCount - $item['urls_count'];
						}
				
						if ($plugin == 'Core')
						{
							$helper = MooCore::getInstance()->getHelper('Core_Moo');
						}
						else
						{
							$helper = MooCore::getInstance()->getHelper($plugin.'_'.$plugin);
						}
						
						if ($helper && method_exists($helper, 'getItemSitemMap'))
						{
							$urls = $helper->getItemSitemMap($item['name'],$limit,$item['urls_count']);
							$newUrlsCount = count($urls);
							$totalUrlsCount = (int) $item['urls_count'] + $newUrlsCount;
							
							$isFetched = false;
							
							if (!$newUrlsCount || $newUrlsCount != $limit || $totalUrlsCount >= $maxCount)
							{
								$item['data_fetched'] = true;
								$item['urls_count'] = $totalUrlsCount;
								$entities[$plugin]['items'][$key] = $item;
							}
							else
							{
								$item['urls_count'] = $totalUrlsCount;
								$entities[$plugin]['items'][$key] = $item;
							}
							
							if ( $newUrlsCount )
							{
								
								foreach ( $urls as $url )
								{
									if ( !$this->checkExist($url) )
									{
										$this->clear();
										$this->save(array('url'=>$url,'type'=>$plugin));
									}
								}
							}
							
							break 2;
						}

					}
				}
			}
			//update entities
			MooSeo::getInstance()->updateConfig('sitemap_entities', $entities);
		}
		
		if ($isFetched)
		{
			$this->buildXMLSitemap();
		}
	}
	
	public function buildXMLSitemap()
	{
		MooSeo::getInstance()->updateConfig('sitemap_build_in_progress', 1);
		
		$urls = $this->find('all',array('limit'=>MooSeo::getInstance()->getConfig('sitemap_max_urls_in_file')));
		
		$newSitemapBuild = MooSeo::getInstance()->getConfig('sitemap_last_build') + 1;
		$entities = MooSeo::getInstance()->getSitemapEntities();
		$sitemapIndex = MooSeo::getInstance()->getConfig('sitemap_index');
		$newSitemapPath = $this->getBaseSitemapPath() . $newSitemapBuild . '/';
		
		if ( !file_exists($newSitemapPath) )
		{
			mkdir($newSitemapPath);
			@chmod($newSitemapPath, 0777);
		}
		
		if ( count($urls ))
		{
			$urlIds = array();
			
			// generate parts of sitemap
			$processedUrls   = array();
			
			foreach ($urls as $url)
			{
				$urlIds[] = $url['Sitemap']['id'];
				$processedUrls[] = array(
						'url' => $this->escapeSitemapUrl($url['Sitemap']['url']),
						'changefreq' => $entities[$url['Sitemap']['type']]['changefreq'],
						'priority' => $entities[$url['Sitemap']['type']]['priority'],
				);
			}
			
			$urlIds = array_chunk($urlIds, 500);
			foreach( $urlIds as $urlId )
			{
				$this->deleteAll(array('id'=>$urlId));
			}
			
			$view = MooCore::getInstance()->getMooView();
			$xml = $view->element("sitemap_path",array('urls'=>$processedUrls));
			
			file_put_contents($newSitemapPath . sprintf(MooSeo::SITEMAP_FILE_NAME, $sitemapIndex + 1), $xml);
			
			MooSeo::getInstance()->updateConfig('sitemap_index', $sitemapIndex + 1);
			
			return;
		}
		
		// generate a final sitemap index file
		if ( $sitemapIndex )
		{
			$sitemapParts = array();
			$lastModDate = date('c', time());
		
			for ( $i = 1; $i <= $sitemapIndex; $i++ )
			{
				$sitemapParts[] = array(
					'url' => $this->escapeSitemapUrl($this->getSitemapUrl($i)),
					'lastmod' => $lastModDate
				);
			}
		
			$view = MooCore::getInstance()->getMooView();
			$xml = $view->element("sitemap",array('urls'=>$sitemapParts));
		
			file_put_contents($newSitemapPath . sprintf(MooSeo::SITEMAP_FILE_NAME, ''), $xml);

			MooSeo::getInstance()->updateConfig('sitemap_index', 0);
			MooSeo::getInstance()->updateConfig('sitemap_last_start', time());
			MooSeo::getInstance()->updateConfig('sitemap_last_build', $newSitemapBuild);
			
			$this->query("TRUNCATE TABLE ".$this->tablePrefix."sitemaps;");
		}
		
		MooSeo::getInstance()->updateConfig('sitemap_entities', '');
		
		$previousBuildPath = $this->getBaseSitemapPath() . ($newSitemapBuild - 1) . '/';
		
		if ( file_exists($previousBuildPath) )
		{
			App::uses('Folder', 'Utility');
			$folder = new Folder($previousBuildPath);
			if ($folder->delete()) {
				// Successfully deleted foo and its nested folders
			}
		}
		
		MooSeo::getInstance()->updateConfig('sitemap_build_in_progress', 0);
	}
	
	public function getSitemapUrl($part = null)
	{
		$request = Router::getRequest();
		return  FULL_BASE_URL.$request->base.'/sitemap.xml?part='.$part;
	}
	
	private function escapeSitemapUrl($url)
	{
		if (function_exists('mb_convert_encoding')) {
        	$url = mb_convert_encoding($url, 'utf-8');
        }
		return htmlspecialchars($url, ENT_QUOTES);
	}
	
	private function getBaseSitemapPath()
	{
		$path = APP . 'webroot' . DS .'uploads' . DS .'sitemap'. DS;
		
		if ( !file_exists($path) )
		{
			mkdir($path);
			@chmod($path, 0777);
		}
		
		return $path;
	}
	
	public function checkExist($url)
	{
		return $this->findByUrl($url);
	}
}
