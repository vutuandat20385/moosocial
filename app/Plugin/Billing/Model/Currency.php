<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('BillingAppModel','Billing.Model');
class Currency extends BillingAppModel 
{
    public $validate = array(   
        'name' =>   array(   
            'notEmpty' => array(
                'rule'     => 'notBlank',
                'message'  => 'Name is required'
            ),
        ),     
        'currency_code' => array(
            'rule' => array('notBlank'),
            'message'  => 'Code is not valid'
        ),
        'symbol' => array(
            'rule' => array('notBlank'),
            'message'  => 'Symbol is not valid'
        ),
    );
    public function afterSave($created, $options = array()) {
        Cache::delete("Config.currency");
    }
    public function beforeDelete($cascade = true){
        Cache::delete("Config.currency");
    }
}
