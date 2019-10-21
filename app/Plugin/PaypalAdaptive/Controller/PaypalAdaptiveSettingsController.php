<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class PaypalAdaptiveSettingsController extends PaypalAdaptiveAppController{
    public function admin_index()
    {
    	$this->loadModel("PaymentGateway.Gateway");
    	$gateway = $this->Gateway->findByPlugin('PaypalAdaptive');
    	$this->redirect('/admin/payment_gateway/manages/create/'.$gateway['Gateway']['id']);
    }
}