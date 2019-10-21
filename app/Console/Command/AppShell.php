<?php
use Embed\Request;

/**
 * AppShell file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Shell', 'Console');
App::uses('MooView', 'View');
App::uses('View', 'View');
App::uses('CakeRequest', 'Network');
App::uses('AppHelper', 'View/Helper');
App::uses('MooMailComponent', 'Mail.Controller/Component');

/**
 * Application Shell
 *
 * Add your application-wide methods in the class below, your shells
 * will inherit them.
 *
 * @package       app.Console.Command
 */
class AppShell extends Shell {
	public function initialize()
	{
		parent::initialize();
		$this->loadSetting();
	}
	
	public function loadSetting()
	{
		$settingDatas = Cache::read('site.settings');
        if (!$settingDatas) 
        {
            $this->loadModel('Setting');
            $this->loadModel('SettingGroup');

            //load all unboot setting
            $settings = $this->Setting->find('all', array(
                'conditions' => array('is_boot' => 0),
                'fields' => array('name', 'value_actual', 'type_id', 'group_id')
            ));
            
            //parse setting value
            $settingDatas = array();
            if($settings != null)
            {
                foreach($settings as $k => $setting)
                {
                    $setting = $setting['Setting'];
                    //parse value
                    $value = $setting['value_actual'];
                    switch($setting['type_id'])
                    {
                        case 'radio':
                        case 'select':
                            $value = '';
                            $multiValues = json_decode($setting['value_actual'], true);
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
                            $multiValues = json_decode($setting['value_actual'], true);
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
                    
                    //parse module
                    $setting_group = $this->SettingGroup->findById($setting['group_id']);
                    $data['module_id'] = $setting_group['SettingGroup']['module_id'];
                    $data['name'] = $setting['name'];
                    $data['value'] = $value;
                    $settingDatas[] = $data;
                }
            }
            Cache::write('site.settings', $settingDatas);
        }

        if($settingDatas != null)
        {
            foreach($settingDatas as $setting)
            {
                Configure::write($setting['module_id'].'.'.$setting['name'], $setting['value']);
            }
        }
        
        Configure::write('core.photo_image_sizes','75_square|150_square|300_square|250|450|850|1500');
        $site = Configure::read("core.site_domain");
        $request = new CakeRequest();
        $request->base = '';
        if ($site)
        {
        	$site = explode('/', $site);
        	$_SERVER['SERVER_NAME'] = $site[0];
        	unset($site[0]);
        	if (count($site))
        	{
        		$request->base = implode('/', $site);
        	}
        }
        Router::setRequestInfo($request);
	}
}
