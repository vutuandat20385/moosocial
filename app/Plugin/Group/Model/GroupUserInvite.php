<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('GroupAppModel', 'Group.Model');
class GroupUserInvite extends GroupAppModel {

    public $belongsTo = array(
        'Group' => array(
            'className'=> 'Group.Group',
        ));

}
