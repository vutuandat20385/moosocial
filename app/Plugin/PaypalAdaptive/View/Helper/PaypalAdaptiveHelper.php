<?php
App::uses('AppHelper', 'View/Helper');
App::import('PaypalAdaptive.Lib','PaypalAdaptiveCore');

class PaypalAdaptiveHelper extends AppHelper {	
	private $_setting  = null;
	private $_array_currency_support = array('AUD','BRL','CAD','CZK','DKK','EUR','HKD','HUF','ILS','JPY','MYR','MXN',
				'NOK','NZD','PHP','PLN','GBP','RUB','SGD','SEK','CHF','TWD','THB','TRY','USD');
	public function getSetting()
	{
		if ($this->_setting === null)
		{
			$model = MooCore::getInstance()->getModel('PaymentGateway.Gateway');
			$row = $model->findByPlugin('PaypalAdaptive');
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
					$this->_setting['acct1.AppId'] = $result['appid'];
					$this->_setting['email'] = $result['email'];
					$this->_setting['ipn_log'] = $row['Gateway']['ipn_log'];
					$this->_setting['enabled'] = $row['Gateway']['enabled'];
					$this->_setting['max_total'] = $result['max_total'];
					$this->_setting['ending_date'] = $result['ending_date'];
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
		return '/paypal_adaptives/process';
	}
	
	public function checkSupportCurrency($currency)
	{
		return (in_array($currency, $this->_array_currency_support));
	}
	
	public function cancel($params)
	{
		if (isset($params['preapproval_key']))
		{
			$paypal = new PaypalAdaptiveCore($this->getSetting());
			$paypal->cancelPreapproval($params['preapproval_key']);
		}
	}
	
	public function expire($type,$id,$params)
	{
		if (isset($params['preapproval_key']))
		{
			$paypal = new PaypalAdaptiveCore($this->getSetting());
			return $paypal->payWithPreapproval($type,$id,$params);
		}
		return false;
	}
	
	public function cancelExpire($params)
	{
		if (isset($params['preapproval_key']))
		{
			$paypal = new PaypalAdaptiveCore($this->getSetting());
			return $paypal->cancelPreapproval($params['preapproval_key']);
		}
		return false;
	}
	
	public function cancelRecurring($params)
	{
		return $this->cancelExpire($params);
	}
	
	public function refund($params)
	{
		if (isset($params['transaction']['transactionId']))
		{
			$paypal = new PaypalAdaptiveCore($this->getSetting());
			$retsult = $paypal->refund($params['transaction']['transactionId']);
			if (isset($params['preapproval_key']))
				return ($retsult) ? 2 : 0;
			return $retsult;
		}
	}
	
	public function checkSetting($params)
	{
		$paypal = new PaypalAdaptiveCore($this->getSetting());
		return $paypal->checkSetting($params);
	}
}
