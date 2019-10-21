<?php 
App::uses('MooPlugin','Lib');
class StoragePlugin implements MooPlugin{
    public function install(){}
    public function uninstall(){}
    public function settingGuide(){}
    public function menu()
    {
        return array(
        	__('Manage Storage Services') => array('plugin' => 'storage', 'controller' => 'storages', 'action' => 'admin_index'),
        	__('Settings') => array('plugin' => 'storage', 'controller' => 'storage_settings', 'action' => 'admin_index'),
        );
    }
    /*
    Example for version 1.0: This function will be executed when plugin is upgraded (Optional)
    public function callback_1_0(){}
    */
}