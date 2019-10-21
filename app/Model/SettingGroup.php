<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class SettingGroup extends AppModel {
    public $validate = array(   
        'name' =>   array(   
            'notEmpty' => array(
                'rule'     => 'notBlank',
                'message'  => 'Name is required'
            ),
        ),                                           
    );

	public function isIdExist($id)
    {
        return $this->hasAny(array('id' => $id));
    }
    
    public function isNameExist($name, $except_id = null)
    {
        $cond = array('name' => $name);
        if(!empty($except_id))
        {
            $cond['id != '] = $except_id;
        }
        return $this->hasAny($cond);
    }
    
    public function canDelete($id)
    {
        return $this->hasAny(array('id' => $id, 'group_type !=' => 'core'));
    }
}
