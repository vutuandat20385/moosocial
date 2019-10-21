<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class RatingUser extends AppModel{
    public $belongsTo = array(
        'Rating' => array(
            'className' => 'Rating',
            'counterCache' => true,
        )
    );
}