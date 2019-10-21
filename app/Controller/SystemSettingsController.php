<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class SystemSettingsController extends AppController 
{
    public function __construct($request = null, $response = null) 
    {
        parent::__construct($request, $response);
        $this->loadModel('Setting');
        $this->loadModel('SettingGroup');
        $this->loadModel('Plugin');
        $this->loadModel('Language');
        (Configure::read('core.production_mode') == 1 || Configure::read('core.production_mode') == 2) ? $this->allow_modify = true :  $this->allow_modify = false;

        $this->url = '/admin/system_settings/';
        $this->url_create = $this->url.'create/';
        $this->url_delete = $this->url.'delete/';
        $this->url_view = $this->url.'view/';
        $this->set('url', $this->url);
        $this->set('url_create', $this->url_create);
        $this->set('url_delete', $this->url_delete);
        $this->set('url_view', $this->url_view);
        $this->set('allow_modify', $this->allow_modify);
    }
    
    public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_checkPermission(array('super_admin' => 1));
	}
    
    public function admin_view($id = null)
    {
        if((int)$id < 1)
        {
            $curGroup = $this->SettingGroup->find('first');
            $id = $curGroup['SettingGroup']['id'];
        }
        else if(!$this->SettingGroup->hasAny(array('id' => $id)))
        {
            $this->redirect($this->url_view);
        }
        else 
        {
            $curGroup = $this->SettingGroup->findById($id);
        }
        $activeId = $id;
        if($curGroup['SettingGroup']['parent_id'] > 0)
        {
            $activeId = $curGroup['SettingGroup']['parent_id'];
        }
        
        //group setting
        $setting_groups = $this->SettingGroup->find('all', array(
            'conditions' => array('parent_id' => 0, 'module_id' => 'core')
        ));

        foreach($setting_groups as $key => $setting_group)
            //remove custom block tab
            if($setting_group['SettingGroup']['id'] == 4 && $setting_group['SettingGroup']['name'] == 'Custom Blocks')
                unset($setting_groups[$key]);

        //child group setting
        $child_groups = $this->SettingGroup->find('all', array(
            'conditions' => array('parent_id' => $activeId)
        ));
        
        //settings
        $settings = $this->Setting->find('all', array(
            'conditions' => array('group_id' => $id),
            'order' => array('ordering ASC')
        ));

        //setting guide
        $settingGuide = '';
        $key = $curGroup['SettingGroup']['module_id'];
        $setupPath = APP . 'Plugin' . DS . $key . DS . 'plugin.php';
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

        if($id == 2) //feature setting
        {
            $auto_add_friend = Configure::read('core.auto_add_friend');
            if(!empty($auto_add_friend))
            {
                $this->loadModel('User');
                $friends = $this->User->getUsers(1,array('FIND_IN_SET(User.id,\''.$auto_add_friend.'\')'));
                $friend_options = array();
                foreach ($friends as $friend)
                    $friend_options[] = array( 'id' => $friend['User']['id'], 'name' => $friend['User']['name'], 'avatar' => $friend['User']['avatar'] );
                $this->set('friends',json_encode( $friend_options ));
            }
            else
                $this->set('friends',null);
            foreach ($settings as $setting)
            {
                if($setting['Setting']['name'] == 'auto_add_friend')
                    $id_auto_add_friend = 'text'.$setting['Setting']['id'];
                
                if($setting['Setting']['name'] == 'link_after_login')
                	$link_after_login = 'text'.$setting['Setting']['id'];
            }
            $this->set('id_auto_add_friend',$id_auto_add_friend);
            $this->set('link_after_login',$link_after_login);
        }
		
        $this->set('curGroup',$curGroup);
        $this->set('settings', $settings);
        $this->set('active_setting', $activeId);
        $this->set('setting_groups', $setting_groups);
        $this->set('child_groups', $child_groups);
        $this->set('settingGuide', $settingGuide);
        $this->set('site_langs', $this->Language->getLanguages());
        $this->set('title_for_layout', __('System Settings'));
    }
    
    public function admin_create($id = null)
    {
        if(!in_array(Configure::read('core.production_mode'),array(1,2)))
        {
            $this->redirect($this->url_view);
        }
        $is_core = false;
        if((int)$id > 0 && $this->Setting->isIdExist($id))
        {
            if(!$this->allow_modify)
            {
                $this->redirect($this->url);
            }
            $setting = $this->Setting->findById($id);
            switch($setting['Setting']['type_id'])
            {
                case 'text':
                    $setting['Setting']['text'] = $setting['Setting']['value_actual'];
                    break;
                case 'text':
                    $setting['Setting']['textarea'] = $setting['Setting']['value_actual'];
                    break;
                case 'radio':
                case 'checkbox':
                case 'select':
                    $setting['Setting']['multi'] = json_decode($setting['Setting']['value_actual'], true);
                    break;
            }
            $setting_group = $this->SettingGroup->findById($setting['Setting']['group_id']);
            if($setting_group['SettingGroup']['group_type'] == 'core')
            {
                $is_core = true;
            }
        }
        else 
        {
            $setting = $this->Setting->initFields();
        }

        // get all installed plugins
        $plugins = $this->Plugin->find( 'all', array('order' => 'id DESC'));
        $cbPlugins = array('core' => 'Core');
        foreach($plugins as $plugin)
        {
            $cbPlugins[$plugin['Plugin']['key']] = $plugin['Plugin']['name'];
        }

        //setting group
        $setting_groups = $this->SettingGroup->find('threaded', array(
            'fields' => array('id', 'parent_id', 'name'),
        ));

        $cbSettingGroups = array();
        foreach($setting_groups as $setting_group)
        {
            if($setting_group['SettingGroup']['parent_id'] == 0)
            {
                $cbSettingGroups[$setting_group['SettingGroup']['id']] = $setting_group['SettingGroup']['name'];
                if($setting_group['children'] != null)
                {
                    foreach($setting_group['children'] as $setting_group_child)
                    {
                        $cbSettingGroups[$setting_group_child['SettingGroup']['id']] = '----'.$setting_group_child['SettingGroup']['name'];
                    }
                }
            }
        }

        $this->set('setting', $setting);
        $this->set('cbPlugins', $cbPlugins);
        $this->set('types', $this->Setting->viewSettingType());
        $this->set('cbSettingGroups', $cbSettingGroups);
        $this->set('site_langs', $this->Language->getLanguages());
        $this->set('is_core', $is_core);
    }

    public function admin_save()
    {
        if ($this->request->is('post')) 
        {
            if((int)$this->request->data['id'] > 0 && !$this->allow_modify)
            {
                $this->redirect($this->view);
            }
            else if((int)($this->request->data['group_id']) > 0 && !$this->SettingGroup->isIdExist($this->request->data['group_id']))
            {
                $this->Session->setFlash(__('Group does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect($this->url_create);
            }
            else if(!$this->Setting->isSettingTypeExist($this->request->data['type_id']))
            {
                $this->Session->setFlash(__('Type does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect($this->url_create);
            }
            else if(!empty($this->request->data['name']) && $this->Setting->isSettingNameExist($this->request->data['name'], $this->request->data['id']))
            {
                $this->Session->setFlash(__('This setting name already exists'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect($this->url_create);
            }
            else 
            {
                //validate
                $this->Setting->set($this->request->data);
                if (!$this->Setting->validates() )
                {
                    $errors = $this->Setting->validationErrors;
                    $this->Session->setFlash(current(current($errors)), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                    $this->redirect($this->url_create);
                }

                //value type
                switch($this->request->data['type_id'])
                {
                    case 'text':
                        $this->request->data['value_actual'] = $this->request->data['value_default'] = $this->request->data['text'];
                        break;
                    case 'textarea':
                        $this->request->data['value_actual'] = $this->request->data['value_default'] = $this->request->data['textarea'];
                        break;
                    case 'radio':
                        foreach($this->request->data['multi']['name'] as $k => $v)
                        {
                            $value[] = array('name' => $v,
                                             'value' => $this->request->data['multi']['value'][$k],
                                             'select' => $this->request->data['multi']['radio'][0] == $k ? 1 : 0);
                        }
                        $this->request->data['value_actual'] = $this->request->data['value_default'] = json_encode($value);
                        // save general settings
                        if($this->request->data['name'] == 'production_mode') {
                            $this->_saveGeneralSettings(array('production_mode' => $this->request->data['multi']['radio'][0]));
                        }
                        break;
                    case 'select':
                        foreach($this->request->data['multi']['name'] as $k => $v)
                        {
                            $sel = isset($this->request->data['multi']['radio'][$k]) ? $this->request->data['multi']['radio'][$k] : 0;
                            $value[] = array('name' => $v,
                                             'value' => $this->request->data['multi']['value'][$k],
                                             'select' => $sel);
                        }
                        $this->request->data['value_actual'] = $this->request->data['value_default'] = json_encode($value);
                        break;
                    case 'checkbox':
                        foreach($this->request->data['multi']['name'] as $k => $v)
                        {
                            $sel = isset($this->request->data['multi']['checkbox'][$k]) ? $this->request->data['multi']['checkbox'][$k] : 0;
                            $value[] = array('name' => $v,
                                             'value' => isset($this->request->data['multi']['value'][$k]) ? $this->request->data['multi']['value'][$k] : 0,
                                             'select' => $sel);
                        }
                        $this->request->data['value_actual'] = $this->request->data['value_default'] = json_encode($value);
                        break;
                    case 'timezone':
                        $this->request->data['value_actual'] = $this->request->data['value_default'] = $this->request->data['timezone'];
                        break;
                    case 'language':
                        $this->request->data['value_actual'] = $this->request->data['value_default'] = $this->request->data['language'];
                        break;
                }

                $this->Setting->id = $this->request->data['id'];
                if(empty($this->request->data['id']))
                {
                    $this->request->data['ordering'] = $this->Setting->generateOrdering($this->request->data['group_id']);
                }
                if ($this->Setting->save($this->request->data)) 
                {
                    $this->update_plugin_info_xml($this->request->data['group_id']);

                    $this->Session->setFlash(__('Successfully saved.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                    $this->redirect($this->url_view.$this->request->data['group_id']);
                }
                $this->Session->setFlash(__('Unable to add setting.'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect($this->url_create);
            }
        }
        else 
        {
            $this->redirect($this->url_create);
        }
    }
    
    public function admin_quick_save()
    {
        if ($this->request->is('post')) 
        {
            if (!empty($_FILES)){
                $this->saveLogo();
            }
            if(!empty( $this->request->data['setting_id']))
            {
                foreach($this->request->data['setting_id'] as $item)
                {
                    //$values['ordering'] = $this->request->data['ordering'][$item];
                    switch($this->request->data['type_id'][$item])
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
                            if($setting['Setting']['name'] == 'production_mode') {
                                $this->_saveGeneralSettings(array('production_mode' => $this->request->data['multi'][$setting['Setting']['id']]));
                            }
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
                        case 'timezone':
                            $values['value_actual'] = $this->request->data['timezone'][$item];
                            break;
                        case 'language':
                            $values['value_actual'] = $this->request->data['language'][$item];
                            break;
                    }

                    if(!is_writeable(APP.'Config'.DS.'settings.php') || !is_writeable(APP.'Config'.DS.'general.php'))
                    {
                        $this->Session->setFlash(__('Updates Failed. Unable to save due to file permissions, please check your file permissions for').'<br />"'.APP.'Config'.DS.'settings.php'.'"<br />"'.APP.'Config'.DS.'general.php'.'"', 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                        $this->redirect( $this->referer() );
                        //break;
                    }

                    $this->Setting->id = $item;
                    $this->Setting->save($values);
                }
                $setting = $this->Setting->findById($this->request->data['setting_id'][0]);
                $this->update_plugin_info_xml($setting['Setting']['group_id']);
            }



            if( isset($this->request->data["url_redirect"])){
                $this->redirect($this->request->data["url_redirect"]);
            }else{
                $this->Session->setFlash(__('Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                $this->redirect( $this->referer() );
            }

        }
        else 
        {
            $this->redirect($this->url_create);
        }
    }
    
    private function saveLogo()
    {
        App::uses('Sanitize', 'Utility');
        $curLogo = Configure::read('core.logo');
        
        /*// remove logo
        if ( !empty( $this->request->data['remove_logo'] ) )
        {
            if ($curLogo && file_exists(WWW_ROOT . $curLogo)){
                unlink(WWW_ROOT . $curLogo);
            }

            $this->Setting->updateAll( array( 'Setting.value_actual' => '""' ), array( 'Setting.name' => 'logo' ) );
        }*/
        
        if ( isset($_FILES['logo']) && is_uploaded_file($_FILES['logo']['tmp_name']) )
        {
            App::import('Vendor', 'secureFileUpload');
            $secureUpload = new SecureImageUpload(
                array(
                   'fileKeyName' =>  'logo',
                    'path'=>WWW_ROOT.'uploads' . DS,
                    'whitelist'=>array('extensions'=>array('jpg','jpeg','gif','png'),'type'=>array('image/png', 'image/jpeg', 'image/gif'),),
                    'maxSize' => 2*1024*1024, // 2Mb
                    'scaleUp'=>true,
                )
            );
            if($secureUpload->execute()){
                $this->Setting->updateAll( array( 'Setting.value_actual' => "'". 'uploads/'. $secureUpload->getFileName() ."'" ), array( 'Setting.name' => 'logo' ) );
            }else{
                $this->Session->setFlash(__($secureUpload->getMessage()), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect( $this->referer() );
            }
        }

        if ( isset($_FILES['og_image']) && is_uploaded_file($_FILES['og_image']['tmp_name']) )
        {
            App::import('Vendor', 'secureFileUpload');
            $secureUpload = new SecureImageUpload(
                array(
                    'fileKeyName' =>  'og_image',
                    'path'=>WWW_ROOT.'uploads' . DS,
                    'whitelist'=>array('extensions'=>array('jpg','jpeg','gif','png'),'type'=>array('image/png', 'image/jpeg', 'image/gif'),),
                    'maxSize' => 2*1024*1024, // 2Mb
                    'scaleUp'=>true,
                )
            );
            if($secureUpload->execute()){
                $this->Setting->updateAll( array( 'Setting.value_actual' => "'". 'uploads/'. $secureUpload->getFileName() ."'" ), array( 'Setting.name' => 'og_image' ) );
            }else{
                $this->Session->setFlash(__($secureUpload->getMessage()), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect( $this->referer() );
            }
        }
        
        if ( isset($_FILES['cover_desktop']) && is_uploaded_file($_FILES['cover_desktop']['tmp_name']) )
        {
            App::import('Vendor', 'secureFileUpload');
            $secureUpload = new SecureImageUpload(
                array(
                    'fileKeyName' =>  'cover_desktop',
                    'path'=>WWW_ROOT.'uploads' . DS,
                    'whitelist'=>array('extensions'=>array('jpg','jpeg','gif','png'),'type'=>array('image/png', 'image/jpeg', 'image/gif'),),
                    'maxSize' => 2*1024*1024, // 2Mb
                    'scaleUp'=>true,
                )
            );
            if($secureUpload->execute()){
                $this->Setting->updateAll( array( 'Setting.value_actual' => "'". 'uploads/'. $secureUpload->getFileName() ."'" ), array( 'Setting.name' => 'cover_desktop' ) );
            }else{
                $this->Session->setFlash(__($secureUpload->getMessage()), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect( $this->referer() );
            }
        }
        if ( isset($_FILES['male_avatar']) && is_uploaded_file($_FILES['male_avatar']['tmp_name']) )
        {
            
            App::import('Vendor', 'secureFileUpload');
            $secureUpload = new SecureImageUpload(
                array(
                    'fileKeyName' =>  'male_avatar',
                    'path'=>WWW_ROOT.'uploads' . DS,
                    'whitelist'=>array('extensions'=>array('jpg','jpeg','gif','png'),'type'=>array('image/png', 'image/jpeg', 'image/gif'),),
                    'maxSize' => 2*1024*1024, // 2Mb
                    'width'=> '300',
                    'height'=> '300',
                    'scaleUp'=>true,
                )
            );
            if($secureUpload->execute()){
                $this->Setting->updateAll( array( 'Setting.value_actual' => "'". 'uploads/'. $secureUpload->getFileName() ."'" ), array( 'Setting.name' => 'male_avatar' ) );
                //Create Thumbnail for new upload avatar .
                $file = $secureUpload->getFileName();
                $epl = explode('.', $file);
                $extension = $epl[count($epl) - 1];
                $avatarNewName = $epl[0] . '-sm.' . $extension;
                copy(WWW_ROOT . 'uploads/'. $secureUpload->getFileName(), WWW_ROOT  .'uploads/'. $avatarNewName);
                $this->_createThumbnailForAvatar(WWW_ROOT  .'uploads/'. $avatarNewName,WWW_ROOT  .'uploads/'. $avatarNewName, 50, 50);
            }else{
                $this->Session->setFlash(__($secureUpload->getMessage()), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect( $this->referer() );
            }
        }
        if ( isset($_FILES['female_avatar']) && is_uploaded_file($_FILES['female_avatar']['tmp_name']) )
        {
            
            App::import('Vendor', 'secureFileUpload');
            $secureUpload = new SecureImageUpload(
                array(
                    'fileKeyName' =>  'female_avatar',
                    'path'=>WWW_ROOT.'uploads' . DS,
                    'whitelist'=>array('extensions'=>array('jpg','jpeg','gif','png'),'type'=>array('image/png', 'image/jpeg', 'image/gif'),),
                    'maxSize' => 2*1024*1024, // 2Mb
                    'width'=> '300',
                    'height'=> '300',
                    'scaleUp'=>true,
                )
            );
            if($secureUpload->execute()){
                $this->Setting->updateAll( array( 'Setting.value_actual' => "'". 'uploads/'. $secureUpload->getFileName() ."'" ), array( 'Setting.name' => 'female_avatar' ) );
                //Create Thumbnail for new upload avatar .
                $file = $secureUpload->getFileName();
                $epl = explode('.', $file);
                $extension = $epl[count($epl) - 1];
                $avatarNewName = $epl[0] . '-sm.' . $extension;
                copy(WWW_ROOT . 'uploads/'. $secureUpload->getFileName(), WWW_ROOT  .'uploads/'. $avatarNewName);
                $this->_createThumbnailForAvatar(WWW_ROOT  .'uploads/'. $avatarNewName,WWW_ROOT  .'uploads/'. $avatarNewName, 50, 50);
            }else{
                $this->Session->setFlash(__($secureUpload->getMessage()), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect( $this->referer() );
            }
        }
        if ( isset($_FILES['unknown_avatar']) && is_uploaded_file($_FILES['unknown_avatar']['tmp_name']) )
        {
            
            App::import('Vendor', 'secureFileUpload');
            $secureUpload = new SecureImageUpload(
                array(
                    'fileKeyName' =>  'unknown_avatar',
                    'path'=>WWW_ROOT.'uploads' . DS,
                    'whitelist'=>array('extensions'=>array('jpg','jpeg','gif','png'),'type'=>array('image/png', 'image/jpeg', 'image/gif'),),
                    'maxSize' => 2*1024*1024, // 2Mb
                    'width'=> '300',
                    'height'=> '300',
                    'scaleUp'=>true,
                )
            );
            if($secureUpload->execute()){
                $this->Setting->updateAll( array( 'Setting.value_actual' => "'". 'uploads/'. $secureUpload->getFileName() ."'" ), array( 'Setting.name' => 'unknown_avatar' ) );
                //Create Thumbnail for new upload avatar .
                $file = $secureUpload->getFileName();
                $epl = explode('.', $file);
                $extension = $epl[count($epl) - 1];
                $avatarNewName = $epl[0] . '-sm.' . $extension;
                copy(WWW_ROOT . 'uploads/'. $secureUpload->getFileName(), WWW_ROOT  .'uploads/'. $avatarNewName);
                $this->_createThumbnailForAvatar(WWW_ROOT  .'uploads/'. $avatarNewName,WWW_ROOT  .'uploads/'. $avatarNewName, 50, 50);
            }else{
                $this->Session->setFlash(__($secureUpload->getMessage()), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect( $this->referer() );
            }
        }

    }
    public function admin_delete($id)
    {
        if(!$this->allow_modify)
        {
            $this->redirect($this->url);
        }
        $setting = $this->Setting->findById($id);
        $setting_group = $this->SettingGroup->findById($setting['Setting']['group_id']);
        if($setting_group['SettingGroup']['group_type'] != 'core')
        {
            $this->Setting->delete( $id );
            $this->update_plugin_info_xml($setting['Setting']['group_id']);

            $this->Session->setFlash(__('Successfully deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect( $this->referer() );
        }
        else{
            $this->Session->setFlash(__('Can\'t delete Core setting'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
            $this->redirect( $this->referer() );
        }
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
    
    private  function _saveGeneralSettings($system_mode){
        Configure::load('general', 'default');
        if (Configure::read('core.production_mode') != $system_mode['production_mode'])
        {
            $this->loadModel("Minify.MinifyUrl");
            $this->MinifyUrl->deleteAll(array('1 = 1'),false,false);
        }
        Configure::write('system.production_mode', $system_mode['production_mode']);
        Configure::dump('general.php', 'default', array('system'));
    }
    
    public function admin_export()
    {
    	$group_names = array('core','Blog','Topic','Photo','Video','Page','Event','Group','SocialIntegration','FacebookIntegration','GoogleIntegration','Storage','Photo');
    	$settings = $this->Setting->find('all',array('conditions'=>array('SettingGroup.group_type'=>$group_names)));
    	$groups = $this->SettingGroup->find('all',array('conditions'=>array('group_type'=>$group_names)));
    	$list_message = array();
    	foreach ($groups as $group)
    	{
    		$list_message[] = $group['SettingGroup']['name'];
    	}
    	
    	foreach ($settings as $setting)
    	{
    		$value = $setting['Setting']['value_actual'];
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
							if ($multiValue['name'])
							{
								$list_message[] = $multiValue['name'];
							}
						}
					}
					break;
            }
            $list_message[] = $setting['Setting']['label'];
            if ($setting['Setting']['description'])
            {
            	$list_message[] = trim($setting['Setting']['description']);
            }
    	}
    	$list_message = array_unique($list_message);
    	$path = APP.'tmp'.DS.'logs'.DS.'setting.po';
    	MooCore::getInstance()->exportTranslate($list_message,$path);    	
    	$this->viewClass = 'Media';
        // Download app/outside_webroot_dir/example.zip
        $params = array(
            'id'        => 'setting.po',
            'name'      => 'setting',
            'download'  => true,
            'extension' => 'po',
            'path'      => APP.'tmp'.DS.'logs'.DS
        );
        $this->set($params);
    	
    }

    public function admin_feed()
    {
        $this->loadModel("UserSettingFeed");

        foreach ($this->request->data as $key=>$value)
        {
            $this->UserSettingFeed->updateAll(
                array(
                    'UserSettingFeed.active' => $value
                ),
                array(
                    'UserSettingFeed.type' => $key
                )
            );
        }

        Cache::delete("user_setting_feed");
        $this->Session->setFlash(__('Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        $this->redirect( $this->referer() );
    }
    public function admin_reset_image_default($id = null) {
        $array = $this->Setting->findById($id);
        $fields = array("unknown_avatar","female_avatar","male_avatar");
        if ($array) {
            if (!in_array($array['Setting']['field'], $fields)) {
                $this->Setting->updateAll( array( 'Setting.value_actual' => 'Setting.value' ), array( 'Setting.id' => $id ) );
            }
            else {
                $this->Setting->updateAll( array( 'Setting.value_actual' => null ), array( 'Setting.id' => $id ) );
            }
            $this->Session->setFlash(__('Updated successful.'), 'default', array('class' => 'alert alert-success fade in'));
        } 
        $this->redirect($this->referer());
    }
    
    private function _createThumbnailForAvatar($src, $dest, $targetWidth, $targetHeight = null) {
        //https://pqina.nl/blog/creating-thumbnails-with-php
        $image_handlers = array (
            'image/jpeg' => [
                'load' => 'imagecreatefromjpeg',
                'save' => 'imagejpeg',
                'quality' => 100
            ],
            'image/png' => [
                'load' => 'imagecreatefrompng',
                'save' => 'imagepng',
                'quality' => 0
            ],
            'image/gif' => [
                'load' => 'imagecreatefromgif',
                'save' => 'imagegif'
            ]
        );
        $type = @getimagesize($src);
        $type = $type['mime'];
        // if no valid type or no handler found -> exit
        if (!$type || !$image_handlers[$type]) {
            return null;
        }

        // load the image with the correct loader
        $image = call_user_func($image_handlers[$type]['load'], $src);

        // no image found at supplied location -> exit
        if (!$image) {
            return null;
        }


        // 2. Create a thumbnail and resize the loaded $image
        // - get the image dimensions
        // - define the output size appropriately
        // - create a thumbnail based on that size
        // - set alpha transparency for GIFs and PNGs
        // - draw the final thumbnail

        // get original image width and height
        $width = imagesx($image);
        $height = imagesy($image);

        // maintain aspect ratio when no height set
        if ($targetHeight == null) {

            // get width to height ratio
            $ratio = $width / $height;

            // if is portrait
            // use ratio to scale height to fit in square
            if ($width > $height) {
                $targetHeight = floor($targetWidth / $ratio);
            }
            // if is landscape
            // use ratio to scale width to fit in square
            else {
                $targetHeight = $targetWidth;
                $targetWidth = floor($targetWidth * $ratio);
            }
        }

        // create duplicate image based on calculated target size
        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

        // set transparency options for GIFs and PNGs
        if ($type == 'image/gif' || $type == 'image/png') {

            // make image transparent
            imagecolortransparent(
                $thumbnail,
                imagecolorallocate($thumbnail, 0, 0, 0)
            );

            // additional settings for PNGs
            if ($type == 'image/png') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }
        }

        // copy entire source image to duplicate image and resize
        imagecopyresampled(
            $thumbnail,
            $image,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $width, $height
        );


        // 3. Save the $thumbnail to disk
        // - call the correct save method
        // - set the correct quality level

        // save the duplicate version of the image to disk
        return call_user_func(
            $image_handlers[$type]['save'],
            $thumbnail,
            $dest,
            $image_handlers[$type]['quality']
        );
    }
}