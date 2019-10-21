<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class RefundsController extends SubscriptionAppController 
{
    public $components = array('Paginator');
    
    public function __construct($request = null, $response = null) 
    {
        parent::__construct($request, $response);
        $this->url = '/admin/subscription/refunds/';
        $this->url_accept = '/admin/subscription/refunds/accept';
        $this->url_deny = '/admin/subscription/refunds/deny';
        $this->set('url', $this->url);
        $this->set('url_deny', $this->url_deny);
        $this->set('url_accept', $this->url_accept);
        $this->loadModel('Subscription.SubscriptionRefund');
        $this->loadModel('Subscription.Subscribe');
        $this->loadModel('Subscription.SubscriptionPackagePlan');
    }
    
    public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_checkPermission(array('super_admin' => 1));
	}
    
    public function admin_index()
    {
        $this->Paginator->settings = array(
            'limit' => 10,
            'order' => array(
                'SubscriptionRefund.created' => 'DESC'
            )
        );
        $cond = array();
        if ( !empty( $this->request->data['plan_id'] ) )
        {
        	$cond['SubscriptionRefund.plan_id'] = $this->request->data['plan_id'];
        	$this->set('plan_id',$this->request->data['plan_id']);
        }
        
    	if ( !empty( $this->request->data['name'] ) )
        {
        	$cond['User.name LIKE'] = '%'.$this->request->data['name'].'%';
        	$this->set('name',$this->request->data['name']);
        }
        
    	if ( !empty( $this->request->data['status'] ) )
        {
        	$cond['SubscriptionRefund.status'] = $this->request->data['status'];
        	$this->set('status',$this->request->data['status']);
        }
        
   		if ( !empty( $this->request->data['start_date'] ) )
        {
        	$cond['SubscriptionRefund.created >'] = $this->request->data['start_date'];
        	$this->set('start_date',$this->request->data['start_date']);
        }
        
    	if ( !empty( $this->request->data['end_date'] ) )
        {
        	$cond['SubscriptionRefund.created <'] = $this->request->data['end_date'];
        	$this->set('end_date',$this->request->data['end_date']);
        }
        
        $refunds = $this->Paginator->paginate('SubscriptionRefund',$cond);
        $this->set('refunds', $refunds);
        
        $plans = $this->SubscriptionPackagePlan->find('all');
        $this->set('plans',$plans);
    }
    
    public function admin_deny()
    {
    	$id = $this->request->data['id'];
    	$refund = $this->SubscriptionRefund->findById($id);
    	$result = array('status'=>0);
    	
    	if (!$refund || $refund['SubscriptionRefund']['status'] != 'initial')
    	{
    		echo json_encode($result);
            exit;
    	}
    	
    	$this->SubscriptionRefund->id = $id;
    	$this->SubscriptionRefund->save(array('status'=>'denied'));
    	
    	$subscribe = $this->Subscribe->findById($refund['Subscribe']['id']);
    	$this->Subscribe->id = $refund['Subscribe']['id'];
    	$this->Subscribe->save(array('is_request_refund'=>0));
    	
    	$this->Session->setFlash(__('Your refund request has been denied'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
		$helper = MooCore::getInstance()->getHelper('Subscription_Subscription');
		//Send email
		$ssl_mode = Configure::read('core.ssl_mode');
        $http = (!empty($ssl_mode)) ? 'https' :  'http';
        $mailComponent = MooCore::getInstance()->getComponent('Mail.MooMail');
        $request = Router::getRequest();
        $params = array(
        	'subscription_title' => $subscribe['SubscriptionPackage']['name'],
        	'subscription_description' => $subscribe['SubscriptionPackage']['description'],        	
        	'reason' => $this->request->data['reason'],
        	'link' => $http.'://'.$_SERVER['SERVER_NAME'].$request->base.'/users/member_login',
        	'plan_title' => $subscribe['SubscriptionPackagePlan']['title'],
			'plan_description' => $helper->getPlanDescription($subscribe['SubscriptionPackagePlan'], $subscribe['Subscribe']['currency_code'])
        );
        $mailComponent->send(array('User'=>$refund['User']),'subscription_refund_deny',$params);
		
		$result['status'] = 1;
		echo json_encode($result);
		exit;
    }
    
   	public function admin_accept()
   	{
   		$id = $this->request->data['id'];
    	$refund = $this->SubscriptionRefund->findById($id);
    	$result = array('status'=>0);
    	
    	if (!$refund || $refund['SubscriptionRefund']['status'] != 'initial')
    	{
    		echo json_encode($result);
            exit;
    	}
    	$subscribe = $this->Subscribe->findById($refund['Subscribe']['id']);
		
		$helper = MooCore::getInstance()->getHelper('Subscription_Subscription');
		$result_refund = $helper->doRefund($subscribe,$refund);
		if (!$result_refund)
		{
			echo json_encode($result);
            exit;
		}
    	
    	$this->Session->setFlash(__('Your refund request has been granted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));    	
		
		$result['status'] = 1;
		echo json_encode($result);
		exit;
   	}
}
