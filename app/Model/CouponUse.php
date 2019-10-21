<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class CouponUse extends AppModel {
	public $belongsTo = array(
		'Coupon' => array(
				'foreignKey' => 'coupon_id'
		),'User');
}
