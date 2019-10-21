<?php 
CakeLog::config('paypal_adaptive', array(
    'engine' => 'FileLog',
));

App::uses('PaypalAdaptiveListener', 'PaypalAdaptive.Lib');
CakeEventManager::instance()->attach(new PaypalAdaptiveListener());
?>