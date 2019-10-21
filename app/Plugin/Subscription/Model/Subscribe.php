<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('SubscriptionAppModel','Subscription.Model');
class Subscribe extends SubscriptionAppModel 
{
	public $mooFields = array('plugin','type');
	
    public $validate = array(   
        'name' =>   array(   
            'notEmpty' => array(
                'rule'     => 'notBlank',
                'message'  => 'Name is required'
            ),
        ),     
        'role_id' => array(
            'rule' => array('comparison', '>', 0),
            'message'  => 'Please select user role'
        ),
        'price' => array(
            'rule' => array('comparison', '>=', 0),
            'message'  => 'Price is not valid'
        ),
        'recurring_price' => array(
            'rule' => array('comparison', '>=', 0),
            'message'  => 'Recurring price is not valid'
        ),
        'recurring' => array(
            'rule' => array('comparison', '>', 0),
            'message'  => 'Recurring must be a valid interger greater than 0'
        )
    );
    
    public $_has_find = true;
    
    public $belongsTo = array(
        'Gateway' => array(
        	'className'=> 'PaymentGateway.Gateway',
            'foreignKey' => 'gateway_id'
        ),'User');


	public function isIdExist($id)
    {
        return $this->hasAny(array('id' => (int)$id));
    }
    
    public function isBelongToPackage($package_id)
    {
        return $this->hasAny(array('package_id' => (int)$package_id));
    }
    public function isBelongToPlan($plan_id)
    {
        return $this->hasAny(array('plan_id' => (int)$plan_id));
    }
    
	public function afterSave($created, $options = array())
    {
    	Cache::clearGroup('subscription');
    	parent::afterSave($created, $options);
    }
    
    public function afterFind($results, $primary = false)
    {
    	if ($results && $this->_has_find)
    	{
    		$planModel = MooCore::getInstance()->getModel("Subscription.SubscriptionPackagePlan");
    		$planModel->unbindModel(array('belongsTo'=>array('SubscriptionPackage')));
    		
    		$packageModel = MooCore::getInstance()->getModel("Subscription.SubscriptionPackage");
    		$packageModel->_has_plans = false;
			
			$transactionModel = MooCore::getInstance()->getModel("Subscription.SubscriptionTransaction");
			$transactionModel->_has_find = false;
    		
    		foreach ($results as &$result)
    		{
    			if (isset($result['Subscribe']) && isset($result['Subscribe']['package_id']) && isset($result['Subscribe']['plan_id']))
    			{
    				$tmp = $planModel->findById($result['Subscribe']['plan_id']);
    				if ($tmp)
    				{
    					$result['SubscriptionPackagePlan'] = $tmp['SubscriptionPackagePlan'];
    				}
    				
    				$tmp = $packageModel->findById($result['Subscribe']['package_id']);
    				if ($tmp)
    				{
    					$result['SubscriptionPackage'] = $tmp['SubscriptionPackage'];
    				}  
    			}
				
				if (isset($result['Subscribe']) && isset($result['Subscribe']['transaction_id']))
				{
					if ($result['Subscribe']['transaction_id'])
					{
						$tmp = $transactionModel->findById($result['Subscribe']['transaction_id']);
						if ($tmp)
						{
							$result['SubscriptionTransaction'] = $tmp['SubscriptionTransaction'];
						}
						else
						{
							$result['SubscriptionTransaction']  = null;
						}
						
					}
					else
					{
						$result['SubscriptionTransaction']  = null;
					}
				}
    		}
    		$planModel->bindModel(array('belongsTo'=>array('SubscriptionPackage')));
    		$packageModel->_has_plans = true;
			$transactionModel->_has_find = true;
    	}
    	return parent::afterFind($results,$primary);
    }
}
