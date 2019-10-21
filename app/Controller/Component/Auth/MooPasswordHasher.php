<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('AbstractPasswordHasher', 'Controller/Component/Auth');

class MooPasswordHasher extends AbstractPasswordHasher {
    public function hash($password, $salt = '') {
        return md5( trim( $password ) . Configure::read('Security.salt').$salt ) ;
    }

    public function check($password , $hashedPassword) {
        $userModel = MooCore::getInstance()->getModel("User");
        $userModel->unbindModel(array('belongsTo'=>'ProfileType'));
        $user = $userModel->findByPassword($hashedPassword);
        $salt = '';
		if (isset($user['User']['salt']))
		{
			$salt = $user['User']['salt'];
		}
        return $hashedPassword === $this->hash($password,$salt);
    }
}