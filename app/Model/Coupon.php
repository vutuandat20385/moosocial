<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class Coupon extends AppModel {
    public $validate = array(
    	'code' => array(
    		'checkCode' => array(
    			'rule' => 'notBlank',
    			'required' => true,
    			'message' => 'Code is required'
    		),
    		'uniqueCode' => array(
    			'rule' => 'uniqueCode',
    			'message' => 'Code already exists'
    		),
    	),
    	'value' => array(
    		'checkValue' => array(
	    		'rule' => array('checkValue'),
	    		'message' => 'Amount is required'
    		),
    		'checkValueType' => array(
    			'rule' => array('checkValueType'),
    			'message' => 'Amount only allow 0 - 100'
    		)
    	)
    );
    
    public function uniqueCode($values)
    {
    	if (!isset($values['code']))
    	{
    		return false;
    	}
    	
    	$id = $this->data['Coupon']['id'];

    	$code = $this->findByCode($values['code']);
    	if ($code && $code['Coupon']['id'] != $id)
    		return false;
		
		return true;
    }
    public function checkValue($values)
    {
    	if (!isset($values['value']) || !$values['value'])
    	{
    		return false;
    	}    	    	
    	if (!is_numeric($values['value']))
    		return false;
    		
    	return true;
    }
    
    public function checkValueType($values)
    {
    	if (!$this->data['Coupon']['type'])
    	{
    		return true;
    	}
    	
    	return $values['value'] >=0 && $values['value'] <=100;
    }
}
