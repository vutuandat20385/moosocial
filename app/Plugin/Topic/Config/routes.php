<?php

if (Configure::read('Topic.topic_enabled')) {
    Router::connect("/topics/:action/*", array(
        'plugin' => 'Topic',
        'controller' => 'topics'
    ));

    Router::connect("/topics/*", array(
        'plugin' => 'Topic',
        'controller' => 'topics',
        'action' => 'index'
    ));
}

