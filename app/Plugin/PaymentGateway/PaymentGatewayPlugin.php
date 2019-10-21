<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('MooPlugin','Lib');
class PaymentGatewayPlugin implements MooPlugin{
    public function install(){}
    public function uninstall(){}
    public function settingGuide(){}
	public function menu()
    {
        return array(
            __('Manage Gateways') => array('plugin' => 'payment_gateway', 'controller' => 'manages', 'action' => 'admin_index'),
        );
    }
}