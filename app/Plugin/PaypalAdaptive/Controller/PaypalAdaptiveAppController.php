<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('AppController', 'Controller');
class PaypalAdaptiveAppController extends AppController{
    protected $_setting;
    public function beforeFilter()
    {
    	parent::beforeFilter();
    	if (get_class($this) != 'PaypalAdaptiveSettingsController')
    	{
    		$helper = MooCore::getInstance()->getHelper('PaypalAdaptive_PaypalAdaptive');
    		$this->_setting = $setting = $helper->getSetting();
    		if (!$setting)
    		{
    			$this->_showError( __('Please config gateway') );
    		}
    		if (!$setting['enabled'] && $this->request->params['action']!='ipn')
    		{
    			$this->_showError( __('Please enable gateway') );
    		}
    	}
    }
}