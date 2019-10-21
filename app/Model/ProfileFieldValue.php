<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ProfileFieldValue extends AppModel 
{	
	public $belongsTo = array( 'ProfileField');
	
	public function getValues( $uid, $profile_fields_only = false, $show_heading = false, $profile_type_id = null)
	{
		$this->virtualFields = array('name' => 'I18n.content');
		if ($profile_type_id)
		{
			$cond = array( 'ProfileField.active' => 1,'ProfileField.profile_type_id' => $profile_type_id);
		}
		else
		{
			$cond = array( 'ProfileField.active' => 1);
		}
        
        if ( $profile_fields_only )
            $cond['ProfileField.profile'] = 1;
        
        if ( $show_heading )
            $cond['OR'] = array( 'ProfileFieldValue.user_id' => $uid, 'ProfileField.type' => 'heading' );
        else
            $cond['ProfileFieldValue.user_id'] = $uid;

		$vals = $this->find( 'all', array('joins'=>array(
       		array(
            	'type' => 'INNER',
            	'alias' => 'I18n',
            	'table' => 'i18n',
            	'foreignKey' => false,
            	'conditions' => array(
            		'ProfileFieldValue.profile_field_id = I18n.foreign_key',
            		'I18n.locale' => Configure::read('Config.language'),
            		'I18n.model' => 'ProfileField',
            		),
            	),
        ), 'conditions' => $cond, 'order' => 'ProfileField.weight' ) );
		
		$this->virtualFields = array();
	
		foreach ($vals as &$val)
		{
			
			if (isset($val['ProfileField']) && $val['ProfileField'])
			{
				$val['ProfileField']['name'] = $val['ProfileFieldValue']['name'];
			}
		}
							
		return $vals;
	}
}
