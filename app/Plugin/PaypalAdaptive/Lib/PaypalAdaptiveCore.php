<?php 
use PayPal\Service\AdaptivePaymentsService;
use PayPal\Types\AP\PayRequest;
use PayPal\Types\Common\RequestEnvelope;
use PayPal\Types\AP\Receiver;
use PayPal\Types\AP\PaymentDetailsRequest;
use PayPal\Types\AP\ReceiverList;
use PayPal\IPN\PPIPNMessage;
use PayPal\Types\AP\PreapprovalRequest;
use PayPal\Types\AP\CancelPreapprovalRequest;
use PayPal\Types\AP\RefundRequest;
use PayPal\Types\AP\SetPaymentOptionsRequest;
use PayPal\Types\AP\SenderOptions;
use PayPal\Types\AP\ExecutePaymentRequest;


require("vendor/autoload.php");

App::import('PaymentGateway.Lib','PaymentGatewayCore');

class PaypalAdaptiveCore extends PaymentGatewayCore
{
	private $_live_link = 'https://www.paypal.com';
	private $_bn_code = 'SocialLOFT_SP';
	private $_sandbox_link = 'https://www.sandbox.paypal.com';
	protected  $_plugin = 'PaypalAdaptive';
	private $_link = '';
	private $_service = null;
	private $_prekey = array();
	
	public function getService()
	{
		if ($this->_service == null)
		{
			$this->_service = new AdaptivePaymentsService($this->_setting);
		}
		return $this->_service;
	}
	public function __construct($setting = null)
	{
		parent::__construct($setting);
		if ($this->_setting['mode'] == 'sandbox')
		{
			$this->_link = $this->_sandbox_link;
		}
		else
		{
			$this->_link = $this->_live_link;
		}
	}
	
	
	public function getUrlPaymentItem($params)
	{
		if ((isset($params['is_recurring']) && $params['is_recurring']) || (isset($params['trial_duration']) && $params['trial_duration']))
		{
			$result = $this->getUrlPaymentPreapproval($params);
		}
		else
		{
			if (isset($params['first_amount']) && $params['first_amount'] > 0)
			{
				$params['amount'] = $params['first_amount'];
			}
			$result = $this->getUrlPaymentPay($params);
		}
		
		return $result;
	}
	
	public function getUrlPaymentPay($params)
	{
		$payRequest = new PayRequest();
		
		$receiver = array();
		$receiver[0] = new Receiver();
		$receiver[0]->amount = $params['amount'];
		$receiver[0]->email = $this->_setting['email'];

		$receiverList = new ReceiverList($receiver);
		$payRequest->receiverList = $receiverList;
		
		$requestEnvelope = new RequestEnvelope(Configure::read('Config.language'));
		$payRequest->requestEnvelope = $requestEnvelope; 
		$payRequest->actionType = "CREATE";
		$payRequest->cancelUrl = $params['cancel_url'];
		$payRequest->returnUrl = $params['return_url'];
		$payRequest->currencyCode = $params['currency'];
		$payRequest->memo = $params['description'];
		$ssl_mode = Configure::read('core.ssl_mode');
        $http = (!empty($ssl_mode)) ? 'https' :  'http';
        
		$payRequest->ipnNotificationUrl = $http.'://'.$_SERVER['SERVER_NAME'].$this->_request->base.'/paypal_adaptives/ipn?type='.$params['type'].'&id='.$params['id'];
		
		$adaptivePaymentsService = $this->getService();
		$response = $adaptivePaymentsService->Pay($payRequest);
		
		$result = array('status'=>false,'message'=>__('Error respone'));		
		if ($response)
		{
			if(strtoupper($response->responseEnvelope->ack) == 'SUCCESS') {
				$token = $response->payKey;
				$result['status'] = true;
				
				if (false)
				{
					$result['url'] = $this->_link.'/webapps/adaptivepayment/flow/pay?paykey='.$token.'&expType=mini';
				}
				else
				{
					$result['url'] = $this->_link.'/cgi-bin/webscr?cmd=_ap-payment&paykey=' . $token;
				}
				
				//set bn code
				$setPaymentOptionsRequest = new SetPaymentOptionsRequest(new RequestEnvelope("en_US"));
				$setPaymentOptionsRequest->payKey = $token;
				$setPaymentOptionsRequest->senderOptions = new SenderOptions();
				$setPaymentOptionsRequest->senderOptions->referrerCode = $this->_bn_code;
				
				$adaptivePaymentsService->SetPaymentOptions($setPaymentOptionsRequest);
			}
			else 
			{
				$result['message'] = $response->error[0]->message;
			}
		}
		
		return $result;
	}
	
