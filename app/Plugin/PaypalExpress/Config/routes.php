<?php
Router::connect('/paypal_expresss/:action/*', array(
    'plugin' => 'PaypalExpress',
    'controller' => 'paypal_expresss'
));

Router::connect('/paypal_expresss/*', array(
    'plugin' => 'PaypalExpress',
    'controller' => 'paypal_expresss',
    'action' => 'index'
));
