<?php
Router::connect('/lophocs/:action/*', array(
    'plugin' => 'Lophoc',
    'controller' => 'lophocs'
));

Router::connect('/lophocs/*', array(
    'plugin' => 'Lophoc',
    'controller' => 'lophocs',
    'action' => 'index'
));
