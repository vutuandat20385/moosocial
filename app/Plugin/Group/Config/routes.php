<?php

if (Configure::read('Group.group_enabled')) {

    Router::connect("/groups/:action/*", array(
        'plugin' => 'Group',
        'controller' => 'groups'
    ));

    Router::connect("/groups/*", array(
        'plugin' => 'Group',
        'controller' => 'groups',
        'action' => 'index'
    ));
}

