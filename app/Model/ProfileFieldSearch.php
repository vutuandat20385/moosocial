<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ProfileFieldSearch extends AppModel 
{	
    public function saveSearchValue($uid, $data = array())
    {
        if (empty($data))
        {
            $mProfileFieldValue = MooCore::getInstance()->getModel('ProfileFieldValue');
            $values = $mProfileFieldValue->findAllByUserId($uid);
            if ($values)
            {
                foreach ($values as $value)
                {
                    if (!empty($value['ProfileFieldValue']['value']))
                    {
                        if ($value['ProfileField']['type'] == 'multilist')
                        {
                            $value['ProfileFieldValue']['value'] = implode(',', explode(', ',$value['ProfileFieldValue']['value']));
                            $data['field_' . $value['ProfileFieldValue']['profile_field_id']] = $value['ProfileFieldValue']['value'];
                        }
                        else
                        {
                            $data['field_' . $value['ProfileFieldValue']['profile_field_id']] = $value['ProfileFieldValue']['value'];
                        } 
                    }
                }
            }
        }

        if (!empty($data))
        {
            $data['user_id'] = $uid;

            $value = $this->find('first', array(
                'conditions' => array('ProfileFieldSearch.user_id' => $uid)
            ));
            if ($value)
            {
                $this->id = $value['ProfileFieldSearch']['id'];
                $this->save($data);
            }
            else
            {
                $this->clear();
                $this->save($data);
            }
        }
    }

    public function searchUser($params, $count)
    {
    	$columns = $this->getColumnTypes();
        $cond = array();
        foreach ($params as $key => $param)
        {
        	if ($columns[$key] == 'string')
        	{
        		$cond['ProfileFieldSearch.'.$key.' LIKE'] = '%'.trim($param).'%';
        	}
        	else
        	{
	            if (is_array($param))
	            {
	                foreach ($param as $value)
	                {
	                    $cond['OR'][] = "FIND_IN_SET('". $value ."',ProfileFieldSearch.".$key.") > 0";
	                }
	            }
	            else
	            {
	                $cond['ProfileFieldSearch.'.$key] = $param;
	            }
        	}
        }
        
        return $cond;
    }
}
