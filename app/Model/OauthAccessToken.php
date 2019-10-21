<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('ApiAppModel', 'Api.Model');
/**
 * OauthAccessToken Model
 *
 * @property User $User
 */
class OauthAccessToken extends AppModel {

/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'access_token';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'access_token';

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'access_token' => array(
			'notEmpty' => array(
				'rule' => array('notBlank'),
				
			),
		),

		'user_id' => array(
			'notEmpty' => array(
				'rule' => array('notBlank'),
				
			),
		),
		'expires' => array(
			'notEmpty' => array(
				'rule' => array('notBlank'),
				
			),
		),
	);

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'User' => array(
			'className' => 'User',
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
