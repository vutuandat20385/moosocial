<?php 
use PayPal\Service\PayPalAPIInterfaceServiceService;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsReq;
use PayPal\PayPalAPI\GetExpressCheckoutDetailsRequestType;
use PayPal\EBLBaseComponents\DoExpressCheckoutPaymentRequestDetailsType;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentRequestType;
use PayPal\PayPalAPI\DoExpressCheckoutPaymentReq;
use PayPal\PayPalAPI\RefundTransactionReq;
use PayPal\PayPalAPI\RefundTransactionRequestType;
use PayPal\EBLBaseComponents\BillingAgreementDetailsType;
use PayPal\EBLBaseComponents\RecurringPaymentsProfileDetailsType;
use PayPal\EBLBaseComponents\BillingPeriodDetailsType;
use PayPal\EBLBaseComponents\ScheduleDetailsType;
use PayPal\EBLBaseComponents\CreateRecurringPaymentsProfileRequestDetailsType;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileRequestType;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileReq;
use PayPal\IPN\PPIPNMessage;
use PayPal\EBLBaseComponents\ActivationDetailsType;
use PayPal\EBLBaseComponents\ManageRecurringPaymentsProfileStatusRequestDetailsType;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusReq;
use PayPal\PayPalAPI\ManageRecurringPaymentsProfileStatusRequestType;

require("vendor/autoload.php");

App::import('PaymentGateway.Lib','PaymentGatewayCore');
App::uses('CakeEvent', 'Event');
App::uses('CakeEventListener', 'Event');
App::uses('CakeEventManager', 'Event');

class PaypalExpressCore extends PaymentGatewayCore
{
	private $_live_link = 'https://www.paypal.com';
	private $_sandbox_link = 'https://www.sandbox.paypal.com';
	protected  $_plugin = 'PaypalExpress';
	private $_service = null;
	
	public function getService()
	{
		if ($this->_service == null)
		{
			$this->_service = new PayPalAPIInterfaceServiceService($this->_setting);
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
	
	public function ipnItem()
	{
		$ipnMessage = new PPIPNMessage(null, $this->_setting);
		if($ipnMessage->validate())
		{
			$data = $ipnMessage->getRawData();
			$this->log('Success: Got invalid IPN data');
			$this->log($data);
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
						
			if ($data['payment_status'] == 'Refunded')
			{
				if (!method_exists($helperPlugin, 'onRefund'))
				{
					$this->log('End: no function onRefund here');
					return;
				}
				$helperPlugin->onRefund($item);
			}
			else
			{
				if ($data['payment_status'] != 'Completed')
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
					if (!method_exists($helperPlugin, 'onSuccessful'))
					{
						$this->log('End: no function onSuccessful here');
						return;
					}
					$helperPlugin->onSuccessful($item,$data,$data['mc_gross'],$data['txn_id']);
				}
			}
			$this->log('End: Successful callback');
		}
		else
		{
			$this->log('Error: Got invalid IPN data');
		}
	}
	
	public function ipnRecurringItem()
	{
		$ipnMessage = new PPIPNMessage(null, $this->_setting);
		if($ipnMessage->validate())
		{
			$data = $ipnMessage->getRawData();
			$this->log('Success: Got invalid IPN recurring data');
			$this->log($data);
			$this->log('Start execute');
			
			if ($data['payment_status'] == 'Refunded')
			{
				$cakeEvent = new CakeEventManager();
				$cakeEvent->dispatch(new CakeEvent('PaypalExpress.Ipn.Refunded', $this,$data));
			}
			else
			{
				$type = '';
				$id = '';
				if (isset($data['rp_invoice_id']))
				{
					$tmp = explode('|', $data['rp_invoice_id']);
					$type = @$tmp[0];
					$id = @$tmp[1];
				}
				
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
				
				switch ($data['txn_type'])
				{
					case 'recurring_payment':
						if ($data['payment_status'] != 'Completed')
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
							if (!method_exists($helperPlugin, 'onSuccessful'))
							{
								$this->log('End: no function onSuccessful here');
								return;
							}
							$helperPlugin->onSuccessful($item,$data,$data['mc_gross'],$data['txn_id']);
						}
						break;
						
					case 'recurring_payment_profile_created':
						if (isset($data['initial_payment_status']))
						{
							if ($data['initial_payment_status'] != 'Completed')
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
								if (!method_exists($helperPlugin, 'onSuccessful'))
								{
									$this->log('End: no function onSuccessful here');
									return;
								}
								$data['txn_id'] = $data['initial_payment_txn_id'];
								$helperPlugin->onSuccessful($item,$data,$data['initial_payment_amount'],$data['txn_id']);
							}
						}
						elseif (isset($data['initial_payment_amount']) && $data['initial_payment_amount'] == 0) 
						{
							if (!method_exists($helperPlugin, 'onSuccessful'))
							{
								$this->log('End: no function onSuccessful here');
								return;
							}
							$helperPlugin->onSuccessful($item,$data,$data['initial_payment_amount']);
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
						break;
					case 'recurring_payment_profile_cancel':
						if (!method_exists($helperPlugin, 'doCancel'))
						{
							$this->log('End: no function doCancel here');
							return;
						}
						$helperPlugin->doCancel($item);
					
						break;
				}
			}
			$this->log('End: Successful callback');
		}
		else
		{
			$this->log('Error: Got invalid IPN recurring data');
		}
	}
	
