<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('PaypalAdaptive.Lib','PaypalAdaptiveCore');

class PaypalAdaptivesController extends PaypalAdaptiveAppController{	
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
		$paypal = new PaypalAdaptiveCore($this->_setting);
		
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
			$this->log(print_r($_REQUEST,true),'paypal_adaptive');
		}
		
		$paypal = new PaypalAdaptiveCore($this->_setting);
		
		$paypal->ipnItem();
		
		die();
	}
	
	public function ipn_preapproval()
	{
		if ($this->_setting['ipn_log'])
		{
			$this->log(print_r($_REQUEST,true),'paypal_adaptive');
		}
		
		$paypal = new PaypalAdaptiveCore($this->_setting);
		
		$paypal->ipnPreapprovalItem();
		die();
	}

}