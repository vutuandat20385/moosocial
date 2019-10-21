<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('EventAppModel', 'Event.Model');
class EventUserInvite extends EventAppModel {

    public $belongsTo = array(
        'Event' => array(
            'className'=> 'Event.Event',
        ));

}
