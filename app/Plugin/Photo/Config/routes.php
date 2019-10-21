<?php

if (Configure::read('Photo.photo_enabled')) {
    Router::connect("/albums/:action/*", array(
        'plugin' => 'Photo',
        'controller' => 'albums'
    ));
    
    Router::connect("/photos/:action/*", array(
        'plugin' => 'Photo',
        'controller' => 'photos'
    ));

    Router::connect("/photos/*", array(
        'plugin' => 'Photo',
        'controller' => 'photos',
        'action' => 'index'
    ));
    Router::connect("/albums/*", array(
        'plugin' => 'Photo',
        'controller' => 'albums',
        'action' => 'index'
    ));
}

