<?php
App::uses('AppHelper', 'View/Helper');
App::import('PaypalExpress.Lib','PaypalExpressCore');

class PaypalExpressHelper extends AppHelper {	
	private $_setting  = null;
	private $_array_currency_support = array('AUD','BRL','CAD','CZK','DKK','EUR','HKD','HUF','ILS','JPY','MYR','MXN',
				'NOK','NZD','PHP','PLN','GBP','RUB','SGD','SEK','CHF','TWD','THB','TRY','USD');
	public function getSetting()
	{
		if ($this->_setting === null)
		{
			$model = MooCore::getInstance()->getModel('PaymentGateway.Gateway');
			$row = $model->findByPlugin('PaypalExpress');
			if ($row)
			{
				if ($row['Gateway']['config'])
				{
					$result = json_decode($row['Gateway']['config'],true);
					if ($row['Gateway']['test_mode'])
						$this->_setting['mode'] = 'sandbox';
					else
						$this->_setting['mode'] = 'live';
						
					$this->_setting['acct1.UserName'] = $result['username'];
					$this->_setting['acct1.Password'] = $result['password'];
					$this->_setting['acct1.Signature'] = $result['signature'];
					$this->_setting['ipn_log'] = $row['Gateway']['ipn_log'];
					$this->_setting['enabled'] = $row['Gateway']['enabled'];
				}
				else
					$this->_setting = false;
			}
			else
			{
				$this->_setting = false;
			}
		}
		
		return $this->_setting;
	}
	
	public function supportRecurring()
	{
		return true;
	}

	public function supportTrial()
	{
		return true;
	}
	
	public function getUrlProcess()
	{
		return '/paypal_expresss/process';
	}
	
	public function checkSupportCurrency($currency)
	{
		return (in_array($currency, $this->_array_currency_support));
	}
	
	public function cancel($params)
	{
		if (isset($params['recurring_payment_id']) && $params['recurring_payment_id'])
		{
			$paypal = new PaypalExpressCore($this->getSetting());
			$paypal->cancelProfile($params['recurring_payment_id']);
		}
	}
	
	public function expire($type,$id,$params)
	{
		if (isset($params['recurring_payment_id']) && $params['recurring_payment_id'])
		{
			$paypal = new PaypalExpressCore($this->getSetting());
			return $paypal->cancelProfile($params['recurring_payment_id']);
		}
		return false;
	}
	
	public function cancelExpire($params)
	{
		if (isset($params['recurring_payment_id']) && $params['recurring_payment_id'])
		{
			$paypal = new PaypalExpressCore($this->getSetting());
			return $paypal->cancelProfile($params['recurring_payment_id']);
		}
		return false;
	}
	
	public function cancelRecurring($params)
	{
		return $this->cancelExpire($params);
	}
	
	public function refund($params)
	{
		if (isset($params['txn_id']))
		{
			$paypal = new PaypalExpressCore($this->getSetting());
			$retsult = $paypal->refund($params['txn_id']);			
			return ($retsult) ? 1 : 0;
		}
	}
	
	public function checkSetting($params)
	{
		$paypal = new PaypalExpressCore($this->getSetting());
		return $paypal->checkSetting($params);
	}
}
