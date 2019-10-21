<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class SubscriptionPackagesController extends SubscriptionAppController
{
    public $components = array('Paginator');
    public $paginate = array(
        'limit' => 10,
        'order' => array(
            'SubscriptionPackage.id' => 'DESC'
        ),
        'conditions' => array('SubscriptionPackage.deleted <>' => 1)
    );
    
    public function __construct($request = null, $response = null) 
    {
        parent::__construct($request, $response);
        $this->loadModel('Role');
        $this->loadModel('User');
        $this->loadModel('Subscription.Subscribe');
        $this->loadModel('Subscription.SubscriptionMembership');
        $this->loadModel('Subscription.SubscriptionPackage');
        $this->loadModel('Subscription.SubscriptionCompare');        
        $this->loadModel('Billing.Gateway');        
        $this->loadModel('Subscription.SubscriptionTransaction');
        
        $this->url = '/admin/subscription/subscription_packages/';
        $this->url_create = $this->url.'create/';
        $this->url_delete = $this->url.'delete/';
        $this->url_subscribes = '/admin/subscription/subscribes/index/';
        $this->url_gateway = '/admin/payment_gateway/manages/';
        $this->set('url', $this->url);
        $this->set('url_create', $this->url_create);
        $this->set('url_delete', $this->url_delete);
        $this->set('url_subscribes', $this->url_subscribes);
    }
    
    public function beforeFilter()
	{
		parent::beforeFilter();
        $this->_checkPermission(array('super_admin' => 1));
	}
    
    public function admin_index()
    {
        $this->Paginator->settings = $this->paginate;
        $packages = $this->Paginator->paginate('SubscriptionPackage');
        foreach($packages as $k => $v)
        {
            $packages[$k]['SubscriptionPackage']['active_member'] = $this->Subscribe->find('count', array(
                'conditions' => array('Subscribe.package_id' => $v['SubscriptionPackage']['id'], 'Subscribe.active' => 1)
            ));
            $packages[$k]['SubscriptionPackage']['members_quantity'] = $this->Subscribe->find('count', array(
                'conditions' => array('Subscribe.package_id' => $v['SubscriptionPackage']['id'])
            ));
        }
        $currency = Configure::read('Config.currency');
        $this->set('currency',$currency);
        $this->set('packages', $packages);
    }
    
    public function admin_create($id = null, $language = null)
    {
        if(!$this->Gateway->hasAny(array('enabled' => 1)))
        {
            $this->Session->setFlash(__( 'You must enabled at least one gateway'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
            $this->redirect($this->url_gateway.'index/');
        }
        else if((int)$id > 0 && !$this->SubscriptionPackage->hasAny(array('SubscriptionPackage.id' => (int)$id)))
        {
            $this->Session->setFlash(__( 'This package does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
            $this->redirect($this->referer());
        }
        else 
        {
            if((int)$id > 0)
            {
            	$this->loadModel("Language");
            	$langs = $this->Language->find( 'all' );
            	
            	if (!$language)
            	{
            		foreach ($langs as $lang)
            		{
            			$language = $lang['Language']['key'];
            			break;
            		}
            	}
            	
            	$tmp = array();
            	foreach ($langs as $lang)
            	{
            		$tmp[$lang['Language']['key']] = $lang['Language']['name'];
            	}
            	
            	$this->set('languages', $tmp);
            	$this->set('language', $language);
            	$this->SubscriptionPackage->locale = $language;
            	
                $package = $this->SubscriptionPackage->findById($id);
                if($this->Subscribe->hasAny(array("package_id" => $id)))
                {
                    $disable = true;
                }
                else 
                {
                    $disable = false;
                }
                $tmp = array();
                foreach ($package['SubscriptionPackagePlan'] as $plan)
                {
                	$tmp[] = $plan['SubscriptionPackagePlan'];
                }
                $this->set('pricingPlan', $tmp);
            }
            else
            {
                $disable = false;
                $package = $this->SubscriptionPackage->initFields();
            }

            //group
            $groups = $this->Role->find('all', array(
                'fields' => array('id', 'name'),
                'conditions' => array('is_super' => 0)
            ));
            $cbGroup = array();
            if($groups != null)
            {
                foreach($groups as $group)
                {
                    $cbGroup[$group['Role']['id']] = $group['Role']['name'];
                }
            }
            
            //default currency
            $currency = Configure::read('Config.currency');
            $this->set('package', $package['SubscriptionPackage']);
            $this->set('cbGroup', $cbGroup);
            $this->set('currency', $currency);
            //$this->set('periodType', $this->SubscriptionPackage->periodType());
            //$this->set('periodType2', $this->SubscriptionPackage->periodType2());
            $this->set('disable', $disable);
        }
    }

    public function admin_save()
    {
        if ($this->request->is('post')) 
        {
        	$this->loadModel("Language");
        	$langs = $this->Language->getLanguages();
            if($this->request->data['SubscriptionPackage']['id'] > 0)
            {
                $this->url_create .= $this->request->data['SubscriptionPackage']['id'];
            }

            //validate package
            $this->SubscriptionPackage->set($this->request->data);
            if (!$this->SubscriptionPackage->validates())
            {
                $errors = $this->SubscriptionPackage->validationErrors;
                $this->Session->setFlash(current(current($errors)), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                
                $this->redirect($this->url_create);
            }
            
            //valid role , not allow super admin
            if(!$this->request->data['SubscriptionPackage']['id'])
            {
	            $role = $this->Role->find('all', array(
	                'fields' => array('id', 'name'),
	                'conditions' => array('id' => $this->request->data['SubscriptionPackage']['role_id'], 'is_super' => 1)
	            ));
	            if($role != null)
	            {
	                $this->Session->setFlash(__( 'You cannot select this role.'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
	                $this->redirect($this->url_create);
	            }
            }

            //save data
            $this->SubscriptionPackage->id = $this->request->data['SubscriptionPackage']['id'];

            if((int)$this->request->data['SubscriptionPackage']['id'] > 0)
            {
            	$this->SubscriptionPackage->locale = $this->request->data['SubscriptionPackage']['language'];
                unset($this->request->data['SubscriptionPackage']['role_id']);
            }
            else

            {
                $this->request->data['SubscriptionPackage']['ordering'] = $this->SubscriptionPackage->generateOrdering();
            }
            //only allow 1 package is default
            if(isset($this->request->data['SubscriptionPackage']['default']) && $this->request->data['SubscriptionPackage']['default'] == 1)
            {
                $this->SubscriptionPackage->updateAll(array('default' => 0));
            }
            
            if ($this->SubscriptionPackage->save($this->request->data))
            {
            	if((int)$this->request->data['SubscriptionPackage']['id'] == 0)
            	{
	            	foreach (array_keys($langs) as $lKey) {
	            		$this->SubscriptionPackage->locale = $lKey;
	            		$this->SubscriptionPackage->saveField('name', $this->request->data['SubscriptionPackage']['name']);
	            		$this->SubscriptionPackage->saveField('description', $this->request->data['SubscriptionPackage']['description']);
	            	}
            	}
            	
                $this->Session->setFlash(__( 'Changes saved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                $package_id = $this->SubscriptionPackage->id;
                //save pricing plan;
                $this->loadModel('Subscription.SubscriptionPackagePlan');
                if(!empty($this->request->data['SubscriptionPackage']['delete_plan_id']))
                {
                    $this->_delete_plan($package_id,$this->request->data['SubscriptionPackage']['delete_plan_id']);
                }
                if(!empty($this->request->data['PricingPlan'])){                
                    foreach($this->request->data['PricingPlan'] as $key => $value)
                    {
                    	if (isset($value['id']) && $value['id'])
                    	{
                    		if(!empty($value['show_at']))
	                            $value['show_at'] = implode(',',$value['show_at']);
	                        else
	                            unset($value['show_at']);
	                        
	                        if (!isset($value['enable_plan']))
	                        	$value['enable_plan'] = 0;
	                        
	                        $this->SubscriptionPackagePlan->id = $value['id'];
	                        $this->SubscriptionPackagePlan->locale = $this->request->data['SubscriptionPackage']['language'];
	                        $this->SubscriptionPackagePlan->save($value);	                        
                    	}
                    	else
                    	{
	                        $this->SubscriptionPackagePlan->create();
	                        $value['subscription_package_id'] = $package_id;
	
	                        if(!empty($value['show_at']))
	                            $value['show_at'] = implode(',',$value['show_at']);
	                        else
	                            unset($value['show_at']);
	                        if(!empty($value['type'])){// not an update
	                        	if (!$value['price'])
	                        		$value['price'] = 0;
	                        	if (!$value['plan_duration'])
	                        		$value['plan_duration'] = 0;
	                        	if (!$value['expiration_reminder'])
	                        		$value['expiration_reminder'] = 0;
	                        	
	                            $this->SubscriptionPackagePlan->set(array('SubscriptionPackagePlan' => $value));
	                            if (!$this->SubscriptionPackagePlan->validates())
	                            {
	                                $errors = $this->SubscriptionPackagePlan->validationErrors;
	                                $this->Session->setFlash(current(current($errors)), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
	
	                                $this->redirect($this->url_create);
	                            }
	                            
	                            $this->SubscriptionPackagePlan->save();
	                            
	                            foreach (array_keys($langs) as $lKey) {
	                            	$this->SubscriptionPackagePlan->locale = $lKey;
	                            	$this->SubscriptionPackagePlan->saveField('title', $value['title']);
	                            }
	                        }
                    	}
                    }
                }

                return $this->redirect($this->url);
            }

            $this->Session->setFlash(__( 'Something went wrong! Please try again.'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
            return $this->redirect($this->url_create);
        }
        else 
        {
            return $this->redirect($this->url_create);
        }
    }
    
    public function admin_do_enable($id)
    {
        $this->do_active($id, 1, 'enabled');
    }
    
    public function admin_do_disable($id)
    {
        $this->do_active($id, 0, 'enabled');
    }
    
    public function admin_do_signup($id)
    {
        $this->do_active($id, 1, 'signup');
    }
    
    public function admin_do_unsignup($id)
    {
        $this->do_active($id, 0, 'signup');
    }
    
    public function admin_do_default($id)
    {
        $this->do_default($id);
    }
    
    private function do_active($id, $value = 1, $task)
    {
        if(!$this->SubscriptionPackage->hasAny(array('SubscriptionPackage.id' => (int)$id)))
        {
            $this->Session->setFlash(__( 'This package does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $this->SubscriptionPackage->id = $id;
            $this->SubscriptionPackage->save(array($task => $value));

            $this->Session->setFlash(__( 'Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        $this->redirect($this->referer());
    }
    
    private function do_default($id)
    {
        if(!$this->SubscriptionPackage->hasAny(array('SubscriptionPackage.id' => (int)$id)))
        {
            $this->Session->setFlash(__( 'This package does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $this->SubscriptionPackage->updateAll(array('default' => 0));
            $this->SubscriptionPackage->id = $id;
            $this->SubscriptionPackage->save(array('default' => 1));
            //$this->Session->setFlash(__( 'Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        //$this->redirect($this->referer());
    }
    
    public function admin_delete($id)
    {
        if(!$this->SubscriptionPackage->hasAny(array('SubscriptionPackage.id' => (int)$id)))
        {
            $this->Session->setFlash(__( 'This package does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
            $this->redirect($this->url);
        }

        elseif($this->Subscribe->isBelongToPackage($id))//SubscriptionMembership
        {
            $this->SubscriptionPackage->id = $id;
            $this->SubscriptionPackage->save(array('deleted' => 1));
            $this->Session->setFlash(__( 'Successfully deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->url);
        }
        else
        {
            $this->SubscriptionPackage->delete($id);
            $this->Session->setFlash(__( 'Successfully deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->url);
        }
    }
    public function admin_pricing_plan(){
        $currency = Configure::read('Config.currency');
        $this->set('currency', $currency);
    }
    public function admin_save_change()
    {
        $this->loadModel('SubscriptionPackagePlan');
        if($this->request->is('post'))
        {
            if(!empty($this->request->data['Package'])){
                $packages = $this->request->data['Package'];
                foreach($packages as $package_id => $package)
                {
                    if(!empty($package['default']))
                        $this->do_default($package_id);
                    $this->SubscriptionPackage->id = $package_id;
                    $this->SubscriptionPackage->save(array('recommended' => $package['recommended'], 'ordering' => $package['ordering']));
                    if(!empty($package['Plan'])){
                        $plans = $package['Plan'];
                        foreach($plans as $plan_id => $plan){
                            $this->SubscriptionPackagePlan->id = $plan_id;
                            $plan['show_at'] = implode(',',$plan['show_at']);
                            $this->SubscriptionPackagePlan->save($plan);
                        }
                    }
                }
                return $this->redirect($this->referer());
            }
            else
                return $this->redirect($this->referer());
        }
        else
            return $this->redirect($this->referer());
    }
    private function _delete_plan($id,$plan_ids){
        $this->loadModel('SubscriptionPackagePlan');
        if ($plan_ids)
        {
	        $plan_ids = explode(',',$plan_ids);
	        foreach ($plan_ids as $id)
	        {
	        	$count = $this->Subscribe->find('count',array(
	        		'conditions' => array('Subscribe.plan_id'=>$id)
	        	));
	        	if ($count)
	        	{
	        		$this->SubscriptionPackagePlan->id = $id;
	        		$this->SubscriptionPackagePlan->save(array('deleted'=>1));
	        	}
	        	else
	        	{
	        		$this->SubscriptionPackagePlan->delete($id);
	        	}
	        }
        }
    }
    public function preview(){
    	$this->layout = 'simple';
        $currency = Configure::read('Config.currency');
        $helper = MooCore::getInstance()->getHelper('Subscription_Subscription');
        list($columns,$compares) = $helper->getPackageSelect(1);        
        $this->set('columns_login', $columns);
        $this->set('compares_login', $compares);
        
        list($columns,$compares) = $helper->getPackageSelect(2);
        $this->set('columns_update', $columns);
        $this->set('compares_update', $compares);
        
        $this->set('currency',$currency);
    }
}