	public function getUrlPaymentPreapproval($params)
	{
		$tz_string = "America/Los_Angeles"; // Use one from list of TZ names http://php.net/manual/en/timezones.php 
		$tz_object = new DateTimeZone($tz_string); 
		
		$datetime = new DateTime(); 
		$datetime->setTimezone($tz_object); 		
		
		$requestEnvelope = new RequestEnvelope(Configure::read('Config.language'));
		$preapprovalRequest = new PreapprovalRequest($requestEnvelope, $params['cancel_url'], 
				$params['currency'], $params['return_url'], $datetime->format('Y-m-d'));
		
		if (isset($params['end_date']) && $params['end_date'])
		{
			if ($this->_setting['ending_date'])
			{				
				$datetime_end = new DateTime($params['end_date']);
				$datetime_end->setTimezone($tz_object);				
				
				$datetime_now = new DateTime(); 
				$datetime_now->setTimezone($tz_object);
				$datetime_now->modify('+'.$this->_setting['ending_date'].' year');
				$datetime_now->modify('-1 day');
				
				if (strtotime($datetime_end->format('Y-m-d')) > strtotime($datetime_now->format('Y-m-d')))
				{
					$preapprovalRequest->endingDate =  $datetime_now->format('Y-m-d');
				}
				else
				{
					$preapprovalRequest->endingDate =  $datetime_end->format('Y-m-d');
				}
			}
			else
			{
				$preapprovalRequest->endingDate =  date('Y-m-d',strtotime($params['end_date']));
			}
			
		}
		else 
		{
			if ($this->_setting['ending_date'])
			{
				$datetime->modify('+'.$this->_setting['ending_date'].' year');
				$datetime->modify('-1 day');
				$preapprovalRequest->endingDate = $datetime->format('Y-m-d'); 
			}
		}
				
		if (isset($params['total_amount']) && $params['total_amount'])
		{
			if ($this->_setting['max_total'] && $params['total_amount'] > $this->_setting['max_total'])
			{
				$preapprovalRequest->maxTotalAmountOfAllPayments = $this->_setting['max_total']; 
			}
			else
			{
				$preapprovalRequest->maxTotalAmountOfAllPayments = $params['total_amount']; 
			}
		}
		else
		{
			if ($this->_setting['max_total'])
			{				
				$preapprovalRequest->maxTotalAmountOfAllPayments = $this->_setting['max_total']; 
			}
		}
		
		$preapprovalRequest->memo = $params['description'];
		
		$adaptivePaymentsService = $this->getService();
		$ssl_mode = Configure::read('core.ssl_mode');
        $http = (!empty($ssl_mode)) ? 'https' :  'http';
		$preapprovalRequest->ipnNotificationUrl = $http.'://'.$_SERVER['SERVER_NAME'].$this->_request->base.'/paypal_adaptives/ipn_preapproval?type='.$params['type'].'&id='.$params['id'];

		$response = $adaptivePaymentsService->Preapproval($preapprovalRequest);
		
		$result = array('status'=>false,'message'=>__('Error respone'));		
		if ($response)
		{
			if(strtoupper($response->responseEnvelope->ack) == 'SUCCESS') {
				$token = $response->preapprovalKey;
				$result['status'] = true;
				
				if (MooCore::getInstance()->isMobile(null))
				{
					$result['url'] = $this->_link.'/webapps/adaptivepayment/flow/preapproval?preapprovalKey='.$token.'&expType=redirect';
				}
				else
				{
					$result['url'] = $this->_link.'/cgi-bin/webscr?cmd=_ap-preapproval&preapprovalkey=' . $token;
				}
				
			}
			else 
			{
				$result['message'] = $response->error[0]->message;
			}
		}
		
		return $result;
	}
	
