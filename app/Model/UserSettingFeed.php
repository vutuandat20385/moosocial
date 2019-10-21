<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');
class UserSettingFeed extends AppModel
{
    public function getListTypeUnActive()
    {
        $result = Cache::read("user_setting_feed");
        if (!$result)
        {
            $items = $this->find('all',array(
                'conditions' => array('active'=>false)
            ));

            $result = array();
            foreach ($items as $item)
            {
                $result[] = $item['UserSettingFeed']['type'];
            }
            Cache::write("user_setting_feed",$result);
        }

        return $result;
    }
}
 