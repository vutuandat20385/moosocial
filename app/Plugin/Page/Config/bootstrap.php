<?php
MooCache::getInstance()->setCache('page', array('groups' => array('page')));

MooSeo::getInstance()->addSitemapEntity("Page", array(
	'page'
));
