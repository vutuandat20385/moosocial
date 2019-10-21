<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('MooPlugin','Lib');
class SubscriptionPlugin implements MooPlugin{
    public function install(){}
    public function uninstall(){}
    public function settingGuide(){}
    public function menu()
    {
        $data = array(
            __('Manage Settings') => array('plugin' => 'subscription', 'controller' => 'subscription_settings', 'action' => 'admin_index'),            
        );
        if (Configure::read('Subscription.enable_subscription_packages'))
        {
        	$data[__('Bulk Edit Membership')] = array('plugin' => 'subscription', 'controller' => 'subscription_settings', 'action' => 'admin_bulk');
        }
        $data = array_merge($data,array(__('Manage Packages') => array('plugin' => 'subscription', 'controller' => 'subscription_packages', 'action' => 'admin_index'),
        		__('Manage Subscribers') => array('plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'admin_index'),
        		__('Manage Transactions') => array('plugin' => 'subscription', 'controller' => 'transactions', 'action' => 'admin_index'),
        		__('Manage Refund Requests') => array('plugin' => 'subscription', 'controller' => 'refunds', 'action' => 'admin_index')));
        
        if (Configure::read('Subscription.select_theme_subscription_packages'))
        {
        	$data[__('Manage Comparison Table')] = array('plugin' => 'subscription', 'controller' => 'subscription_compares', 'action' => 'admin_index');
        }
        
        return $data;
    }
}