	public function ipnItem()
	{
		$ipnMessage = new PPIPNMessage(null, $this->_setting); 	
		if($ipnMessage->validate()) 
		{
			$data = $ipnMessage->getRawData();			
			$this->log('Success: Got invalid IPN data');
			$this->log('Start execute');
			$type = @$_REQUEST['type'];
			$id = @$_REQUEST['id'];
			
			if (!$id || !$type)
			{
				$this->log('End: no item here');	
				return;
			}
			
			$item = MooCore::getInstance()->getItemByType($type,$id);
			if (!$item)
			{
				$this->log('End: no item here');	
				return;
			}
			
			$plugin = $item[key($item)]['moo_plugin'];
			$helperPlugin = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
			if (!$helperPlugin)
			{
				$this->log('End: no helper here');	
				return;
			}
			
			if (isset($data['reason_code']) && $data['reason_code'] == 'Refund')
			{
				if ($data['status'] != 'COMPLETED')
				{
					if (!method_exists($helperPlugin, 'onRefundFailed'))
					{
						$this->log('End: no function onRefundFailed here');	
						return;
					}
					$helperPlugin->onRefundFailed($item);
				}
				else
				{
					if (!method_exists($helperPlugin, 'onRefund'))
					{
						$this->log('End: no function onRefund here');	
						return;
					}				
					$helperPlugin->onRefund($item);
				}
			}
			else
			{
				if ($data['status'] != 'COMPLETED')
				{
					if (!method_exists($helperPlugin, 'onFailure'))
					{
						$this->log('End: no function onFailure here');	
						return;
					}
					$helperPlugin->onFailure($item,$data);
				}
				else
				{
					$result = $this->getDetailPayment($data['pay_key']);
					if ($result['status'])
					{
						$data['transaction'] = $this->convertObjectToArray($result['response']->paymentInfoList->paymentInfo[0]);
					}
					if (!method_exists($helperPlugin, 'onSuccessful'))
					{
						$this->log('End: no function onSuccessful here');	
						return;
					}			
					$transaction_id = isset($data['transaction']['txn_id']) ? $data['transaction']['txn_id'] : '';
					$helperPlugin->onSuccessful($item,$data,$data['transaction']['receiver']['amount'],$transaction_id);
				}
			}
			$this->log('End: Successful callback');	
		} 
		else 
		{
			$this->log('Error: Got invalid IPN data');	
		}
	}
	
	public function ipnPreapprovalItem()
	{
		$ipnMessage = new PPIPNMessage(null, $this->_setting); 	
		if($ipnMessage->validate()) 
		{
			$data = $ipnMessage->getRawData();
			$this->log('Success: Got invalid IPN data');
			$this->log('Start execute');
			$type = @$_REQUEST['type'];
			$id = @$_REQUEST['id'];
			
			if (!$id || !$type)
			{
				$this->log('End: no item here');	
				return;
			}
			
			$item = MooCore::getInstance()->getItemByType($type,$id);
			if (!$item)
			{
				$this->log('End: no item here');	
				return;
			}
			
			$plugin = $item[key($item)]['moo_plugin'];
			$helperPlugin = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
			if (!$helperPlugin)
			{
				$this->log('End: no helper here');	
				return;
			}
			
			if ($data['status'] == 'ACTIVE')
			{
				$params = $helperPlugin->getParamsPayment($item);
				$params['amount'] = $params['first_amount'];
				if (isset($params['first_amount']) && $params['first_amount'] > 0)
				{
					$result = $this->payPreapproval($data['preapproval_key'], $params);
					if (!$result['status'])
					{
						$this->log('Error: When payPreapproval '.$result['message']);	
						
						if (!method_exists($helperPlugin, 'onFailure'))
						{
							$this->log('End: no function onFailure here');	
							return;
						}
						$helperPlugin->onFailure($item,$data);
						$this->log('End: Successful callback');	
						return;
					}
					$data['transaction'] = $result['transaction']; 
				}
				
				if (!method_exists($helperPlugin, 'onSuccessful'))
				{
					$this->log('End: no function onSuccessful here');	
					return;
				}
				$transaction_id = isset($data['transaction']['txn_id']) ? $data['transaction']['txn_id'] : '';
				$helperPlugin->onSuccessful($item,$data,$params['amount'],$transaction_id);
			}
			elseif ($data['status'] == 'CANCELED')
			{
				//nothing todo
			}
			else
			{
				if (!method_exists($helperPlugin, 'onFailure'))
				{
					$this->log('End: no function onFailure here');	
					return;
				}
				$helperPlugin->onFailure($item,$data);
			}
			$this->log('End: Successful callback');	
		} 
		else 
		{
			$this->log('Error: Got invalid IPN data');	
		}
	}
	
