<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ProfileFieldOption extends AppModel 
{	
	public $actsAs = array(
		'Translate' => array('name' => 'nameTranslation')
	);

	public $validate = array(	
		'name' => 	array( 	 
			'rule' => 'notBlank',
			'message' => 'Name is required'
		)
	);

	public $recursive = 1;
	private $_default_locale = 'eng' ;
	public function setLanguage($locale) {
		$this->locale = $locale;
	}
	
	function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$this->locale = Configure::read('Config.language');
	}

	public $options = array();
	public $field_options = array();
	public $order = 'ProfileFieldOption.order ASC';

	public function getListOption($id)
	{
		if (isset($options[$id]))
		{
			return $options[$id];
		}
		$result = array();
		$options = $this->find('all', array(
			'conditions' => array(
				'ProfileFieldOption.profile_field_id' => $id
			),
			'order' => array(
				'ProfileFieldOption.order ASC'
			)
		));
        if(!empty($options)){
            foreach($options as $option) {
                foreach($option['nameTranslation'] as $t_opt) {
                    if($t_opt['locale'] == $this->locale) {
                        $result[$t_opt['foreign_key']] = $t_opt['content'] ;
                    }
                }
            }
        }
        $options[$id] = $result;
        return $result;
	}

	public function getNameOption($profile_field_id, $id)
	{
		if (empty($this->field_options[$profile_field_id]))
		{
			$options = $this->findAllByProfileFieldId($profile_field_id);
			$this->field_options[$profile_field_id] = $options;
		}

		foreach ($this->field_options[$profile_field_id] as $option)
		{
			if ($option['ProfileFieldOption']['id'] == $id)
			{
				return $option['ProfileFieldOption']['name'];
			}
		}

	}

	public function deleteProfileFieldOption($ids)
	{
		$option_ids = $this->find('list', array(
			'conditions' => array(
				'ProfileFieldOption.profile_field_id' => $ids
			),
			'fields' => 'ProfileFieldOption.id'
		));
		$this->delete($option_ids);
	}
}
