<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class SubscriptionPackagePlan extends SubscriptionAppModel
{
	public $actsAs = array(
		'Translate' => array(
			'title' => 'titleTranslation',
		)
	);
	
	private $_default_locale = 'eng' ;
	public $recursive = 1;
	
	function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->locale = Configure::read('Config.language');
	}

    public function afterFind($results, $primary = false)
    {
    	if ($results)
    	{
    		$packageModel = MooCore::getInstance()->getModel("Subscription.SubscriptionPackage");
    		$packageModel->_has_plans = false;
    		
    		foreach ($results as &$result)
    		{
    			if (isset($result['SubscriptionPackagePlan']) && isset($result['SubscriptionPackagePlan']['subscription_package_id']))
    			{    				
    				$tmp = $packageModel->findById($result['SubscriptionPackagePlan']['subscription_package_id']);
    				if ($tmp)
    				{
    					$result['SubscriptionPackage'] = $tmp['SubscriptionPackage'];
    				}
    			}
    		}
    		$packageModel->_has_plans = true;
    	}
    	return parent::afterFind($results,$primary);
    }
    
    public function beforeDelete($cascade = true)
    {
    	
    }
}