	public function payWithPreapproval($type,$id,$params_pre)
	{
		$this->log('Start: pay with preapproval');
		if (!$id || !$type)
		{
			$this->log('End: no item here');	
			return false;
		}
		
		$item = MooCore::getInstance()->getItemByType($type,$id);
		if (!$item)
		{
			$this->log('End: no item here');	
			return false;
		}
		
		$plugin = $item[key($item)]['moo_plugin'];
		$helperPlugin = MooCore::getInstance()->getHelper($plugin . '_' . $plugin);
		if (!$helperPlugin)
		{
			$this->log('End: no helper here');	
			return false;
		}
		
		$params = $helperPlugin->getParamsPayment($item);

		$result = $this->payPreapproval($params_pre['preapproval_key'], $params);
		if (!$result['status'])
		{
			$this->log('End: When payPreapproval '.$result['message']);	
			return false;
		}
		$params_pre['transaction'] = $result['transaction']; 

		if (!method_exists($helperPlugin, 'onSuccessful'))
		{
			$this->log('End: no function onSuccessful here');	
			return false;
		}
		$transaction_id = isset($result['transaction']['txn_id']) ? $result['transaction']['txn_id'] : '';
		$helperPlugin->onSuccessful($item,$params_pre,$params['amount'],$transaction_id,true);
		$this->log('End: pay with preapprova successful');
		return true;
	}
	
	public function payPreapproval($preKey,$params)
	{
		$payRequest = new PayRequest();
		
		$receiver = array();
		$receiver[0] = new Receiver();
		$receiver[0]->amount = $params['amount'];
		$receiver[0]->email = $this->_setting['email'];

		$receiverList = new ReceiverList($receiver);
		$payRequest->receiverList = $receiverList;
		
		$requestEnvelope = new RequestEnvelope(Configure::read('Config.language'));
		$payRequest->requestEnvelope = $requestEnvelope; 
		$payRequest->actionType = "CREATE";
		$payRequest->currencyCode = $params['currency'];
		$payRequest->cancelUrl = $params['cancel_url'];
		$payRequest->returnUrl = $params['return_url'];
		$payRequest->preapprovalKey = $preKey;
		
		$payRequest->memo = $params['description'];		
		
		$adaptivePaymentsService = $this->getService();
		$response = $adaptivePaymentsService->Pay($payRequest);
		
		$result = array('status'=>false,'message'=>__('Error response'));		
		$this->log($response);
		if ($response)
		{
			$this->log('Start pay Preapproval');
			$this->log($response);
			if(strtoupper($response->responseEnvelope->ack) == 'SUCCESS') 
			{							
				$token = $response->payKey;
								
				//set bn code
				$setPaymentOptionsRequest = new SetPaymentOptionsRequest(Configure::read('Config.language'));
				$setPaymentOptionsRequest->payKey = $token;
				$setPaymentOptionsRequest->senderOptions = new SenderOptions();
				$setPaymentOptionsRequest->senderOptions->referrerCode = $this->_bn_code;
				
				$adaptivePaymentsService->SetPaymentOptions($setPaymentOptionsRequest);
				
				$this->log('Start pay execute');
				$executePaymentRequest = new ExecutePaymentRequest(new RequestEnvelope(Configure::read('Config.language')),$token);
				$executePaymentRequest->actionType = 'PAY';
				
				$response = $adaptivePaymentsService->ExecutePayment($executePaymentRequest);
				$this->log($response);
				if(strtoupper($response->responseEnvelope->ack) == 'SUCCESS') 
				{
					$result['status'] = true;
					$response = $this->getDetailPayment($token);
					if ($response['status'])
					{
						$result['transaction'] = $this->convertObjectToArray($response['response']->paymentInfoList->paymentInfo[0]);
					}					
				}
				else
				{
					$result['message'] = $response->error[0]->message;
				}
			}
			else 
			{
				$result['message'] = $response->error[0]->message;
			}
		}
		
		return $result;
	}
	
