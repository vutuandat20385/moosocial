<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');
class UserFollow extends AppModel
{
    public $belongsTo = array( 'User'  =>
            array('className' => 'User',
                'className' => 'User',
                'foreignKey' => 'user_follow_id')
    );

    public function add($user_id,$user_follow_id)
    {
        $row = $this->find('first',array('conditions'=>array(
            'UserFollow.user_id' => $user_id,
            'UserFollow.user_follow_id' => $user_follow_id
        )));

        if (!$row)
        {
            $this->clear();
            $this->save(array(
                'user_id' => $user_id,
                'user_follow_id' => $user_follow_id
            ));
        }
    }

    public function checkFollow($user_id,$user_follow_id)
    {
        return $this->find('first',array('conditions'=>array(
            'UserFollow.user_id' => $user_id,
            'UserFollow.user_follow_id' => $user_follow_id
        )));
    }
}
 