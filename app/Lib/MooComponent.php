<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooComponent
{
   	protected static $_values = array();
   	
   	public static function register($key,$settings = array())
   	{
   		self::$_values[$key] = $settings;
   	}
   	
   	public static function getAll()
   	{
   		return self::$_values;
   	}
}