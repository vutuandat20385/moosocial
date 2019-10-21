<?php 
App::uses('AppController', 'Controller');
class StorageAppController extends AppController{
    public function __construct($request = null, $response = null)
    {
        parent::__construct($request, $response);
        $this->loadModel('Setting');
        $this->loadModel('SettingGroup');
    }
    public function beforeFilter() {
        parent::beforeFilter();
    }
    public function update_plugin_info_xml($group_id)
    {
        $setting_group = $this->SettingGroup->findById($group_id);
        $settings = $this->Setting->find('all', array('conditions' => array('group_id' => $group_id)));
        $xmlPath = APP . 'Plugin' . DS . $setting_group['SettingGroup']['module_id'] . DS . 'info.xml';
        if(file_exists($xmlPath))
        {
            $content = file_get_contents($xmlPath);
            $xml = new SimpleXMLElement($content);
            $xml->settings = '';
            $xmlSettings = $xml->settings;
            foreach($settings as $setting)
            {
                $setting = $setting['Setting'];
                $values = json_decode($setting['value_actual'], true);
                $xmlSetting = $xmlSettings->addChild('setting');
                $xmlSetting->label = $setting['label'];
                $xmlSetting->name = $setting['name'];
                $xmlSetting->description = $setting['description'];
                $xmlSetting->type = $setting['type_id'];
                if(!is_array($values))
                {
                    $xmlSetting->values = $setting['value_actual'];
                }
                else
                {
                    $xmlValues = $xmlSetting->addChild('values');
                    foreach($values as $value)
                    {
                        $xmlValue = $xmlValues->addChild('value');
                        $xmlValue->name = $value['name'];
                        $xmlValue->value = $value['value'];
                        $xmlValue->select = $value['select'];
                    }
                }
            }
            $xml->saveXML($xmlPath);
        }
    }
    public function active_service($type){
        $option = $this->Setting->findByName('storage_current_type');
        if($option){
            $option["Setting"]["value_actual"] = $type;
            $this->Setting->save($option);
            $this->update_plugin_info_xml($option["Setting"]["group_id"]);
        }
    }
}