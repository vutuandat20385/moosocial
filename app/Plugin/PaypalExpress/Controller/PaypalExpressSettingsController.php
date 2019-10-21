<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class PaypalExpressSettingsController extends PaypalExpressAppController{
    public function admin_index()
    {
    	$this->loadModel("PaymentGateway.Gateway");
    	$gateway = $this->Gateway->findByPlugin('PaypalExpress');
    	$this->redirect('/admin/payment_gateway/manages/create/'.$gateway['Gateway']['id']);
    }
}