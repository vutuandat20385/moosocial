<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MooTranslateHelper extends AppHelper {
	public function translateText($text,$plugin = '',$second_plugin = '')
	{
		if (!$text)
			return '';
			
		if (strtolower($plugin) == 'core')
		{
			$plugin = '';
		}
		
		$translate = $text;
		if ($plugin)
			$translate = __d(Inflector::underscore($plugin),$text);
		else
			$translate = __($text);
			
		if ($translate != $text)
			return $translate;
			
		if ($second_plugin)
		{
			$translate = __d(Inflector::underscore($second_plugin),$text);
		}
		
		return $translate;
	}
}