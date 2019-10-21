<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

App::uses('ObjectCollection', 'Utility');
App::uses('Component', 'Controller');
App::uses('CakeEventListener', 'Event');
App::uses('ComponentCollection', 'Controller');
class WidgetCollection extends ComponentCollection{
	public function __construct($controller = null)
	{
		$this->_Controller = $controller;
	}

    public function load($widget, $settings = array()) {
        if (isset($settings['className'])) {
            $alias = $widget;
            $widget = $settings['className'];
        }
        list($plugin, $name) = pluginSplit($widget, true);
        
        if ($plugin)
        {
        	$plugins_active = MooCore::getInstance()->getListPluginEnable();
        	$plugin_name = str_replace('.', '', $plugin);
        	if (!in_array($plugin_name,$plugins_active))
        	{
        		return false;
        	}
        }
        
        if (!isset($alias)) {
            $alias = $name;
        }        
        $alias.=$settings['params']['content_id'];
        
        $path = 'Controller'.DS.'Widgets';
        if (strpos($name, DS) !== false) {
            $parts = explode(DS, $name);

            $name = $parts[count($parts)-1];
            unset($parts[count($parts)-1]);
            $path = $path.DS.implode(DS,$parts);
        }
        
        if ($plugin)
        {
        	$widgetClass = $name . str_replace('.', '', $plugin) .'Widget';	
        }
        else
        {
        	$widgetClass = $name . 'CoreWidget';	
        }
        App::uses($widgetClass, $plugin . $path);

        if (!class_exists($widgetClass)) {
            return false;
            throw new MissingComponentException(array(
                'class' => $widgetClass,
                'plugin' => substr($plugin, 0, -1)
            ));
        }

        $this->_loaded[$alias] = new $widgetClass($this, $settings);
        if(isset($settings['params']))
           $this->_loaded[$alias]->set($settings['params']);
        $enable = isset($settings['enabled']) ? $settings['enabled'] : true;
        if ($enable) {
            $this->enable($alias);
        }
        return $this->_loaded[$alias];
    }
}