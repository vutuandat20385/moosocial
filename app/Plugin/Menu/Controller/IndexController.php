<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class IndexController extends MenuAppController
{
    //put your code here

    public function beforeFilter()
    {
        parent::beforeFilter();

        $this->_checkPermission(array('super_admin' => 1));
    }

    public function admin_index()
    {

    }
}
