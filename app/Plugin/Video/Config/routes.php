<?php
//debug(Configure::read('Video.video_enabled'));die();
if(Configure::read('Video.video_enabled')){
    Router::connect("/videos/:action/*",array('plugin'=>'Video','controller'=>'videos'));
    Router::connect("/videos/*",array('plugin'=>'Video','controller'=>'videos','action'=>'index'));
}
