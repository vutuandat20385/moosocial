<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

App::uses('AppHelper', 'View/Helper');
class PageHelper extends AppHelper {
	public function getItemSitemMap($name,$limit,$offset)
	{		
		$pageModel = MooCore::getInstance()->getModel("Page.Page");
		$pages = $pageModel->find('all',array(
			'conditions' => array(
				'Page.permission' => '',
				'Page.type'=>'Page'				
			),
			'limit' => $limit,
			'offset' => $offset
		));
			
		$urls = array();
		foreach ($pages as $page)
		{
			$urls[] = FULL_BASE_URL.$page['Page']['moo_href'];
		}
			
		return $urls;
	}
}
