<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
 
 App::uses('User', 'Model');
class UserUpgrade extends User
{
	public $useTable = 'users';
    public $belongsTo = array( 'Role' );
}