<?php
App::uses('CakeEventListener', 'Event');
class PaypalAdaptiveListener implements CakeEventListener
{
    public function implementedEvents()
    {
        return array(
            'Plugin.PaymentGateway.Managers.save_validate' => 'saveValidate',
        );
    }
    
    public function saveValidate($event)
    {
    	$e = $event->subject();
    	if (isset($e->request->data['Gateway']['plugin']) && 
    		$e->request->data['Gateway']['plugin'] == 'PaypalAdaptive')
    	{    		
    		$message = array();
    		if (!$e->request->data['Gateway']['config']['username'])
    		{
    			$message[] = __('Paypal API Username is required');
    		}
    		
    		if (!$e->request->data['Gateway']['config']['password'])
    		{
    			$message[] = __('Paypal API Password is required');
    		}
    		
    		if (!$e->request->data['Gateway']['config']['signature'])
    		{
    			$message[] = __('Paypal API Signature is required');
    		}
    		
    		if (!$e->request->data['Gateway']['config']['appid'])
    		{
    			$message[] = __('Paypal API AppId is required');
    		}
    		
    		if (!$e->request->data['Gateway']['config']['email'])
    		{
    			$message[] = __('Paypal Email is required');
    		}
    		if (!count($message))
    		{
    			$params = $e->request->data['Gateway']['config'];
    			$params['test_mode'] = $e->request->data['Gateway']['test_mode'];    			
    			$helper = MooCore::getInstance()->getHelper('PaypalAdaptive_PaypalAdaptive');
    			$result = $helper->checkSetting($params);
    			if (!$result['status'])
    			{
    				$message[] = $result['message'];
    			}
    		}
    		$event->result['messages'] = $message;
    		
    	}
    	
    }
} 
?>