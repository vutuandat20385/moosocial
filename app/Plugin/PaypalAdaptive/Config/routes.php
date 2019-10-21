<?php
Router::connect('/paypal_adaptives/:action/*', array(
    'plugin' => 'PaypalAdaptive',
    'controller' => 'paypal_adaptives'
));

Router::connect('/paypal_adaptives/*', array(
    'plugin' => 'PaypalAdaptive',
    'controller' => 'paypal_adaptives',
    'action' => 'index'
));
