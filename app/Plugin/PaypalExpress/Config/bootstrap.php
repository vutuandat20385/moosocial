<?php 
CakeLog::config('paypal_express', array(
    'engine' => 'FileLog',
));

App::uses('PaypalExpressListener', 'PaypalExpress.Lib');
CakeEventManager::instance()->attach(new PaypalExpressListener());
?>