<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
 
App::uses('Component', 'Controller');

class QuickSettingsComponent extends Component 
{
    public function run( &$controller, $module = array(), $id = null)
    {
        $this->Setting = ClassRegistry::init('Setting');
        $this->SettingGroup = ClassRegistry::init('SettingGroup');
        $setting_groups = $settings = null;
        
        if($module != null)
        {
            $module_id = array();
            foreach($module as $item)
            {
                $module_id[] = array("module_id" => $item);
            }
            
            //group setting
            $setting_groups = $this->SettingGroup->find('all', array(
                'conditions' => array(
                    'OR' => $module_id
                )
            ));
        }

        //settings
        $settingGuides = array();
        if((int)$id < 1)
        {
            $groupId = array();
            if($setting_groups != null)
            {
                foreach($setting_groups as $setting_group)
                {
                    $setting_group = $setting_group['SettingGroup'];
                    $groupId[] = $setting_group['id'];
                    $settingGuides[] = $this->getSettingGuide($setting_group['module_id']);
                }
                $settings = $this->Setting->find('all', array(
                    'conditions' => array('group_id' => $groupId),
                ));
            }
        }
        else 
        {
            $settings = $this->Setting->find('all', array(
                'conditions' => array('group_id' => $id),
            ));
            if($setting_groups != null)
            {
                foreach($setting_groups as $setting_group)
                {
                    $setting_group = $setting_group['SettingGroup'];
                    if($setting_group['id'] == $id)
                    {
                        $settingGuides[] = $this->getSettingGuide($setting_group['module_id']);
                        break;
                    }
                }
            }
        }
        
        $controller->set('setting_groups', $setting_groups);
        $controller->set('settings', $settings);
        $controller->set('settingGuides', $settingGuides);
        $controller->set('acive_group', $id);
    }
    
    public function install() {}
    
    public function upgrade() {}
    
    public function uninstall() {}
    
    private function getSettingGuide($key)
    {
        $settingGuide = '';
        $setupPath = sprintf(PLUGIN_FILE_PATH, $key, $key);
        if ( file_exists($setupPath) )
        {
            require_once($setupPath);
            $classname = $key.'Plugin';
            if(class_exists($classname))
            {
                $cl = new $classname();
                if(method_exists($classname, 'settingGuide'))
                {
                    $settingGuide = $cl->settingGuide();
                }
            }
        }
        return $settingGuide;
    }
} 