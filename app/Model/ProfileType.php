<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ProfileType extends AppModel {
	    
    public $order = 'ProfileType.order asc';

	public $validate = array(	
							'name' => 	array( 	 
								'rule' => 'notBlank',
								'message' => 'Name is required'
							)							
	);
	
	public $actsAs = array(
			'Translate' => array('name' => 'nameTranslation')
	);
	
	public $recursive = 1;
	private $_default_locale = 'eng' ;
	public function setLanguage($locale) {
		$this->locale = $locale;
	}
	
	public function getLanguage()
	{
		return $this->locale;
	}
	
	function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->locale = Configure::read('Config.language');
	}
	
	protected $_user_types = array();
	public function getProfileTypesForUserModel($ids)
	{
		$ids['lang'] = $this->locale;
		$key = md5(json_encode($ids));
		if (isset($this->_user_types[$key]))
		{
			return $this->_user_types[$key];
		}
		$this->_user_types[$key] = $this->find('all',array('conditions'=>array('ProfileType.id'=>$ids,'ProfileType.actived'=>true)));
		return $this->_user_types[$key];
	}
}