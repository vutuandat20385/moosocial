<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class Setting extends AppModel {	
    
    public $validate = array(   
        'label' =>   array(   
            'rule' => 'notBlank',
            'message' => 'Label is required'
        ),
        'name' =>   array(   
            'notEmpty' => array(
                'rule'     => 'notBlank',
                'message'  => 'Name is required'
            ),
            'characters' => array(
                'rule'     => array('custom', '/^[a-z0-9_]*$/i'),
                'message'  => 'Name only contains letters, numbers and the underscore (_) with no space'
            ),
        ),                                           
    );
    
    public $belongsTo = array( 'SettingGroup' =>array('className' => 'SettingGroup', 'foreignKey' => 'group_id'));

    public function viewSettingType()
    {
        $result = array('text' => 'Input Text', 'radio' => 'Input Radio', 'checkbox' => 'Input Checkbox', 
                       'select' => 'Select', 'textarea' => 'Textarea', 'timezone' => 'Timezone', 'language' => 'Language');
        return $result;
    }
    
    public function isSettingTypeExist($type)
    {
        return array_key_exists($type, $this->viewSettingType());
    }
    
	public function isSettingNameExist($name, $except_id = null)
    {
        $cond = array('name' => $name);
        if((int)$except_id > 0)
        {
            $cond['id !='] = $except_id;
        }
        return $this->hasAny($cond);
    }
    
    public function isIdExist($id)
    {
        $cons = array('id' => $id);
        return $this->hasAny($cons);
    }
    
    public function generateOrdering($group_id)
    {
        $data = $this->find('first', array(
            'conditions' => array('group_id' => $group_id),
            'fields' => array('ordering'),
            'order' => 'ordering DESC'
        ));
        if($data != null)
        {
            return (int)$data['Setting']['ordering'] + 1;
        }
        return 1;
    }
    
    public function isGroupHasSettings($id)
    {
        return $this->hasAny(array('group_id' => $id));
    }
    
    public function afterSave($created, $options = array()){
        Cache::delete('site.settings');
        $this->save_boot_settings();
        Cache::clearGroup('blog');
    }
    
    public function updateAll($fields, $conditions = true){
        parent::updateAll($fields, $conditions);
        // Update cache
        Cache::delete("site.settings");
    }
    
    //app/config/settings.php
    public function save_boot_settings()
    {
        $this->SettingGroup = ClassRegistry::init('SettingGroup');
        Configure::load('settings', 'default');
        
        $settings = $this->find('all', array(
            'conditions' => array('is_boot' => 1)
        ));
        $setting_groups = $this->SettingGroup->find('all', array('group' => array('module_id')));
        $module_id = array();
        foreach($setting_groups as $setting_group)
        {
            $module_id[] = $setting_group['SettingGroup']['module_id'];
            Configure::delete($setting_group['SettingGroup']['module_id']);
        }
        
        $this->setConfig($settings);

        Configure::dump('settings.php', 'default', $module_id);
        
        $settings = $this->find('all', array(
        		'conditions' => array('is_boot' => 0)
        ));
        
        $this->setConfig($settings);
    }
    
    private function setConfig($settings)
    {
    	foreach($settings as $setting)
    	{
    		$setting_group = $this->SettingGroup->findById($setting['Setting']['group_id']);
    		$value = $setting['Setting']['value_actual'];
    		switch($setting['Setting']['type_id'])
    		{
    			case 'radio':
    			case 'select':
    				$value = '';
    				$multiValues = json_decode($setting['Setting']['value_actual'], true);
    				if($multiValues != null)
    				{
    					foreach($multiValues as $multiValue)
    					{
    						if($multiValue['select'] == 1)
    						{
    							$value = $multiValue['value'];
    						}
    					}
    				}
    				break;
    			case 'checkbox':
    				$value = '';
    				$multiValues = json_decode($setting['Setting']['value_actual'], true);
    				if($multiValues != null)
    				{
    					// Fixed [] operator not supported for strings on php7
    					$valueS = array();
    					foreach($multiValues as $multiValue)
    					{
    						if($multiValue['select'] == 1)
    						{
    							$valueS[] = $multiValue['value'];
    						}
    					}
    					if(is_array($valueS) && count($valueS) == 1)
    					{
    						$value = $valueS[0];
    					}
    				}
    				break;
    		}
    		Configure::write($setting_group['SettingGroup']['module_id'].'.'.$setting['Setting']['name'], $value);
    	}
    }
}
