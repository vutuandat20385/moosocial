<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class PluginsController extends AppController 
{
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_checkPermission( array('super_admin' => true) ); 
        $this->loadModel('Setting');
        $this->loadModel('SettingGroup');
        $this->loadModel('Menu.CoreMenuItem');
    } 
        
    public function admin_index()
    {
        // get all installed plugins
        $plugins = $this->Plugin->find( 'all', array(
            'order' => 'name'
        ));
        
        //check new version
        $plugins = $this->checkNewVersion($plugins);
        
        $installed_plugins_key = array();
        $plugin_setting_url = array();
        foreach ( $plugins as $plugin )
        {
            $installed_plugins_key[] = $plugin['Plugin']['key'];
            $plugin_setting_url[] = $this->splitAtUpperCase($plugin['Plugin']['key']);
        }
        
        // get all plugins in folder
        $all_plugins  = scandir(APP  . 'Plugin');   
        $not_installed_plugins = array();                    
        foreach ( $all_plugins as $key => $plugin )
        {
            if ($plugin != '.' && $plugin != '..' && 
                array_search( $plugin, $installed_plugins_key ) === false && 
                !empty( $plugin ) &&
                is_dir(APP  . 'Plugin'. DS .$plugin))
            {
                $not_installed_plugins[$key]['name'] = $plugin;
                $xmlPath = sprintf(PLUGIN_INFO_PATH, $plugin);
                if(file_exists($xmlPath))
                {
                    $content = file_get_contents($xmlPath);
                    $xml = new SimpleXMLElement($content);
                    $not_installed_plugins[$key]['version'] =   $xml->version;
                    $not_installed_plugins[$key]['author'] =   $xml->author;
                }
            }
        }

        $this->set('plugins', $plugins);
        $this->set('plugin_setting_url',$plugin_setting_url);
        $this->set('not_installed_plugins', $not_installed_plugins);
    } 

    public function admin_ajax_view( $id = null )
    {
        if(!$this->Plugin->isIdExist($id))
        {
            $this->set('error', __("This plugin does not exist."));
        }
        else 
        {
            $plugin = $this->Plugin->findById( $id );
            

            // get plugin info
            $xmlPath = sprintf(PLUGIN_INFO_PATH, $plugin['Plugin']['key']);
            if(file_exists($xmlPath))
            {
                $content = file_get_contents($xmlPath);
                $info = new SimpleXMLElement($content);

                

                // get all roles
                $this->loadModel('Role');
                $roles = $this->Role->find('all');

                $this->set('roles', $roles);        
                $this->set('info', $info);      
                $this->set('plugin', $plugin['Plugin']);     
            }
            else 
            { 
                $this->set('error', __("This plugin does not exist.")); 
            }
        }
    }
    
    public function admin_do_download( $key )
    {
        if(!$this->Plugin->isKeyExist($key))
        {
            $this->Session->setFlash(__('This plugin does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->referer());
        }
        else 
        {
            $plugin = $this->Plugin->findByKey( $key );
            
            $file_name = '';
            if(!empty($plugin['Plugin']['version'])) {
                $file_name = 'moo-' . $key . '-' . $plugin['Plugin']['version'] . '.zip';
            } else {
                $file_name = 'moo-' . $key  . '.zip';
            }

            $zip = new ZipArchive;

            if ( $zip->open( WWW_ROOT . DS . 'uploads' . DS . 'tmp' . DS . $file_name, ZipArchive::CREATE ) === TRUE ) 
            {
                // add plugin folder
                addDir( APP . 'Plugin' . DS . $key, $zip, 'Plugin' . DS . $key );

                if ( !$zip->close() )
                    $this->_showError(__('Cannot create zip file'));

                $this->redirect( '/uploads/tmp/' . $file_name ); 
            }
            else
                $this->_showError(__('Cannot create zip file'));
        }
    }
    
    public function admin_do_enable( $key )
    {
        $this->loadModel('SettingGroup');
        $setting_group = $this->SettingGroup->findByModuleId($key);
        $plugin_info = $this->Plugin->findByKey($key,array('name','id'));
        $original_name = $plugin_info['Plugin']['name'];
        if(!empty($setting_group))
        {
            $this->loadModel('Setting');
            $settings = $this->Setting->findAllByGroupId($setting_group['SettingGroup']['id']);
            $pattern = '/^[\w]+_enabled$/';
            foreach($settings as &$setting)
            {
                //find enabled setting
                if(preg_match($pattern,$setting['Setting']['name']))
                {
                    $values = json_decode($setting['Setting']['value_actual'],true);
                    foreach($values as &$value)
                    {
                        $value['select'] = ($value['name'] == 'Enable') ? 1 : 0;
                    }
                    $new_values = json_encode($values);
                    $this->Setting->id = $setting['Setting']['id'];
                    $this->Setting->save(array('value_actual' => $new_values));

                    //update info.xml in plugin
                    App::uses('SystemSettingsController','Controller');
                    $sys = new SystemSettingsController();
                    $sys->update_plugin_info_xml($setting['Setting']['group_id']);

                    //update core menu
                    $this->loadModel('Menu.CoreMenuItem');
                    if(in_array($key,array('Blog','Group','Topic','Event','Photo','Video')))
                    {
                        $original_name.='s';
                    }
                    $keys_menu = $this->CoreMenuItem->find('first',array(
                        'conditions'=>array('original_name'=> $original_name ,'type'=>'plugin')
                    ));
                    if(!empty($keys_menu)){
                        if ($keys_menu['CoreMenuItem']['id']) {
                            $this->CoreMenuItem->id = $keys_menu['CoreMenuItem']['id'];
                            $this->CoreMenuItem->save(array('is_active' => 1));
                        } else {
                            $this->CoreMenuItem->set(array(
                                'name' => $original_name,
                                'original_name' => $original_name,
                                'url' => '/'.strtolower($key).'s',
                                'is_active' => 1,
                                'menu_id' => 1,
                                'type' => 'plugin',
                                'menu_order' => 999
                            ));
                            $this->CoreMenuItem->save();
                        }
                        Cache::clearGroup('menu');
                    }

                    //update plugin enable status
                    $this->Plugin->id = $plugin_info['Plugin']['id'];
                    $this->Plugin->save( array( 'enabled' => 1 ) );

                    $this->Session->setFlash(__('Plugin has been successfully enabled'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                    break;
                }
            }
        }
        $this->redirect($this->referer());
    }
    
    public function admin_do_disable( $key )
    {
        $this->loadModel('SettingGroup');
        $setting_group = $this->SettingGroup->findByModuleId($key);
        $plugin_info = $this->Plugin->findByKey($key,array('name','id'));
        $original_name = $plugin_info['Plugin']['name'];
        if(!empty($setting_group))
        {
            $this->loadModel('Setting');
            $settings = $this->Setting->findAllByGroupId($setting_group['SettingGroup']['id']);
            $pattern = '/^[\w]+_enabled$/';
            foreach($settings as &$setting)
            {
                //find enabled setting
                if(preg_match($pattern,$setting['Setting']['name']))
                {
                    $values = json_decode($setting['Setting']['value_actual'],true);
                    foreach($values as &$value)
                    {
                        $value['select'] = ($value['name'] == 'Disable') ? 1 : 0;
                    }
                    $new_values = json_encode($values);
                    $this->Setting->id = $setting['Setting']['id'];
                    $this->Setting->save(array('value_actual' => $new_values));

                    //update info.xml in plugin
                    App::uses('SystemSettingsController','Controller');
                    $sys = new SystemSettingsController();
                    $sys->update_plugin_info_xml($setting['Setting']['group_id']);

                    //update core menu
                    $this->loadModel('Menu.CoreMenuItem');
                    if(in_array($key,array('Blog','Group','Topic','Event','Photo','Video')))
                    {
                        $original_name.='s';
                    }
                    $keys_menu = $this->CoreMenuItem->find('first',array(
                        'conditions'=>array('original_name'=> $original_name ,'type'=>'plugin')
                    ));
                    if(!empty($keys_menu)){
                        if ($keys_menu['CoreMenuItem']['id']) {
                            $this->CoreMenuItem->id = $keys_menu['CoreMenuItem']['id'];
                            $this->CoreMenuItem->save(array('is_active' => 0));
                        } else {
                            $this->CoreMenuItem->set(array(
                                'name' => $original_name,
                                'original_name' => $original_name,
                                'url' => '/'.strtolower($key).'s',
                                'is_active' => 0,
                                'menu_id' => 1,
                                'type' => 'plugin',
                                'menu_order' => 999
                            ));
                            $this->CoreMenuItem->save();
                        }
                        Cache::clearGroup('menu');
                    }

                    //update plugin enable status
                    $this->Plugin->id = $plugin_info['Plugin']['id'];
                    $this->Plugin->save( array( 'enabled' => 0 ) );

                    $this->Session->setFlash(__('Plugin has been successfully disabled'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                    break;
                }
            }
        }
        $this->redirect($this->referer());
    }
    
    public function admin_routes_on( $id )
    {
        if($this->Plugin->hasAny(array('id' => $id, 'core' => 1)))
        {
            $this->Session->setFlash(__('You can not modify core plugin'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else if(!$this->Plugin->isIdExist($id))
        {
            $this->Session->setFlash(__('This plugin does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $plugin = $this->Plugin->findById( $id );

            $this->Plugin->id = $id;
            $this->Plugin->save( array( 'routes' => 1 ) );
            
            //update plugin info xml
            $xmlPath = sprintf(PLUGIN_INFO_PATH, $plugin['Plugin']['key']);
            if ( file_exists( $xmlPath) )
            {
                $content = file_get_contents($xmlPath);
                $info = new SimpleXMLElement($content);            
                $info->routes = 1;
                $info->saveXML($xmlPath);
            }

            //update plugins xml
            $this->init_plugins_xml();

            
            Cache::clearGroup('cache_group', '_cache_group_');

            $this->Session->setFlash(__('Routes has been successfully enabled'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        $this->redirect( $this->referer() );
    }
    
    public function admin_routes_off( $id )
    {
        if($this->Plugin->hasAny(array('id' => $id, 'core' => 1)))
        {
            $this->Session->setFlash(__('You can not modify core plugin'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else if(!$this->Plugin->isIdExist($id))
        {
            $this->Session->setFlash(__('This plugin does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $plugin = $this->Plugin->findById( $id );

            $this->Plugin->id = $id;
            $this->Plugin->save( array( 'routes' => 0 ) );
            
            //update plugin info xml
            $xmlPath = sprintf(PLUGIN_INFO_PATH, $plugin['Plugin']['key']);
            if ( file_exists( $xmlPath) )
            {
                $content = file_get_contents($xmlPath);
                $info = new SimpleXMLElement($content);            
                $info->routes = 0;
                $info->saveXML($xmlPath);
            }

            //update plugins xml
            $this->init_plugins_xml();

            
            Cache::clearGroup('cache_group', '_cache_group_');

            $this->Session->setFlash(__('Routes has been successfully disabled'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        $this->redirect( $this->referer() );
    }
    
    public function admin_bootstrap_on( $id )
    {
        if($this->Plugin->hasAny(array('id' => $id, 'core' => 1)))
        {
            $this->Session->setFlash(__('You can not modify core plugin'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else if(!$this->Plugin->isIdExist($id))
        {
            $this->Session->setFlash(__('This plugin does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $plugin = $this->Plugin->findById( $id );
            

            $this->Plugin->id = $id;
            $this->Plugin->save( array( 'bootstrap' => 1 ) );
            
            //update plugin info xml
            $xmlPath = sprintf(PLUGIN_INFO_PATH, $plugin['Plugin']['key']);
            if ( file_exists( $xmlPath) )
            {
                $content = file_get_contents($xmlPath);
                $info = new SimpleXMLElement($content);            
                $info->bootstrap = 1;
                $info->saveXML($xmlPath);
            }

            //update plugins xml
            $this->init_plugins_xml();

            
            Cache::clearGroup('cache_group', '_cache_group_');

            $this->Session->setFlash(__('Bootstrap has been successfully enabled'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        $this->redirect( $this->referer() );
    }
    
    public function admin_bootstrap_off( $id )
    {
        if($this->Plugin->hasAny(array('id' => $id, 'core' => 1)))
        {
            $this->Session->setFlash(__('You can not modify core plugin'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else if(!$this->Plugin->isIdExist($id))
        {
            $this->Session->setFlash(__('This plugin does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $plugin = $this->Plugin->findById( $id );
            

            $this->Plugin->id = $id;
            $this->Plugin->save( array( 'bootstrap' => 0 ) );
            
            //update plugin info xml
            $xmlPath = sprintf(PLUGIN_INFO_PATH, $plugin['Plugin']['key']);
            if ( file_exists( $xmlPath) )
            {
                $content = file_get_contents($xmlPath);
                $info = new SimpleXMLElement($content);            
                $info->bootstrap = 0;
                $info->saveXML($xmlPath);
            }

            //update plugins xml
            $this->init_plugins_xml();

            
            Cache::clearGroup('cache_group', '_cache_group_');

            $this->Session->setFlash(__('Bootstrap has been successfully disabled'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        $this->redirect( $this->referer() );
    }
    
    public function admin_ajax_reorder()
    {
        $this->autoRender = false;
        
        $i = 1;
        foreach ($this->request->data['plugins'] as $plugin_id)
        {
            $this->Plugin->updateAll( array( 'weight' => $i ), array( 'id' => $plugin_id ) );
            $i++;
        }
        
        
        Cache::clearGroup('cache_group', '_cache_group_');
    }
    
    public function admin_ajax_save()
    {
        $id = $this->request->data['id'];
        if(!$this->Plugin->isIdExist($id))
        {
            $this->Session->setFlash(__('This plugin does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        else 
        {
            $this->autoRender = false;

            $plugin = $this->Plugin->findById($id);
            $this->_checkExistence( $plugin );

            $xmlPath = sprintf(PLUGIN_INFO_PATH, $plugin['Plugin']['key']);
            if ( file_exists( $xmlPath) )
            {
                $content = file_get_contents($xmlPath);
                $info = new SimpleXMLElement($content);            
                $info->bootstrap = $this->request->data['bootstrap'];
                $info->routes = $this->request->data['routes'];
                $info->saveXML($xmlPath);
            }

            $this->request->data['permission'] = (empty( $this->request->data['everyone'] )) ? implode(',', $_POST['permissions']) : '';

            $this->Plugin->id = $this->request->data['id'];
            $this->Plugin->save( $this->request->data );

            
            Cache::clearGroup('cache_group', '_cache_group_');
            $this->Session->setFlash(__('Plugin has been successfully updated'),'default',
                array('class' => 'Metronic-alerts alert alert-success fade in' ));
            
            //update plugins config
            $this->init_plugins_xml();
        }
    }
    
    public function admin_ajax_create()	
    {
        $plugin_folder = (APP . 'Plugin');
        
        $perms = (int) substr(sprintf('%o', fileperms($plugin_folder)), -4);
        if ($perms >= 755) {
            $dir_writable = 1;
        } else {
            $dir_writable = 0;
        }
        
        $this->set('dir_writable', $dir_writable);
        
        // get all roles
        $this->loadModel('Role');
        $roles = $this->Role->find('all');
        
        $this->set('roles', $roles);  
        $this->set('pluginType', $this->Plugin->PluginType());
    }
    
    private function splitAtUpperCase($string)
    {
        $result = preg_split('/(?=[A-Z])/',$string);
        $result = implode('_', $result);
        $result = strtolower($result);
        if(substr($result, 0, 1) == '_')
        {
            $result = substr($result, 1);
        }
        return $result;
    }
    
    public function pluginMenu()
    {
        $plugins = $this->Plugin->find('all', array(
            'conditions' => array('core' => 0, 'enabled' => 1),
            'fields' => array('name', 'key')
        ));
        if($plugins != null)
        {
            foreach($plugins as $k => $plugin)
            {
                $plugin = $plugin['Plugin']; 
                $plugins[$k]['Plugin']['link'] = $this->request->base.'/admin/'.$this->splitAtUpperCase($plugin['key']);
            }
        }
        return $plugins;
    }

    protected function _jsonError( $msg )
    {
        $this->autoRender = false;
        
        $response['result'] = 0;
        $response['message'] = $msg;
        
        echo json_encode($response);
        exit;
    }
    
    private function init_plugins_xml()
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
                    <info></info>';
        file_put_contents(PLUGIN_CONFIG_PATH, $content);
        $xml = new SimpleXMLElement($content);  

        //add plugins to xml
        $plugins = $plugins = $this->Plugin->find('all');
        if($plugins != null)
        {
            $pluginsXml = $xml->addChild('plugins');
            foreach($plugins as $plugin)
            {
                $pluginXml = $pluginsXml->addChild('plugin');
                $pluginXml->addChild('name', $plugin['Plugin']['key']);
                $pluginXml->addChild('enabled', $plugin['Plugin']['enabled'] == 1 ? 1: 0);
                $pluginXml->addChild('bootstrap', $plugin['Plugin']['bootstrap'] == 1 ? 1: 0);
                $pluginXml->addChild('routes', $plugin['Plugin']['routes'] == 1 ? 1: 0);
            }
        }
        $xml->saveXML(PLUGIN_CONFIG_PATH);
        
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $dom->save(PLUGIN_CONFIG_PATH);
    }
    
    private function init_setting_group($key, $name)
    {
        if(!$this->SettingGroup->hasAny(array('group_type' => $key, 'module_id' => $key)))
        {
            $data = array('group_type' => $key, 'module_id' => $key, 'name' => $name);
            $this->SettingGroup->save($data);
            return $this->SettingGroup->getInsertID();
        }
        else 
        {
            $setting_group = $this->SettingGroup->find('first', array(
                'conditions' => array('group_type' => $key, 'module_id' => $key),
                'fields' => array('id')
            ));
            return $setting_group['SettingGroup']['id'];
        }
    }
    
    private function init_setting($group_id, $name)
    {
        $this->Setting->create();
        $this->Setting->set(array(
            'group_id' => $group_id,
            'label' => $name,
            'name' => strtolower($name) . '_enabled',
            'type_id' => 'radio',
            'value_actual' => '[{"name":"Disable","value":"0","select":0},{"name":"Enable","value":"1","select":1}]',
            'value_default' => '[{"name":"Disable","value":"0","select":0},{"name":"Enable","value":"1","select":1}]',
            'is_boot' => '1'
        ));
        $this->Setting->save();
    }
    
    private function clearCache()
    {
        //clear cache for unbootsetting in app/AppController function loadUnBootSetting
        Cache::delete('site.settings');

        Cache::clear(false,'_cake_core_');
        Cache::clear(false,'_cake_model_');
        Cache::clear(false,'_cache_group_');
    }
    
    ///////////////////////////////////////create///////////////////////////////////////
    public function admin_ajax_structure()
	{
		$this->autoRender = false;	
		$key = $this->request->data['key'] = ucfirst($this->request->data['key']);	
        if($this->Plugin->isKeyExist($key))
        {
            $this->_jsonError(' <b>' . $key . '</b> Duplicate key');
        }
        else 
        {
            $pluginPath = APP . 'Plugin' . DS . $key;

            $this->Plugin->set( $this->request->data );
            $this->_validateData( $this->Plugin );

            if ( file_exists($pluginPath ) )
                $this->_jsonError(' <b>' . $key . '</b> folder already exists');

            if ( $this->Plugin->save() )
            {				
                // create folders
                mkdir($pluginPath, 0755 );	

                mkdir($pluginPath . DS . 'Config', 0755 );
                mkdir($pluginPath . DS . 'Config' . DS . 'install', 0755 );
                file_put_contents($pluginPath . DS . 'Config' . DS . 'bootstrap.php', '');
                file_put_contents($pluginPath . DS . 'Config' . DS . 'install' . DS . 'install.sql', '');
                file_put_contents($pluginPath . DS . 'Config' . DS . 'install' . DS . 'uninstall.sql', '');
                $content = 
'<?xml version="1.0" encoding="utf-8"?>
<versions>
    <version>
        <number>'.$this->request->data['version'].'</number>
        <queries>
            <query></query>
        </queries>
    </version>
</versions>';
                file_put_contents($pluginPath . DS . 'Config' . DS . 'install' . DS . 'upgrade.xml', $content);
                
                mkdir($pluginPath . DS . 'Controller', 0755 );
                mkdir($pluginPath . DS . 'Controller' . DS . 'Component', 0755 );
                
                mkdir($pluginPath . DS . 'Lib', 0755 );

                mkdir($pluginPath . DS . 'Model', 0755 );	
                mkdir($pluginPath . DS . 'Model' . DS . 'Behavior', 0755 );

                mkdir($pluginPath . DS . 'View', 0755 );	
                mkdir($pluginPath . DS . 'View' . DS . 'Elements', 0755 );	
                mkdir($pluginPath . DS . 'View' . DS . 'Helper', 0755 );	
                mkdir($pluginPath . DS . 'View' . DS . 'Layouts', 0755 );	
                
                //create routes.php
                $plugin_url = $this->splitAtUpperCase($key).'s';
                $content = 
"<?php
Router::connect('/$plugin_url/:action/*', array(
    'plugin' => '$key',
    'controller' => '$plugin_url'
));

Router::connect('/$plugin_url/*', array(
    'plugin' => '$key',
    'controller' => '$plugin_url',
    'action' => 'index'
));
";
                file_put_contents($pluginPath . DS . 'Config' . DS . 'routes.php', $content);
                // create xml file
                $content = 
'<?xml version="1.0" encoding="utf-8"?>
<info>
    <name>' . $this->request->data['name'] . '</name>
    <key>' . $this->request->data['key'] . '</key>
    <version>' . $this->request->data['version'] . '</version>
    <description>' . $this->request->data['description'] . '</description>
    <author>' . $this->request->data['author'] . '</author>
    <website>' . $this->request->data['website'] . '</website>
    <bootstrap>' . $this->request->data['bootstrap'] . '</bootstrap>
    <routes>' . $this->request->data['routes'] . '</routes>
    <addtomenu>' . $this->request->data['add_to_menu'] . '</addtomenu>
</info>';
                file_put_contents($pluginPath . DS . 'info.xml', $content);

                //create default file
                $this->createDefaultPluginFiles($key, $this->request->data['name']);
                
                //create file by plugin type
                $this->createFileByPluginType($key, $this->request->data['plugin_type']);

                if ($this->request->data['add_to_menu']) {
                    //add to menu
                    $this->AddMenu($this->request->data['name'], $this->request->data['key']);
                }
                // add setting enable plugin
                
                
                // delete cache file
                Cache::delete('site_plugins');

                $response['result'] = 1;
                $response['id'] = $this->Plugin->id;
                echo json_encode($response);

                $this->init_plugins_xml();
                $group_id = $this->init_setting_group($key, $this->request->data['name']);
                $this->init_setting($group_id, $this->request->data['name']);
            }
        }
	}
    
    private function createDefaultPluginFiles($key, $pluginName)
    {
        $pluginPath = APP . 'Plugin' . DS . $key;
        $defControllerName = $key."sController";
        $defSettingControllerName = $key."SettingsController";
        $defViewName = $key.'s';
        $defSettingViewName = $key."Settings";
        $defSetupName = $key."Plugin";
        
        //create setup file
        $pluginUrl = $this->splitAtUpperCase($key);
        $content = 
"<?php 
App::uses('MooPlugin','Lib');
class $defSetupName implements MooPlugin{
    public function install(){}
    public function uninstall(){}
    public function settingGuide(){}
    public function menu()
    {
        return array(
            'General' => array('plugin' => '$pluginUrl', 'controller' => '".$this->splitAtUpperCase($defViewName)."', 'action' => 'admin_index'),
            'Settings' => array('plugin' => '$pluginUrl', 'controller' => '".$this->splitAtUpperCase($defSettingViewName)."', 'action' => 'admin_index'),
        );
    }
    /*
    Example for version 1.0: This function will be executed when plugin is upgraded (Optional)
    public function callback_1_0(){}
    */
}";
        file_put_contents($pluginPath . DS . $defSetupName.'.php', $content);

        //create plugin app controller
        $content = 
"<?php 
App::uses('AppController', 'Controller');
class ".$key."AppController extends AppController{
    
}";
        file_put_contents($pluginPath . DS . 'Controller' . DS . $key.'AppController.php', $content);

        //create plugin app model
        $content = 
"<?php 
App::uses('AppModel', 'Model');
class ".$key."AppModel extends AppModel{
    
}";
        file_put_contents($pluginPath . DS . 'Model' . DS . $key.'AppModel.php', $content);
        
        //create default controller
        $content = 
"<?php 
class $defControllerName extends ".$key."AppController{
    public function admin_index()
    {
    }
    public function index()
    {
    }
}";
        file_put_contents($pluginPath . DS . 'Controller' . DS . $defControllerName.'.php', $content);
        
        //create default setting controller
        $content = 
"<?php 
class $defSettingControllerName extends ".$key."AppController{
    public function admin_index()
    {
    }
}";
        file_put_contents($pluginPath . DS . 'Controller' . DS .$defSettingControllerName.'.php', $content);
        
        //create default plugin view
        mkdir($pluginPath . DS . 'View' . DS . $defViewName, 0755 );	
        $content = 
"<?php
    echo ".'$this'."->Html->css(array('jquery-ui', 'footable.core.min'), null, array('inline' => false));
    echo ".'$this'."->Html->script(array('jquery-ui', 'footable'), array('inline' => false));
    ".'$this'."->startIfEmpty('sidebar-menu');
    echo ".'$this'."->element('admin/adminnav', array('cmenu' => '$pluginName'));
    ".'$this'."->end();
?>
<?php echo".'$this'."->Moo->renderMenu('$key', 'General');?>";
        file_put_contents($pluginPath . DS . 'View' . DS . $defViewName . DS .'admin_index.ctp', $content);
        file_put_contents($pluginPath . DS . 'View' . DS . $defViewName . DS .'index.ctp', '');
        
        //create default setting view
        mkdir($pluginPath . DS . 'View' . DS .$defSettingViewName, 0755 );	
        $content = 
"<?php
    echo ".'$this'."->Html->css(array('jquery-ui', 'footable.core.min'), null, array('inline' => false));
    echo ".'$this'."->Html->script(array('jquery-ui', 'footable'), array('inline' => false));
    ".'$this'."->startIfEmpty('sidebar-menu');
    echo ".'$this'."->element('admin/adminnav', array('cmenu' => '$pluginName'));
    ".'$this'."->end();
?>
<?php echo".'$this'."->Moo->renderMenu('$key', 'Settings');?>";
        file_put_contents($pluginPath . DS . 'View' . DS .$defSettingViewName . DS .'admin_index.ctp', $content);
    }
    
    private function createFileByPluginType($key, $type)
    {
        $pluginPath = APP . 'Plugin' . DS . $key;
        $libPath = $pluginPath . DS . 'Lib';
        switch($type)
        {
            case 'payment':
                $content = 
"<?php 
App::uses('MooGateway','Lib');
class $key implements MooGateway{
    public function renderHtmlForm(".'$params'.", ".'$recurrence'." = false)
    {
    }
    
    public function validateTransaction()
    {
    }
}";
                file_put_contents($libPath . DS . $key.'.php', $content);
                break;
        }
    }
    
    ///////////////////////////////////////install///////////////////////////////////////
    public function admin_do_install( $key )
    {
        if($this->Plugin->isKeyExist($key))
        {
            $this->Session->setFlash(__('Duplicate key'), 'default', array( 'class' => 'Metronic-alerts alert alert-danger fade in') );
        }
        else 
        {
            //xml
            $xmlPath = sprintf(PLUGIN_INFO_PATH, $key);
            if ( file_exists($xmlPath) )
            {
                $content = file_get_contents($xmlPath);
                $info = new SimpleXMLElement($content);
                $setting_group_id = $this->init_setting_group($key, (String)$info->name);
                $settings = array();
                if ( !empty( $info->settings ) )
                {
                    $datas = json_decode(json_encode($info->settings), true);
                    if(!isset($datas['setting'][0]))
                    {
                        $temp = $datas['setting'];
                        unset($datas['setting']);
                        $datas['setting'][] = $temp;
                    }
                    foreach ($datas['setting'] as $data)
                    {
                        //insert setting to db if not exist
                        if(!$this->Setting->isSettingNameExist($data['name']))
                        {
                            $this->Setting->create();
                            $vals = $data['values'];
                            if($data['type'] == 'radio' || $data['type'] == 'checkbox' || $data['type'] == 'select')
                            {
                            	//Fix install with checkbox one value
                            	if (isset($data['values']['value']['name']))
                            	{
                            		if (!$data['values']['value']['name']);
                            			$data['values']['value']['name'] = '';
                            		$data['values']['value'] = array($data['values']['value']);
                            	}
                                $vals = json_encode($data['values']['value']);
                            }
                            $setting_values = array('group_id' => $setting_group_id,
                                                    'label' => (String)$data['label'],
                                                    'name' => (String)$data['name'],
                                                    'description' => $data['description'],
                                                    'type_id' => (String)$data['type'],
                                                    'value_actual' => $vals,
                                                    'value_default' => $vals,
                                                    'ordering' => $this->Setting->generateOrdering($setting_group_id));

                            $this->Setting->save($setting_values);
                        }
                    }  
                }
                
                $this->Plugin->set( array( 'name' => (String)$info->name,
                                           'key' => (String)$info->key,
                                           'menu' => (String)$info->menu,
                                           'url' => (String)$info->url,
                                           'version' => (String)$info->version,
                                           'bootstrap' => (int)$info->bootstrap,
                                           'routes' => (int)$info->routes));
                if ( $this->Plugin->save() )
                {
                    $id = $this->Plugin->getLastInsertId();
                    
                    // register plugin in plugins config
                    $this->init_plugins_xml();

                    if (!empty($info->addtomenu)) {
                        //add menu
                        $this->AddMenu($info->name, $info->key);
                    }

                    //install db
                    $this->installDatabase($key);
                    
                    //run install function
                    $this->executeInstall($key);
                    
                    //clear cache
                    $this->clearCache();

                    $this->Session->setFlash(__('Plugin has been successfully installed'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                }
                else
                {
                    $this->Session->setFlash(__('An error has occured'), 'default', array( 'class' => 'error-message') );
                }
            }
            else
            {
                $this->Session->setFlash(__('Cannot read plugin info file'), 'default', array( 'class' => 'error-message') );
            }
        }
        $this->redirect( $this->referer() );
    }
    
    private function installDatabase($key)
    {
        $file = sprintf(PLUGIN_INSTALL_PATH, $key);
        if(file_exists($file))
        {
            $content = file_get_contents($file);
            if(!empty($content))
            {
                $query = str_replace('{PREFIX}', $this->Setting->tablePrefix, $content);
                $db = ConnectionManager::getDataSource('default');
                try 
                {
                    $db->rawQuery($query);
                } 
                catch (Exception $ex) 
                {
                    $this->Session->setFlash($ex->getMessage(), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                }
            }
        }
    }
    
    private function executeInstall($key)
    {
        App::build(array($key.'Plugin' => array(sprintf(PLUGIN_PATH, $key))), App::REGISTER);
        $classname = $key.'Plugin';
        App::uses($classname, $classname);
        if(class_exists($classname))
        {
            $cl = new $classname();
            if(method_exists($classname, 'install'))
            {
                $cl->install();
            }
        }
    }
    
    private function AddMenu($name, $key)
    {
        
        $roleModel = MooCore::getInstance()->getModel('Role');
    	$roles = $roleModel->find('all');
    	$role_ids = array();
    	foreach ($roles as $role)
    	{
            $role_ids[] = $role['Role']['id'];
    	}   
        
        $this->CoreMenuItem->set(array(
            'name' => $name,
            'original_name' => $name,
            'url' => '/'.$this->splitAtUpperCase($key).'s',
            'is_active' => 1,
            'menu_id' => 1,
            'type' => 'plugin',
            'role_access'=>json_encode($role_ids),
            'menu_order' => 999
        ));
        $this->CoreMenuItem->save();
        
        // clear cache
        Cache::clearGroup('menu', 'menu');
    }


    ///////////////////////////////////////uninstall///////////////////////////////////////
    public function admin_do_uninstall( $id )
    {
        if(!$this->Plugin->isIdExist($id))
        {
            $this->Session->setFlash(__('This plugin does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $plugin = $this->Plugin->findById( $id );
            $key = $plugin['Plugin']['key'];
            $this->_checkExistence( $plugin );

            // run plugin uninstall script here

            $this->Plugin->delete( $id ); 

            //remove plugin in plugins config
            $this->init_plugins_xml();
            
            //delete menu
            $this->deleteMenu($key);
            
            //uninstall db
            $this->uninstallDatabase($key);
            
            //run uninstall function
            $this->executeUninstall($key);
            
            //delete setting
            $this->deleteSetting($key);
            
            //clear cache
            $this->clearCache();

            //activitylog event
            $cakeEvent = new CakeEvent('Controller.Plugin.afterUninstall', $this, array('plugin' => $plugin));
            $this->getEventManager()->dispatch($cakeEvent);

            $this->Session->setFlash(__('Plugin has been successfully uninstalled'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        $this->redirect( $this->referer() );
    }
    
    private function deleteSetting($key)
    {
    	$this->loadModel('SettingGroup');
    	$this->loadModel('Setting');
    	$group = $this->SettingGroup->findByModuleId($key);
    	if ($group)
    	{
    		$this->Setting->deleteAll(array('Setting.group_id' => $group['SettingGroup']['id']), false);
    		$this->SettingGroup->delete($group['SettingGroup']['id']);
    	}
    }
    
    private function uninstallDatabase($key)
    {
        $file = sprintf(PLUGIN_UNINSTALL_PATH, $key);

        if(file_exists($file))
        {
            $content = file_get_contents($file);
            if(!empty($content))
            {
                $query = str_replace('{PREFIX}', Configure::read('core.prefix'), $content);
                $db = ConnectionManager::getDataSource('default');
                try 
                {
                    $db->rawQuery($query);
                } 
                catch (Exception $ex) 
                {
                    $this->Session->setFlash($ex->getMessage(), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                }
            }
        }
    }
    
    private function executeUninstall($key)
    {
        $classname = $key.'Plugin';
        App::uses($classname, $classname);
        if(class_exists($classname))
        {
            $cl = new $classname();
            if(method_exists($classname, 'uninstall'))
            {
                $cl->uninstall();
            }
        }
    }
    
    private function deleteMenu($key)
    {
        $url = "/".$this->splitAtUpperCase($key).'s';
        $menu = $this->CoreMenuItem->findByUrl($url);
        if ($menu)
            $this->CoreMenuItem->delete($menu['CoreMenuItem']['id']);
        
        // clear cache
        Cache::clearGroup('menu', 'menu');
    }
    
    ///////////////////////////////////////upgrade///////////////////////////////////////
    private function checkNewVersion($plugins)
    {
        if($plugins != null)
        {
            foreach($plugins as $k => $plugin)
            {
                $plugins[$k]['Plugin']['new_version'] = false;
                $plugins[$k]['Plugin']['new_version_number'] = '';
                
                $plugin = $plugin['Plugin'];
                $file = sprintf(PLUGIN_INFO_PATH, $plugin['key']);
                if(file_exists($file))
                {
                    $xml = simplexml_load_file($file);
                    if($xml != null && isset($xml->version))
                    {
                        if((string)$xml->version > $plugin['version'])
                        {
                            $plugins[$k]['Plugin']['new_version'] = true;
                            $plugins[$k]['Plugin']['new_version_number'] = (string)$xml->version;
                        }
                    }
                }
            }
        }
        return $plugins;
    }
    
    public function totalNewVersion()
    {
        // get all installed plugins
        $plugins = $this->Plugin->find( 'all', array(
            'order' => 'id DESC'
        ));
        
        $totalNewVersion = 0;
        if($plugins != null)
        {
            foreach($plugins as $k => $plugin)
            {
                $plugin = $plugin['Plugin'];
                $file = sprintf(PLUGIN_INFO_PATH, $plugin['key']);
                if(file_exists($file))
                {
                    $xml = simplexml_load_file($file);
                    if($xml != null && isset($xml->version))
                    {
                        if((string)$xml->version > $plugin['version'])
                        {
                            $totalNewVersion += 1;
                        }
                    }
                }
            }
        }
        return $totalNewVersion;
    }
    
    public function admin_do_upgrade( $id )
    {
        if(!$this->Plugin->hasAny(array('id' => $id)))
        {
            $this->Session->setFlash(__('This plugin does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            //load plugin
            $plugin = $this->Plugin->findById( $id );
            $plugin = $plugin['Plugin'];
            
            //check new version
            $file = sprintf(PLUGIN_INFO_PATH, $plugin['key']);
            $version = $plugin['version'];
            if(file_exists($file))
            {
                $xml = simplexml_load_file($file);
                if($xml != null && isset($xml->version))
                {
                    if((string)$xml->version <= $plugin['version'])
                    {
                        $this->Session->setFlash(__('There is no new upgrade for this plugin.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                        $this->redirect( $this->referer() );
                    }
                    else 
                    {
                        $version = (string)$xml->version;
                    }
                }
            }
            else 
            {
                $this->Session->setFlash(__('File info.xml not found.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                $this->redirect( $this->referer() );
            }
            
            //update db version number
            $this->Plugin->id = $id;
            $this->Plugin->set(array('version' => $version));
            if(!$this->Plugin->save())
            {
                $this->Session->setFlash(__('Cannot upgrade new version. Please try again.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                $this->redirect( $this->referer() );
            }
            else 
            {
                $this->Session->setFlash(__('Successfully upgrade.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            }
                
            //read upgrade.xml
            $file = sprintf(PLUGIN_UPGRADE_PATH, $plugin['key']);
            if(file_exists($file))
            {
                $xml = simplexml_load_file($file);
                if($xml != null && isset($xml->version))
                {
                    foreach($xml->version as $version)
                    {
                        if((string)$version->number > $plugin['version'])
                        {
                            //update db
                            $this->upgradeDatabase($version->queries);
                            
                            //upgrade callback
                            $this->executeUpgrade($plugin['key'], (string)$version->number);
                        }
                    }
                }
            }
            else
            {
                $this->Session->setFlash(__('Cannot read plugin upgrade file. Please try again.'), 'default', array( 'class' => 'Metronic-alerts alert alert-success fade in') );
            }
        }
        
        //clear cache
        $this->clearCache();
        
        $this->redirect( $this->referer() );
    }
    
    private function upgradeDatabase($xml)
    {
        if($xml != null)
        {
			$db = ConnectionManager::getDataSource('default');
            foreach($xml->query as $query)
            {
				$query = (string)$query;
                if(!empty($query))
                {
                    $query = str_replace('{PREFIX}', Configure::read('core.prefix'), $query);
                    try 
                    {
                        $db->rawQuery($query);
                    } 
                    catch (Exception $ex) 
                    {
                        $this->Session->setFlash($ex->getMessage(), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                    }
                }
            }
        }
    }
    
    private function executeUpgrade($key, $version)
    {
        if(!empty($key) && !empty($version))
        {
            $version = str_replace('.', '_', $version);
            $classname = $key.'Plugin';
            App::uses($classname, $classname);
            if(class_exists($classname))
            {
                $cl = new $classname();
                $func = "callback_".$version;
                if(method_exists($classname, $func))
                {
                    $cl->$func();
                }
            }
        }
    }
}
    