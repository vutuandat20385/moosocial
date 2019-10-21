<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class SubscriptionCompare extends SubscriptionAppModel
{
	public $actsAs = array(
		'Translate' => array(
			'compare_name' => 'compare_nameTranslation',
			'compare_value' => 'compare_valueTranslation'
		)
	);
}