	public function getDetailPayment($pay_key)
	{
		$service = $this->getService();
		$requestEnvelope = new RequestEnvelope(Configure::read('Config.language'));
		$paymentDetailsReq = new PaymentDetailsRequest($requestEnvelope);
		$paymentDetailsReq->payKey = $pay_key;
		$response = $service->PaymentDetails($paymentDetailsReq);
		
		$this->log($response);
		$result = array();
		if(strtoupper($response->responseEnvelope->ack) == 'SUCCESS') 
		{			
			$result['status'] = true;
			$result['response'] = $response;
		}
		else 
		{
			$result['message'] = $response->error[0]->message;
		}
		
		return $result;
	}
	
	public function cancelPreapproval($preKey)
	{
		if (isset($this->_prekey[$preKey]))
		{
			return;
		}
		$this->_prekey[$preKey] = true;
		
		$this->log('Cancel Preapproval : '.$preKey);
		
		$service = $this->getService();
		$requestEnvelope = new RequestEnvelope(Configure::read('Config.language'));
		$cancelPreapprovalReq = new CancelPreapprovalRequest($requestEnvelope, $preKey);
		
		try {			
			$response = $service->CancelPreapproval($cancelPreapprovalReq);
			$this->log('Cancel Preapproval Respone');
			$this->log($response);
			
			if(strtoupper($response->responseEnvelope->ack) == 'SUCCESS')
			{
				return true;
			}
		} catch(Exception $ex) {
			$this->log($ex);
		}
		
		return false;
	}
	
	public function refund($transactionId)
	{
		$refundRequest = new RefundRequest(new RequestEnvelope(Configure::read('Config.language')));
		$refundRequest->transactionId = $transactionId;
		
		$service = $this->getService();
		$this->log('Refund with  : '.$transactionId);
		$response = $service->Refund($refundRequest);
		$this->log('Respone refund');
		$this->log($response);
		
		if(strtoupper($response->responseEnvelope->ack) == 'SUCCESS')
		{
			return 1;
		}
		
		return 0;
	}
	
	public function checkSetting($params)
	{
		$payRequest = new PayRequest();
		$receiver = array();
		$receiver[0] = new Receiver();
		$receiver[0]->amount = "1.00";
		$receiver[0]->email = $params['email'];
		$receiverList = new ReceiverList($receiver);
		$payRequest->receiverList = $receiverList;		
		
		
		$requestEnvelope = new RequestEnvelope("en_US");
		$payRequest->requestEnvelope = $requestEnvelope; 
		$payRequest->actionType = "PAY";
		$payRequest->cancelUrl = FULL_BASE_URL;
		$payRequest->returnUrl = FULL_BASE_URL;
		$payRequest->currencyCode = "USD";
		$payRequest->ipnNotificationUrl = FULL_BASE_URL;
		
		$sdkConfig = array(
			"mode" => ($params['test_mode'] ? "sandbox" : 'live'),
			"acct1.UserName" => $params['username'],
			"acct1.Password" => $params['password'],
			"acct1.Signature" => $params['signature'],
			"acct1.AppId" => $params['appid']
		);
		
		$adaptivePaymentsService = new AdaptivePaymentsService($sdkConfig);
		$payResponse = $adaptivePaymentsService->Pay($payRequest);		
		
		$result = array('status'=>false,'message'=>__('Error respone'));		
		
		if ($payResponse)
		{
			if(strtoupper($payResponse->responseEnvelope->ack) == 'SUCCESS') 
			{
				$result['status'] = true;
			}
			else 
			{
				$result['message'] = $payResponse->error[0]->message;
			}
		}
		
		return $result;
	}
	
	public function log($msg, $type = LOG_ERR, $scope = null)
	{
		if ($this->_setting['ipn_log'])
		{
			if (!is_string($msg))
			{
				$msg = print_r($msg,true);
			}
			
			parent::log($msg,'paypal_adaptive');
		}
	}
	
	public function convertObjectToArray($data)
	{
		return json_decode(json_encode($data), true);
	}
}