<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('ModelBehavior', 'Model');

class ActivityBehavior extends ModelBehavior
{
	public $_default_privacy = array(
		'1' => PRIVACY_EVERYONE,
		'2' => PRIVACY_FRIENDS,
		'3' => PRIVACY_ME
	);
    /**
     * Used for runtime configuration of model
     *
     * @var array
     */
    public $runtime = array();

    /**
     * Stores the joinTable object for generating joins.
     *
     * @var object
     */
    protected $_joinTable;

    /**
     * Stores the runtime model for generating joins.
     *
     * @var Model
     */
    protected $_runtimeModel;

    public function setup(Model $Model, $settings = array())
    {
        $this->settings[$Model->alias] = (array)$settings;
    }

    public function activityModel()
    {
    	if (!$this->_runtimeModel)
       		$this->_runtimeModel = ClassRegistry::init('Activity');
        return $this->_runtimeModel;
    }

    public function afterSave(Model $Model, $created, $options = array())
    {
        extract($this->settings[$Model->alias]);
        if (!isset($this->settings[$Model->alias]['privacy_field']))
		{
			$privacy_field = 'privacy';
		}
		$default_privacy = $this->_default_privacy;
    	if (isset($this->settings[$Model->alias]['default_privacy']))
		{
			$default_privacy = $this->settings[$Model->alias]['default_privacy'];
		}		
		$privacy = 0;
		if (isset($Model->data[$Model->alias][$privacy_field]))
        	$privacy = $default_privacy[$Model->data[$Model->alias][$privacy_field]];
        
        if (isset($this->settings[$Model->alias]['parent_field']))
        {
        	if (isset($Model->data[$Model->alias][$this->settings[$Model->alias]['parent_field']]) && $Model->data[$Model->alias][$this->settings[$Model->alias]['parent_field']])
        	{
        		return ;
        	}
        }
        
        if ($created) {
            $RuntimeModel = $this->activityModel();
            
            $RuntimeModel->save(array('type' => $type,
				'action' => $action_afterCreated,
				'user_id' => $Model->data[$Model->alias]['user_id'],
				'item_type' => $item_type,
				'item_id' => $Model->data[$Model->alias]['id'],
				'query' => $query,
				'privacy' => $privacy,
				'params' => $params,
            	'plugin' => $Model->plugin,
			));
		}
		else
		{			
			$id = $Model->id;
			$id = (!$id ? $Model->data[$Model->alias]['id'] : $id);
			if (!$id)
				return;
				
			if (!isset($Model->data[$Model->alias][$privacy_field]))
				return;
							
			$RuntimeModel = $this->activityModel();
			$RuntimeModel->updateAll(array('privacy'=>$privacy),array('action'=>$action_afterCreated,'item_type'=>$item_type,'item_id'=>$id));
		}
    }
    
    public function afterDelete(Model $Model)
    {
    	if(!$Model->id){           
            return false;
        }
        
        extract($this->settings[$Model->alias]);
        $RuntimeModel = $this->activityModel();
        $RuntimeModel->deleteAll(array('Activity.action'=>$action_afterCreated,'Activity.item_type'=>$item_type,'Activity.item_id'=>$Model->id),true,true);
        $RuntimeModel->deleteAll(array('Activity.target_id' => $Model->id, 'Activity.type' => $item_type), true, true);
    }
}