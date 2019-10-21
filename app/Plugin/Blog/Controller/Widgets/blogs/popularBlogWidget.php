<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Widget','Controller/Widgets');

class popularBlogWidget extends Widget {
    public function beforeRender(Controller $controller) {
    	$num_item_show = $this->params['num_item_show'];
    	$controller->loadModel('Blog.Blog');
          $user_blocks = array();
            $cuser = $controller->_getUser();
            if($cuser){
                $user_blocks = $controller->getBlockedUsers($cuser['id']);  
            }
            
            if(empty($user_blocks)){
    	$popular_blogs = Cache::read('blog.popular_blog.'.$num_item_show,'blog');
    	if (!$popular_blogs)
    	{
    		$popular_blogs = $controller->Blog->getPopularBlogs( $num_item_show, Configure::read('core.popular_interval') );
    		Cache::write('blog.popular_blog.'.$num_item_show,$popular_blogs,'blog');
    	}
            }else{
                $popular_blogs = $controller->Blog->getPopularBlogs( $num_item_show, Configure::read('core.popular_interval') );
            }
    	
    	$this->setData('popular_blogs', $popular_blogs);
    }
}