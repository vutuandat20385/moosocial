<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('SocialIntegrationAppModel', 'SocialIntegration.Model');

class SocialUser extends SocialIntegrationAppModel {

    /**
     * Display field
     *
     * @var string
     */
    public $displayField = 'user_id';

    /**
     * Validation rules
     *
     * @var array
     */
    public $validate = array(
        'id' => array(
            'notEmpty' => array(
                'rule' => array('notBlank'),
                'message' => 'Unknow id',
                'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
            'numeric' => array(
                'rule' => array('numeric'),
                'message' => 'Id must be integer',
                'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
        ),
        'user_id' => array(
            'notEmpty' => array(
                'rule' => array('notBlank'),
                'message' => 'Must provide user id',
                'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
            'numeric' => array(
                'rule' => array('numeric'),
                'message' => 'Must be a number',
                'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
        ),
        'provider' => array(
            'notEmpty' => array(
                'rule' => array('notBlank'),
                'required' => true,
                'last' => true, // Stop validation after this rule
                'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
        ),
        'provider_uid' => array(
            'notEmpty' => array(
                'rule' => array('notBlank'),
                'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
            'numeric' => array(
                'rule' => array('numeric'),
                'on' => 'create', // Limit validation to 'create' or 'update' operations
            ),
        ),
    );

    // check user connected to provider or not
    public function connect($provider = 'facebook') {
        $uid = CakeSession::read('uid');
        $connect = $this->find('count', array('conditions' => array('SocialUser.provider' => $provider, 'SocialUser.user_id' => $uid)));
        if ($connect){
            return true;
        }
        return false;
    }
    
    public function getSocialUser($params = array())
    {
    	$cond = array();
    	if (isset($params['provider']))
    	{
    		$cond['SocialUser.provider'] = $params['provider'];
    	}
    	if (isset($params['provider_uid']))
    	{
    		$cond['SocialUser.provider_uid'] = $params['provider_uid'];
    	}
    	if (isset($params['user_id']))
    	{
    		$cond['SocialUser.user_id'] = $params['user_id'];
    	}
    	if ($cond == null)
    	{
    		return array();
    	}
    	return $this->find('first', array(
    			'conditions' => $cond
    	));
    }
}
