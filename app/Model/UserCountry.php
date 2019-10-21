<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');
class UserCountry extends AppModel
{
    public $belongsTo = array(
        'Country' => array(
            'className' => 'Country',
            'foreignKey' => 'country_id'
        ),
        'State' => array(
            'className' => 'State',
            'foreignKey' => 'state_id'
        ),
    );
    protected $_users = array();
    public function updateData($uid,$data)
    {
        $data['user_id'] = $uid;
        $row = $this->find('first',array(
            'conditions'=>array('user_id' => $uid

            )
        ));
        if (isset($data['id']))
        	unset($data['id']);
        if ($row)
        {
            $this->id = $row['UserCountry']['id'];
            $this->save($data);
        }
        else
        {
            $this->clear();
            $this->save($data);
        }
    }

    public function getUserCountryByUser($uid)
    {
        if (isset($this->_users[$uid]))
            return $this->_users[$uid];

        $row = $this->find('first',array(
            'conditions'=>array('user_id' => $uid

            )
        ));
        $this->_users[$uid] = $row;

        return $row;
    }
}
 