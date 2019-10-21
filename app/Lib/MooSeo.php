<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooSeo
{	
	const SITEMAP_ITEM_UPDATE_WEEKLY = 'weekly';
	
	const SITEMAP_ITEM_UPDATE_DAILY = 'daily';

	const SITEMAP_FILE_NAME = 'sitemap%s.xml';
	
	const SITEMAP_UPDATE_DAILY = 'daily';
	
	const SITEMAP_UPDATE_WEEKLY = 'weekly';

	const SITEMAP_UPDATE_MONTHLY = 'monthly';
	
	private $seo_sitemap_entities = array();
	
	
	public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new MooSeo();
        }

        return $instance;
    }
    
    public function __construct()
    {
    	$processedItems = array();
    	$processedItems[] = array(
    		'name' => 'user',
    		'data_fetched' => false,
    		'urls_count' => 0,
    	);
    	
    	$this->seo_sitemap_entities['Core'] = array(
    		'items' => $processedItems,
            'enabled' => true,
            'priority' => 0.5,
            'changefreq' => self::SITEMAP_ITEM_UPDATE_WEEKLY
    	);
    }
    
    public function getSitemapEntities()
    {
    	return $this->seo_sitemap_entities;
    }
   	
	public function addSitemapEntity($plugin, $items, $priority = 0.5, $changeFreq = self::SITEMAP_ITEM_UPDATE_WEEKLY)
    {
        $entities = $this->getSitemapEntities();

        if ( !array_key_exists($plugin, $entities) )
        {
            // process items
            $processedItems = array();
            foreach ($items as $item) {
                $processedItems[] = array(
                    'name' => $item,
                    'data_fetched' => false,
                    'urls_count' => 0,
                );
            }

            $entities[$plugin] = array(
                'items' => $processedItems,
                'enabled' => true,
                'priority' => $priority,
                'changefreq' => $changeFreq
            );
            
            $this->seo_sitemap_entities = $entities;
        }
    }
    
    public function getConfig($name)
    {
    	$settingModel = MooCore::getInstance()->getModel("SitemapSetting");
    	return $settingModel->getConfig($name);
    }
    
    public function updateConfig($name,$value)
    {
    	if (is_array($value))
    	{
    		$value = json_encode($value);
    	}
    	$settingModel = MooCore::getInstance()->getModel("SitemapSetting");
    	$db = $settingModel->getDataSource();
    	$value = $db->value($value, 'string');
    	$settingModel->updateAll(
    		array(
    			'SitemapSetting.value' => $value
    		),
    		array(
    			'SitemapSetting.name' => $name
    		)
    	);
    	
    	Cache::delete('sitemap_setting');
    }
    
    public function readyForNextBuild()
    {
    	$lastStart  = $this->getConfig('sitemap_last_start');
    	$scheduleUpdate = $this->getConfig('sitemap_schedule_update');
    	
    	if ( !$lastStart )
    	{
    		return true;
    	}
    	
    	$secondsInDay = 86400;
    	
    	switch($scheduleUpdate)
    	{
    		case self::SITEMAP_UPDATE_MONTHLY :
    			$delaySeconds = $secondsInDay * 30;
    			break;
    	
    		case self::SITEMAP_UPDATE_WEEKLY :
    			$delaySeconds = $secondsInDay * 7;
    			break;
    	
    		case self::SITEMAP_UPDATE_DAILY:
    		default:
    			$delaySeconds = $secondsInDay;
    	}
    	
    	return time() - $lastStart >= $delaySeconds;
    }
}