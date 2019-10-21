<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class ManagesController extends PaymentGatewayAppController 
{
    public $components = array('Paginator');

    public function __construct($request = null, $response = null) 
    {
        parent::__construct($request, $response);
        
        
        $this->url = '/admin/payment_gateway/manages/';
        $this->url_create = $this->url.'create/';
        $this->url_delete = $this->url.'delete/';        
        $this->set('url', $this->url);
        $this->set('url_create', $this->url_create);
        $this->set('url_delete', $this->url_delete);
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
            'limit' => 10,
            'order' => array(
                'id' => 'DESC'
            )
        );
        $gateways = $this->Paginator->paginate('Gateway');
        $this->set('gateways', $gateways);
    }
    
    public function admin_create($id)
    {
        if(!$this->Gateway->hasAny(array('id' => (int)$id)))
        {
            $this->Session->setFlash(__('This gateway does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
            $this->redirect($this->url);
        }
        else 
        {
            $gateway = $this->Gateway->findById($id);
            $this->set('gateway', $gateway['Gateway']);            
            $this->set('extra_params_value',json_decode($gateway['Gateway']['config'],true));
        }

    }

    public function admin_save()
    {      
        $errors = array();
    	if(!$this->Gateway->hasAny(array('id' => (int)$this->request->data['Gateway']['id'])))
        {
			$errors[] = __('This gateway does not exist.');
        }
        
        //validate
    	$this->Gateway->set($this->request->data);
		if (!$this->Gateway->validates())
		{
			$tmp = $this->Gateway->validationErrors;
			foreach ($tmp as $item)
			{
				$errors[] = current($item);
			}
		}
		
		//validate plugin
		$messages = array();
		$event = new CakeEvent('Plugin.PaymentGateway.Managers.save_validate', $this);
		$this->getEventManager()->dispatch($event);
		if(!empty($event->result['messages']))
            $messages = $event->result['messages'];
        
        $errors = array_merge($errors,$messages);
        
        $response['result'] = 0;
        $response['message'] = current($errors);
        if (!count($errors))
        {
	        //save data
            $this->Gateway->id = $this->request->data['Gateway']['id'];
            if ($this->Gateway->save($this->request->data)) 
            {
                $this->Session->setFlash(__('Successfully saved.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                $response['result'] = 1;                
            }
        	
        }

        echo json_encode($response);
        exit;
    }
    
    public function admin_do_enable($id)
    {
        $this->do_active($id, 1, 'enabled');
    }
    
    public function admin_do_disable($id)
    {
        $this->do_active($id, 0, 'enabled');
    }

    public function admin_do_enabletest($id)
    {
        $this->do_active($id, 1, 'test_mode');
    }
    
    public function admin_do_disabletest($id)
    {
        $this->do_active($id, 0, 'test_mode');
    }
    
    public function admin_do_enable_ipn($id)
    {
        $this->do_active($id, 1, 'ipn_log');
    }
    
    public function admin_do_disable_ipn($id)
    {
        $this->do_active($id, 0, 'ipn_log');
    }
    
    private function do_active($id, $value = 1, $task)
    {
        if(!$this->Gateway->hasAny(array('id' => (int)$id)))
        {
            $this->Session->setFlash(__('This gateway does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $this->Gateway->id = $id;
            $this->Gateway->save(array($task => $value));
            $this->Session->setFlash(__('Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        $this->redirect($this->referer());
    }
}
