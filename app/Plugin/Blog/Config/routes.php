<?php
//debug(Configure::read('Blog.blog_enabled'));die();
if(Configure::read('Blog.blog_enabled')){
    Router::connect("/blogs/:action/*",array('plugin'=>'Blog','controller'=>'blogs'));
    Router::connect("/blogs/*",array('plugin'=>'Blog','controller'=>'blogs','action'=>'index'));
}
