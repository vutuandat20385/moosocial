<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class SubscriptionTransaction extends SubscriptionAppModel 
{
    public $belongsTo = array('User',
    	'Gateway' => array(
    		'className'=> 'PaymentGateway.Gateway',            
        	)       
    	);
    public $_has_find = true;
    public function afterFind($results, $primary = false)
    {
    	if ($results && $this->_has_find)
    	{
    		$planModel = MooCore::getInstance()->getModel("Subscription.SubscriptionPackagePlan");
    		$planModel->unbindModel(array('belongsTo'=>array('SubscriptionPackage')));
    		
    		$packageModel = MooCore::getInstance()->getModel("Subscription.SubscriptionPackage");
    		$packageModel->_has_plans = false;
    		
    		$subscribeModel = MooCore::getInstance()->getModel("Subscription.Subscribe");
    		$subscribeModel->_has_find= false;
    		
    		foreach ($results as &$result)
    		{
    			if (isset($result['SubscriptionTransaction']) && isset($result['SubscriptionTransaction']['package_id']) && isset($result['SubscriptionTransaction']['plan_id']) && isset($result['SubscriptionTransaction']['subscribes_id']))
    			{
    				$tmp = $planModel->findById($result['SubscriptionTransaction']['plan_id']);
    				if ($tmp)
    				{
    					$result['SubscriptionPackagePlan'] = $tmp['SubscriptionPackagePlan'];
    				}
    				
    				$tmp = $packageModel->findById($result['SubscriptionTransaction']['package_id']);
    				if ($tmp)
    				{
    					$result['SubscriptionPackage'] = $tmp['SubscriptionPackage'];
    				}
    				
    				$tmp = $subscribeModel->findById($result['SubscriptionTransaction']['subscribes_id']);
    				if ($tmp)
    				{
    					$result['Subscribe'] = $tmp['Subscribe'];
    				}
    			}
    		}
    		$planModel->bindModel(array('belongsTo'=>array('SubscriptionPackage')));
    		$packageModel->_has_plans = true;
    		$subscribeModel->_has_find= true;
    	}
    	return parent::afterFind($results,$primary);
    }
}
