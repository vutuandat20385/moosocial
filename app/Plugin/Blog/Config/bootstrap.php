<?php
MooCache::getInstance()->setCache('blog', array('groups' => array('blog')));

if (Configure::read('Blog.blog_enabled')) {
    App::uses('BlogListener', 'Blog.Lib');
    CakeEventManager::instance()->attach(new BlogListener());

    App::uses('BlogApiListener','Blog.Lib');
    CakeEventManager::instance()->attach(new BlogApiListener());

    MooSeo::getInstance()->addSitemapEntity("Blog", array(
    	'blog'	
    ));
}