	public function getUrlPaymentItem($params)
	{
		if (isset($params['is_recurring']) && $params['is_recurring'])
		{
			$result = $this->getUrlPaymentRecurring($params);
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
	
	public function getUrlPaymentRecurring($params)
	{
		$ssl_mode = Configure::read('core.ssl_mode');
		$http = (!empty($ssl_mode)) ? 'https' :  'http';
		
		$paymentDetails = new PaymentDetailsType();
		//$paymentDetails->OrderTotal = new BasicAmountType($params['currency'],$params['amount']);
		$paymentDetails->NotifyURL =  $http.'://'.$_SERVER['SERVER_NAME'].$this->_request->base.'/paypal_expresss/ipn_recurring?type='.$params['type'].'&id='.$params['id'];	
		
		$setECReqDetails = new SetExpressCheckoutRequestDetailsType();
		$billingAgreementDetails = new BillingAgreementDetailsType("RecurringPayments");
		$billingAgreementDetails->BillingAgreementDescription = substr(strip_tags($params['description']),0,127);
		$setECReqDetails->BillingAgreementDetails = array($billingAgreementDetails);
		
		$setECReqDetails->PaymentDetails[0] = $paymentDetails;
		$setECReqDetails->ReturnURL = $http.'://'.$_SERVER['SERVER_NAME'].$this->_request->base.'/paypal_expresss/return_recurring?type='.$params['type'].'&id='.$params['id'].'&app_no_tab=1';
		if (strpos($params['cancel_url'], '?') === false)
		{
			$setECReqDetails->CancelURL = $params['cancel_url'].'?app_no_tab=1';
		}
		else 
		{
			$setECReqDetails->CancelURL = $params['cancel_url'].'&app_no_tab=1';
		}
		
		$setECReqType = new SetExpressCheckoutRequestType();
		$setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
		
		$setECReq = new SetExpressCheckoutReq();
		$setECReq->SetExpressCheckoutRequest = $setECReqType;
		
		$service = $this->getService();
		$setECResponse = $service->SetExpressCheckout($setECReq);
		$result = array('status'=>false,'message'=>__('Error respone'));
		if ($setECResponse->Ack == 'Success')
		{
			$token = $setECResponse->Token;
			$result['status'] = true;
			$result['url'] = $this->_link.'/cgi-bin/webscr?cmd=_express-checkout&token='.$token;
		}
		else
		{
			$result['message'] = $setECResponse->Errors[0]->LongMessage;
		}
		
		return $result;
	}

	public function calculatorStartDate($cycle,$cycle_type)
	{
		switch($cycle_type)
		{
			case 'day':
				$result = strtotime("+$cycle Day");
				break;
			case 'week':
				$result = strtotime("+$cycle Week");
				break;
			case 'month':
				$result = strtotime("+$cycle Month");
				break;
			case 'year':
				$result = strtotime("+$cycle Year");
				break;
		}
		return gmdate("Y-m-d\TH:i:s\Z", $result);
	}
	
	public function doCreateRecurring($params,$token,$type,$id)
	{
		$this->log("CreateRecurring: Token ".$token);
		$getExpressCheckoutDetailsRequest = new GetExpressCheckoutDetailsRequestType($token);
		
		$getExpressCheckoutReq = new GetExpressCheckoutDetailsReq();
		$getExpressCheckoutReq->GetExpressCheckoutDetailsRequest = $getExpressCheckoutDetailsRequest;
		$service = $this->getService();
		
		$result = array('status'=>false);
		
		$getECResponse = $service->GetExpressCheckoutDetails($getExpressCheckoutReq);
		$this->log("GetCheckoutDetail: Token ".$token);
		$this->log($getECResponse);
		if ($getECResponse->Ack == 'Success')
		{			
			$RPProfileDetails = new RecurringPaymentsProfileDetailsType();			
			$RPProfileDetails->ProfileReference = $type.'|'.$id;
			
			$paymentBillingPeriod =  new BillingPeriodDetailsType();
			$paymentBillingPeriod->BillingFrequency = $params['cycle'];
			$paymentBillingPeriod->BillingPeriod = ucfirst($params['cycle_type']);
			if ($params['end_date'])
			{
				$tmp = $params['duration'] / $params['cycle'];
				if (round($tmp) != $tmp)
				{
					$tmp++;
				}
				$paymentBillingPeriod->TotalBillingCycles = floor($tmp);
			}
			
			$start_date = '';
			if (isset($params['trial_duration']) && $params['trial_duration'])
			{
				$start_date  = $this->calculatorStartDate($params['trial_duration'], $params['trial_duration_type']);
			}
			else
			{
				$start_date = $this->calculatorStartDate($params['cycle'], $params['cycle_type']);
				if ($paymentBillingPeriod->TotalBillingCycles)
					$paymentBillingPeriod->TotalBillingCycles--;
			}
			
			$RPProfileDetails->BillingStartDate = $start_date;

			$paymentBillingPeriod->Amount = new BasicAmountType($params['currency'], $params['amount']);
			
			$first_amount = $params['amount'];
			if (isset($params['first_amount']))
			{
				$first_amount = $params['first_amount'];
			}
			$activationDetails = new ActivationDetailsType();
			$activationDetails->InitialAmount = new BasicAmountType($params['currency'], $first_amount);
			
			$scheduleDetails = new ScheduleDetailsType();
			$scheduleDetails->Description = $params['description'];
			$scheduleDetails->PaymentPeriod = $paymentBillingPeriod;
			$scheduleDetails->ActivationDetails = $activationDetails;
			
			$createRPProfileRequestDetail = new CreateRecurringPaymentsProfileRequestDetailsType();
			$createRPProfileRequestDetail->Token  = $token;
			
			$createRPProfileRequestDetail->ScheduleDetails = $scheduleDetails;
			$createRPProfileRequestDetail->RecurringPaymentsProfileDetails = $RPProfileDetails;
			$createRPProfileRequest = new CreateRecurringPaymentsProfileRequestType();
			$createRPProfileRequest->CreateRecurringPaymentsProfileRequestDetails = $createRPProfileRequestDetail;
			
			
			$createRPProfileReq =  new CreateRecurringPaymentsProfileReq();
			$createRPProfileReq->CreateRecurringPaymentsProfileRequest = $createRPProfileRequest;
			$this->log($createRPProfileReq);
			$paypalService = $this->getService();			
			try {
				$createRPProfileResponse = $paypalService->CreateRecurringPaymentsProfile($createRPProfileReq);
				$this->log("Response requrest create recurring");
				$this->log($createRPProfileResponse);
				if ($createRPProfileResponse->Ack == 'Success')
				{
					$result['status'] = true;
					$result['data'] = $this->convertObjectToArray($createRPProfileResponse);
				}
				else
				{
					$result['message'] = $createRPProfileResponse->Errors[0]->LongMessage;
				}
			}
			catch (Exception $e)
			{
				$result['message'] = $e->getMessage();
			}
			
		}
		else
		{
			$result['message'] = $getECResponse->Errors[0]->LongMessage;
		}
		return $result;
	}
	
	public function getUrlPaymentPay($params)
	{
		$ssl_mode = Configure::read('core.ssl_mode');
		$http = (!empty($ssl_mode)) ? 'https' :  'http';
		
		$paymentDetails = new PaymentDetailsType();
		$paymentDetails->OrderTotal = new BasicAmountType($params['currency'],$params['amount']);
		$paymentDetails->NotifyURL =  $http.'://'.$_SERVER['SERVER_NAME'].$this->_request->base.'/paypal_expresss/ipn?type='.$params['type'].'&id='.$params['id'];
		
		$itemDetails = new PaymentDetailsItemType();
		$itemDetails->Name = $params['description'];
		$itemDetails->Amount = $params['amount'];
		$itemDetails->Quantity =1;
		
		$paymentDetails->PaymentDetailsItem[0] = $itemDetails;
		
		
		$setECReqDetails = new SetExpressCheckoutRequestDetailsType();
		$setECReqDetails->PaymentDetails[0] = $paymentDetails;
		$setECReqDetails->ReturnURL = $http.'://'.$_SERVER['SERVER_NAME'].$this->_request->base.'/paypal_expresss/return_item?type='.$params['type'].'&id='.$params['id'].'&app_no_tab=1';
		if (strpos($params['cancel_url'], '?') === false)
		{
			$setECReqDetails->CancelURL = $params['cancel_url'].'?app_no_tab=1';
		}
		else
		{
			$setECReqDetails->CancelURL = $params['cancel_url'].'&app_no_tab=1';
		}
		
		$setECReqType = new SetExpressCheckoutRequestType();
		$setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
		
		$setECReq = new SetExpressCheckoutReq();
		$setECReq->SetExpressCheckoutRequest = $setECReqType;
		
		$service = $this->getService();
		$setECResponse = $service->SetExpressCheckout($setECReq);
		$result = array('status'=>false,'message'=>__('Error respone'));
		if ($setECResponse->Ack == 'Success')
		{
			$token = $setECResponse->Token;
			$result['status'] = true;
			$result['url'] = $this->_link.'/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token='.$token;
		}
		else
		{
			$result['message'] = $setECResponse->Errors[0]->LongMessage;
		}
		
		return $result;
	}
	
	public function doPayment($token)
	{
		$this->log("DoCheckout: Token ".$token);
		$getExpressCheckoutDetailsRequest = new GetExpressCheckoutDetailsRequestType($token);
		
		$getExpressCheckoutReq = new GetExpressCheckoutDetailsReq();
		$getExpressCheckoutReq->GetExpressCheckoutDetailsRequest = $getExpressCheckoutDetailsRequest;
		$service = $this->getService();
		
		$result = array('status'=>false);
		
		$getECResponse = $service->GetExpressCheckoutDetails($getExpressCheckoutReq);
		$this->log("GetCheckoutDetail: Token ".$token);
		$this->log($getECResponse);
		if ($getECResponse->Ack == 'Success')
		{
			$DoECRequestDetails = new DoExpressCheckoutPaymentRequestDetailsType();
			$DoECRequestDetails->PayerID = $getECResponse->GetExpressCheckoutDetailsResponseDetails->PayerInfo->PayerID;
			$DoECRequestDetails->Token = $token;
			$DoECRequestDetails->PaymentDetails = $getECResponse->GetExpressCheckoutDetailsResponseDetails->PaymentDetails;
			
			$DoECRequest = new DoExpressCheckoutPaymentRequestType();
			$DoECRequest->DoExpressCheckoutPaymentRequestDetails = $DoECRequestDetails;
			
			$DoECReq = new DoExpressCheckoutPaymentReq();
			$DoECReq->DoExpressCheckoutPaymentRequest = $DoECRequest;
			
			$DoECResponse = $service->DoExpressCheckoutPayment($DoECReq);
			$this->log($DoECResponse);
			if ($DoECResponse->Ack == 'Success')
			{
				$result['status'] = true;
				$result['data'] = $this->convertObjectToArray($DoECResponse);
			}
			else
			{
				$result['message'] = $getECResponse->Errors[0]->LongMessage;
			}
			
		}
		else
		{
			$result['message'] = $getECResponse->Errors[0]->LongMessage;
		}
		return $result;
	}
	
	public function checkSetting($params)
	{
		$paymentDetails = new PaymentDetailsType();
		$paymentDetails->OrderTotal = new BasicAmountType('USD',1);
		$paymentDetails->NotifyURL = 'https://community.moosocial.com/';
		
		$itemDetails = new PaymentDetailsItemType();
		$itemDetails->Name = 'Test';
		$itemDetails->Amount = 1;
		$itemDetails->Quantity =1;
		
		$paymentDetails->PaymentDetailsItem[0] = $itemDetails;
		
		
		$setECReqDetails = new SetExpressCheckoutRequestDetailsType();
		$setECReqDetails->PaymentDetails[0] = $paymentDetails;
		$setECReqDetails->ReturnURL = FULL_BASE_URL;
		$setECReqDetails->CancelURL = FULL_BASE_URL;
		
		$setECReqType = new SetExpressCheckoutRequestType();
		$setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
		
		$setECReq = new SetExpressCheckoutReq();
		$setECReq->SetExpressCheckoutRequest = $setECReqType;
		$config = array(
				'mode' => $params['test_mode'] ? 'sandbox' : 'live',
				"acct1.UserName" => $params['username'],
				"acct1.Password" => $params['password'],
				"acct1.Signature" => $params['signature'],
		);
		$service= new PayPalAPIInterfaceServiceService($config);
		
		$setECResponse = $service->SetExpressCheckout($setECReq);
		$result = array('status'=>false,'message'=>__('Error respone'));
		if ($setECResponse->Ack == 'Success')
		{
			$result['status'] = true;
		}
		else
		{
			$result['message'] = $setECResponse->Errors[0]->LongMessage;
		}
		
		return $result;
	}
	
	public function refund($transaction_id)
	{
		$this->log("Refund: Transaction id ".$transaction_id);
		
		$service= $this->getService();
		$refundReqest = new RefundTransactionRequestType();
		$refundReqest->TransactionID = $transaction_id;
		
		$refundReq = new RefundTransactionReq();
		$refundReq->RefundTransactionRequest = $refundReqest;
		
		$refundResponse = $service->RefundTransaction($refundReq);
		
		$this->log($refundResponse);
		
		if ($refundResponse->Ack == 'Success')
		{
			return 1;
		}
		
		return 0;
	}
	
	public function cancelProfile($profile_id)
	{
		$manageRPPStatusReqestDetails = new ManageRecurringPaymentsProfileStatusRequestDetailsType();
		$manageRPPStatusReqestDetails->ProfileID =  $profile_id;
		
		$manageRPPStatusReqest = new ManageRecurringPaymentsProfileStatusRequestType();
		$manageRPPStatusReqest->ManageRecurringPaymentsProfileStatusRequestDetails = $manageRPPStatusReqestDetails;
		
		$manageRPPStatusReq = new ManageRecurringPaymentsProfileStatusReq();
		$manageRPPStatusReq->ManageRecurringPaymentsProfileStatusRequest = $manageRPPStatusReqest;
		
		$this->log("Cancel: Profile ".$profile_id);
		
		$service= $this->getService();
		try {
			$manageRPPStatusResponse = $service->ManageRecurringPaymentsProfileStatus($manageRPPStatusReq);
			$this->log($manageRPPStatusResponse);
			
			if(strtoupper($manageRPPStatusResponse->Ack) == 'SUCCESS')
			{
				return true;
			}
		}
		catch (Exception $e)
		{
			
		}
		
		return false;
	}
	
	public function log($msg, $type = LOG_ERR, $scope = null)
	{
		if ($this->_setting['ipn_log'])
		{
			if (!is_string($msg))
			{
				$msg = print_r($msg,true);
			}
			
			parent::log($msg,'paypal_express');
		}
	}
	
	public function convertObjectToArray($data)
	{
		return json_decode(json_encode($data), true);
	}
}