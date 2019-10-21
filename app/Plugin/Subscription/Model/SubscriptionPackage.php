<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class SubscriptionPackage extends SubscriptionAppModel
{
	public $actsAs = array(
		'Translate' => array(
			'name' => 'nameTranslation',
			'description' => 'descriptionTranslation'
		)
	);
	
	private $_default_locale = 'eng' ;
	
	function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->locale = Configure::read('Config.language');
	}
	
    public $validate = array(   
        'name' =>   array(   
            'notEmpty' => array(
                'rule'     => 'notBlank',
                'message'  => 'Name is required'
            ),
        ), 
        'role_id' => array(
            'rule' => array('validateRole', 'role_id'),
            'message' => 'Select user role'
        ),
        'ordering' => array(
            'rule' => array('comparison', '>', 0),
            'message'  => 'Order must be a valid integer greater than 0'
        ),
    );
    public $belongsTo = 'Role';
    public $_has_plans = true;
    public $_condition_plans = null;
    
    public $recursive = 1;
    /*public $hasMany = array("SubscriptionPackagePlan"=> array(
        'conditions' => array('SubscriptionPackagePlan.deleted <> ' => 1),
        'dependent' => true,
    	'order' => 'SubscriptionPackagePlan.order ASC,SubscriptionPackagePlan.id ASC',
    	'className' => 'Subscription.SubscriptionPackagePlan'
    ));//PackagePricingPlan*/
    
    public function afterFind($results, $primary = false) 
    {
    	if ($results && $this->_has_plans)
    	{
    		$planModel = MooCore::getInstance()->getModel("Subscription.SubscriptionPackagePlan");
    		$planModel->unbindModel(array('belongsTo'=>array('SubscriptionPackage')));
    		$planModel->locale = $this->locale;
	    	foreach ($results as &$result)
	    	{
	    		if (isset($result['SubscriptionPackage']))
	    		{
	    			if (!$this->_condition_plans)
	    			{
			    		$result['SubscriptionPackagePlan'] = $planModel->find('all',array(
			    			'conditions'=>array(
			    				'SubscriptionPackagePlan.subscription_package_id' => $result['SubscriptionPackage']['id'],
			    				'SubscriptionPackagePlan.deleted <> ' => 1
			    			),
			    			'order' => 'SubscriptionPackagePlan.order ASC,SubscriptionPackagePlan.id ASC',
			    		));
	    			}
	    			else
	    			{
	    				$result['SubscriptionPackagePlan'] = $planModel->find('all',array(
	    						'conditions'=>array_merge(array('SubscriptionPackagePlan.subscription_package_id' => $result['SubscriptionPackage']['id']),$this->_condition_plans),
	    						'order' => 'SubscriptionPackagePlan.order ASC,SubscriptionPackagePlan.id ASC',
	    				));
	    			}
	    		}
	    	}
	    	$planModel->bindModel(array('belongsTo'=>array('SubscriptionPackage')));
    	}
    	return parent::afterFind($results,$primary);
    }
    
    function validateRole($id)
    {
        return $this->Role->hasAny(array('id' => $id));
    }
    
    public function generateOrdering()
    {
        $result = $this->find('first', array(
            'fields' => array('ordering'),
            'order' => 'ordering DESC'
        ));
        if($result != null)
        {
            return (int)$result['SubscriptionPackage']['ordering'] + 1;
        }
        return 1;
    }
    
    public function beforeDelete($cascade = true)
    {
    	
    }
}