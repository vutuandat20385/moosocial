<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('PaypalExpress.Lib','PaypalExpressCore');

class PaypalExpresssController extends PaypalExpressAppController{	
	public $check_subscription = false; 	
	public $check_force_login = false;
	
	public function process($type = null,$id = null)
	{
		if (!$type || !$id)
		{
			$this->_showError( __('Item does not exist') );
		}
		$item = MooCore::getInstance()->getItemByType($type,$id);
		$this->_checkExistence($item);
		
		$plugin = $item[key($item)]['moo_plugin'];
		$helperPlugin = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
		if (!$helperPlugin)
		{
			$this->_showError(__('Helper does not exist'));
			return;
		}
		
		$params = $helperPlugin->getParamsPayment($item);
		$paypal = new PaypalExpressCore($this->_setting);
		
		$result = $paypal->getUrlPaymentItem($params);
		$this->set('error',false);
		if ($result)
		{
			if ($result['status'])
			{
				$this->set('url_redirect',$result['url']);
			}
			else
			{
				$this->set('error',$result['message']);
			}
		}
	}

	
	public function ipn()
	{		
		if ($this->_setting['ipn_log'])
		{
			$this->log("IPN Item",'paypal_express');
			$this->log(print_r($_REQUEST,true),'paypal_express');
		}
		
		$paypal = new PaypalExpressCore($this->_setting);
		
		$paypal->ipnItem();
		
		die();
	}
	
	public function ipn_recurring()
	{		
		if ($this->_setting['ipn_log'])
		{
			$this->log("IPN Recurring",'paypal_express');
			$this->log(print_r($_REQUEST,true),'paypal_express');
		}
		
		$paypal = new PaypalExpressCore($this->_setting);
		
		$paypal->ipnRecurringItem();
		die();
	}
	
	public function return_recurring()
	{
		$id = isset($this->request->query['id']) ? $this->request->query['id']: '';
		$type = isset($this->request->query['type']) ? $this->request->query['type']: '';
		$token = isset($this->request->query['token']) ? $this->request->query['token']: '';
		
		if (!$type || !$id || !$token)
		{
			$this->_showError( __('Item does not exist') );
		}
		$item = MooCore::getInstance()->getItemByType($type,$id);
		$this->_checkExistence($item);
		
		$plugin = $item[key($item)]['moo_plugin'];
		$helperPlugin = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
		if (!$helperPlugin)
		{
			$this->_showError(__('Helper does not exist'));
			return;
		}
		
		$params = $helperPlugin->getParamsPayment($item);
		$paypal = new PaypalExpressCore($this->_setting);
		
		$result = $paypal->doCreateRecurring($params,$token,$type,$id);
		$this->set('error',false);
		if ($result)
		{
			if ($result['status'])
			{
				$this->redirect($params['return_url']);
			}
			else
			{
				$this->set('error',$result['message']);
			}
		}
	}
	
	public function return_item()
	{
		$id = isset($this->request->query['id']) ? $this->request->query['id']: '';
		$type = isset($this->request->query['type']) ? $this->request->query['type']: '';
		$token = isset($this->request->query['token']) ? $this->request->query['token']: '';
		
		if (!$type || !$id || !$token)
		{
			$this->_showError( __('Item does not exist') );
		}
		$item = MooCore::getInstance()->getItemByType($type,$id);
		$this->_checkExistence($item);
		
		$plugin = $item[key($item)]['moo_plugin'];
		$helperPlugin = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
		if (!$helperPlugin)
		{
			$this->_showError(__('Helper does not exist'));
			return;
		}
		
		$params = $helperPlugin->getParamsPayment($item);
		$paypal = new PaypalExpressCore($this->_setting);
		
		$result = $paypal->doPayment($token);
		$this->set('error',false);
		if ($result)
		{
			if ($result['status'])
			{				
				$this->redirect($params['return_url']);
			}
			else
			{
				$this->set('error',$result['message']);
			}
		}
	}

}