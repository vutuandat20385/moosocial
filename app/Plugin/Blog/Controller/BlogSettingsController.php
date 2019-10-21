<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class BlogSettingsController extends BlogAppController{
    public $components = array('QuickSettings');
    public function beforeFilter(){
        parent::beforeFilter();
        $this->loadModel('Setting');
        $this->loadModel('SettingGroup');
    }
    public function admin_index()
    {
        $blog_enabled = Configure::read('Blog.blog_enabled');
        $this->set('title_for_layout', __('Blogs Setting'));
        if($blog_enabled == 0)
        {
            $this->loadModel('Menu.CoreMenuItem');
            //$this->loadModel('CoreMenu');
            $blogs_menu = $this->CoreMenuItem->find('first',array(
                'conditions'=>array('url'=>'/blogs','type'=>'page')
            ));
            if ($blogs_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $blogs_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 0));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Blogs',
                    'url' => '/blogs',
                    'is_active' => 0,
                    'menu_id' => 1,
                    'type' => 'page',
                    'menu_order' => 999
                ));
                $this->CoreMenuItem->save();
            }
        }
        elseif($blog_enabled == 1)
        {
            $this->loadModel('Menu.CoreMenuItem');
            $blogs_menu = $this->CoreMenuItem->find('first',array(
                'conditions'=>array('url'=>'/blogs','type'=>'page')
            ));
            if ($blogs_menu['CoreMenuItem']['id']) {
                $this->CoreMenuItem->id = $blogs_menu['CoreMenuItem']['id'];
                $this->CoreMenuItem->save(array('is_active' => 1));
            } else {
                $this->CoreMenuItem->set(array(
                    'name' => 'Blogs',
                    'url' => '/blogs',
                    'is_active' => 1,
                    'menu_id' => 1,
                    'type' => 'page',
                    'menu_order' => 999
                ));
                $this->CoreMenuItem->save();
            }
        }
        
        // clear cache menu
        Cache::clearGroup('menu', 'menu');
        $this->QuickSettings->run($this, array("Blog"));

    }
    public function admin_save(){

        if ($this->request->is('post'))
        {
            $this->autoRender = false;
            //save data

            foreach($this->request->data['setting_id'] as $key => $item)
            {
                $item = intval($item);
                
                switch($this->request->data['type_id'][$key])
                {
                    case 'text':
                        $values['value_actual'] = $this->request->data['text'][$item];
                        break;
                    case 'textarea':
                        $values['value_actual'] = $this->request->data['textarea'][$item];
                        break;
                    case 'radio':
                    case 'select':
                        $setting = $this->Setting->findById($item);
                        $multiValue = json_decode($setting['Setting']['value_actual'], true);
                        foreach($multiValue as $k => $multi)
                        {
                            if($multi['value'] == $this->request->data['multi'][$item])
                            {
                                $multiValue[$k]['select'] = 1;
                            }
                            else
                            {
                                $multiValue[$k]['select'] = 0;
                            }
                        }
                        $values['value_actual'] = json_encode($multiValue);
                        break;
                    case 'checkbox':
                        $setting = $this->Setting->findById($item);
                        $multiValue = json_decode($setting['Setting']['value_actual'], true);
                        foreach($multiValue as $k => $multi)
                        {
                            $multiValue[$k]['select'] = $this->request->data['multi'][$item][$multi['value']];
                        }
                        $values['value_actual'] = json_encode($multiValue);
                        break;
                }
                $this->Setting->id = $item;
                $this->Setting->save($values);
            }
            $setting = $this->Setting->findById($this->request->data['setting_id'][0]);
            $this->update_plugin_info_xml($setting['Setting']['group_id']);
            $this->save_setting_file($setting['Setting']['group_id']);

            $this->Session->setFlash(__('Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect( $this->referer() );
        }
        else
        {
            $this->redirect($this->referer());
        }
    }

    private function save_setting_file($group_id)
    {
        Configure::load('settings', 'default');

        $settings = $this->Setting->find('all', array(
            'conditions' => array('group_id' => $group_id)
        ));
        $setting_groups = $this->SettingGroup->find('all', array('conditions' => array('id' => $group_id)));
        $module_id = array();
        foreach($setting_groups as $setting_group)
        {
            $module_id[] = $setting_group['SettingGroup']['module_id'];
            Configure::delete($setting_group['SettingGroup']['module_id']);
        }
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
                        foreach($multiValues as $multiValue)
                        {
                            if($multiValue['select'] == 1)
                            {
                                $value[] = $multiValue['value'];
                            }
                        }
                        if(is_array($value) && count($value) == 1)
                        {
                            $value = $value[0];
                        }
                    }
                    break;
            }
            Configure::write($setting_group['SettingGroup']['module_id'].'.'.$setting['Setting']['name'], $value);
        }
        Configure::dump('settings.php', 'default', $module_id);
    }

    private function update_plugin_info_xml($group_id)
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
}