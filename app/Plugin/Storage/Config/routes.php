<?php
Router::connect('/storages/:action/*', array(
    'plugin' => 'Storage',
    'controller' => 'storages'
));

Router::connect('/storages/*', array(
    'plugin' => 'Storage',
    'controller' => 'storages',
    'action' => 'index'
));
