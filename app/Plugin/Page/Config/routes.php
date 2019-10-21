<?php
if(Configure::read('Page.page_enabled') != 0){
//Router::connect("/pages/:action/*",array('plugin'=>'Page','controller'=>'pages'));
Router::connect("/pages/*",array('plugin'=>'Page','controller'=>'pages','action'=>'display'));
}