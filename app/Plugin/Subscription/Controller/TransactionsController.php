<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class TransactionsController extends SubscriptionAppController 
{
    public $components = array('Paginator');
    
    public function __construct($request = null, $response = null) 
    {
        parent::__construct($request, $response);
        $this->url = '/admin/subscription/transactions/';
        $this->url_create = $this->url.'create/';
        $this->url_delete = $this->url.'delete/';
        $this->set('url', $this->url);
        $this->set('url_create', $this->url_create);
        $this->set('url_delete', $this->url_delete);
        $this->loadModel('Subscription.SubscriptionTransaction');
        $this->loadModel('Subscription.SubscriptionPackagePlan');
        $this->loadModel('Subscription.SubscriptionPackagePlan');
        $this->loadModel('PaymentGateway.Gateway');
    }
    
    public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_checkPermission(array('super_admin' => 1));
	}
    
    public function admin_index()
    {
        $this->Paginator->settings = array(
            'limit' => 20,
            'order' => array(
                'SubscriptionTransaction.created' => 'DESC'
            )
        );
        
    	$cond = array('SubscriptionTransaction.admin'=>0);
    	$this->request->data = array_merge($this->request->data,$this->request->params['named']);
    	$data_search = array();
        if ( !empty( $this->request->data['plan_id'] ) )
        {
        	$cond['SubscriptionTransaction.plan_id'] = $this->request->data['plan_id'];
        	$this->set('plan_id',$this->request->data['plan_id']);
        	if ($this->request->data['plan_id'])
        		$data_search['plan_id'] = $this->request->data['plan_id'];
        }
        
    	if ( !empty( $this->request->data['gateway_id'] ) )
        {
        	$cond['SubscriptionTransaction.gateway_id'] = $this->request->data['gateway_id'];
        	$this->set('gateway_id',$this->request->data['gateway_id']);
        	if ($this->request->data['gateway_id'])
        		$data_search['gateway_id'] = $this->request->data['gateway_id'];
        }
        
    	if ( !empty( $this->request->data['name'] ) )
        {
        	$cond['User.name LIKE'] = '%'.$this->request->data['name'].'%';
        	$this->set('name',$this->request->data['name']);
        	if ($this->request->data['name'])
        		$data_search['name'] = $this->request->data['name'];
        }
        
    	if ( !empty( $this->request->data['status'] ) )
        {
        	$cond['SubscriptionTransaction.status'] = $this->request->data['status'];
        	$this->set('status',$this->request->data['status']);
        	if ($this->request->data['status'])
        		$data_search['status'] = $this->request->data['status'];
        }
        
    	if ( !empty( $this->request->data['start_date'] ) )
        {
        	$cond['SubscriptionTransaction.created'] = $this->request->data['start_date'];
        	$this->set('start_date',$this->request->data['start_date']);
        	if ($this->request->data['start_date'])
        		$data_search['start_date'] = $this->request->data['start_date'];
        }
        
    	if ( !empty( $this->request->data['end_date'] ) )
        {
        	$cond['SubscriptionTransaction.created'] = $this->request->data['end_date'];
        	$this->set('end_date',$this->request->data['end_date']);
        	if ($this->request->data['end_date'])
        		$data_search['end_date'] = $this->request->data['end_date'];
        }
        
        $transactions = $this->Paginator->paginate('SubscriptionTransaction',$cond);
        $this->set('transactions', $transactions);
        
        $plans = $this->SubscriptionPackagePlan->find('all');
        $this->set('plans',$plans);
        
        $gateways = $this->Gateway->find('all');
        $this->set('gateways',$gateways);
        
        $this->set('data_search',$data_search);
    }
}
