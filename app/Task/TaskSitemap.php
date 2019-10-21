<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('Cron.Task','CronTaskAbstract');
class TaskSitemap extends CronTaskAbstract
{
    public function execute()
    {
    	if (!MooSeo::getInstance()->getConfig("sitemap_enable"))
    		return;
    	
    	$in_progress = MooSeo::getInstance()->getConfig("sitemap_in_progress");
    	$in_progress_time = MooSeo::getInstance()->getConfig("sitemap_in_progress_time");
    	$next_buid = MooSeo::getInstance()->readyForNextBuild();
    	if ( (!$in_progress || time() - $in_progress_time >= 3600) && $next_buid)
    	{
    		MooSeo::getInstance()->updateConfig('sitemap_in_progress', 1);
    		MooSeo::getInstance()->updateConfig('sitemap_in_progress_time', time());
    		
    		$siteMapModel = MooCore::getInstance()->getModel("Sitemap");
    		$siteMapModel->generateSitemap();
    	
    		MooSeo::getInstance()->updateConfig('sitemap_in_progress', 0);
    	}
    }
}