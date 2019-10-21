<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class SubscriptionRefund extends SubscriptionAppModel 
{
    public $belongsTo = array('User',
    	'Subscribe' => array(
    		'className'=> 'Subscription.Subscribe', 
			'foreignKey' => 'subscribe_id'			
        	),
        'SubscriptionTransaction' => array(
        	'className'=> 'Subscription.SubscriptionTransaction',
			'foreignKey' => 'transaction_id'
        	)
    	);
    	
    	public $order = 'SubscriptionRefund.id desc';
    	
	
    	public function afterFind($results, $primary = false)
    	{
    		if ($results)
    		{
    			$planModel = MooCore::getInstance()->getModel("Subscription.SubscriptionPackagePlan");
    			$planModel->unbindModel(array('belongsTo'=>array('SubscriptionPackage')));
    			
    			foreach ($results as &$result)
    			{
    				if (isset($result['SubscriptionRefund']) && isset($result['SubscriptionRefund']['plan_id']))
    				{
    					$tmp = $planModel->findById($result['SubscriptionRefund']['plan_id']);
    					if ($tmp)
    					{
    						$result['SubscriptionPackagePlan'] = $tmp['SubscriptionPackagePlan'];
    					}
    				}
    			}
    			$planModel->bindModel(array('belongsTo'=>array('SubscriptionPackage')));
    		}
    		return parent::afterFind($results,$primary);
    	}
    
}
