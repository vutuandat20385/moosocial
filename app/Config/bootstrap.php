<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */

/**
 * This file is loaded automatically by the app/webroot/index.php file after core.php
 *
 * This file should load/create any application wide configuration settings, such as
 * Caching, Logging, loading additional configuration files.
 *
 * You should also use this file to include any files that provide global functions/constants
 * that your application uses.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Cache Engine Configuration
 * Default settings provided below
 *
 * File storage engine.
 *
 *   Cache::config('default', array(
 *      'engine' => 'File', //[required]
 *      'duration'=> 3600, //[optional]
 *      'probability'=> 100, //[optional]
 *      'path' => CACHE, //[optional] use system tmp directory - remember to use absolute path
 *      'prefix' => 'cake_', //[optional]  prefix every cache file with this string
 *      'lock' => false, //[optional]  use file locking
 *      'serialize' => true, // [optional]
 *      'mask' => 0666, // [optional] permission mask to use when creating cache files
 *  ));
 *
 * APC (http://pecl.php.net/package/APC)
 *
 *   Cache::config('default', array(
 *      'engine' => 'Apc', //[required]
 *      'duration'=> 3600, //[optional]
 *      'probability'=> 100, //[optional]
 *      'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *  ));
 *
 * Xcache (http://xcache.lighttpd.net/)
 *
 *   Cache::config('default', array(
 *      'engine' => 'Xcache', //[required]
 *      'duration'=> 3600, //[optional]
 *      'probability'=> 100, //[optional]
 *      'prefix' => Inflector::slug(APP_DIR) . '_', //[optional] prefix every cache file with this string
 *      'user' => 'user', //user from xcache.admin.user settings
 *      'password' => 'password', //plaintext password (xcache.admin.pass)
 *  ));
 *
 * Memcache (http://memcached.org/)
 *
 *   Cache::config('default', array(
 *      'engine' => 'Memcache', //[required]
 *      'duration'=> 3600, //[optional]
 *      'probability'=> 100, //[optional]
 *      'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *      'servers' => array(
 *          '127.0.0.1:11211' // localhost, default port 11211
 *      ), //[optional]
 *      'persistent' => true, // [optional] set this to false for non-persistent connections
 *      'compress' => false, // [optional] compress data in Memcache (slower, but uses less memory)
 *  ));
 *
 *  Wincache (http://php.net/wincache)
 *
 *   Cache::config('default', array(
 *      'engine' => 'Wincache', //[required]
 *      'duration'=> 3600, //[optional]
 *      'probability'=> 100, //[optional]
 *      'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *  ));
 *
 * Redis (http://http://redis.io/)
 *
 *   Cache::config('default', array(
 *      'engine' => 'Redis', //[required]
 *      'duration'=> 3600, //[optional]
 *      'probability'=> 100, //[optional]
 *      'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *      'server' => '127.0.0.1' // localhost
 *      'port' => 6379 // default port 6379
 *      'timeout' => 0 // timeout in seconds, 0 = unlimited
 *      'persistent' => true, // [optional] set this to false for non-persistent connections
 *  ));
 */
$mooCacheEngine = 'File';// File , APC , Xcache , Memcache , Redis
Cache::config('default', array('engine' => $mooCacheEngine));

/**
 * The settings below can be used to set additional paths to models, views and controllers.
 *
 * App::build(array(
 *     'Model'                     => array('/path/to/models', '/next/path/to/models'),
 *     'Model/Behavior'            => array('/path/to/behaviors', '/next/path/to/behaviors'),
 *     'Model/Datasource'          => array('/path/to/datasources', '/next/path/to/datasources'),
 *     'Model/Datasource/Database' => array('/path/to/databases', '/next/path/to/database'),
 *     'Model/Datasource/Session'  => array('/path/to/sessions', '/next/path/to/sessions'),
 *     'Controller'                => array('/path/to/controllers', '/next/path/to/controllers'),
 *     'Controller/Component'      => array('/path/to/components', '/next/path/to/components'),
 *     'Controller/Component/Auth' => array('/path/to/auths', '/next/path/to/auths'),
 *     'Controller/Component/Acl'  => array('/path/to/acls', '/next/path/to/acls'),
 *     'View'                      => array('/path/to/views', '/next/path/to/views'),
 *     'View/Helper'               => array('/path/to/helpers', '/next/path/to/helpers'),
 *     'Console'                   => array('/path/to/consoles', '/next/path/to/consoles'),
 *     'Console/Command'           => array('/path/to/commands', '/next/path/to/commands'),
 *     'Console/Command/Task'      => array('/path/to/tasks', '/next/path/to/tasks'),
 *     'Lib'                       => array('/path/to/libs', '/next/path/to/libs'),
 *     'Locale'                    => array('/path/to/locales', '/next/path/to/locales'),
 *     'Vendor'                    => array('/path/to/vendors', '/next/path/to/vendors'),
 *     'Plugin'                    => array('/path/to/plugins', '/next/path/to/plugins'),
 * ));
 *
 */
App::build(array('I18n' => array(CAKE.DS.'I18n')),APP::REGISTER);
//App::build(array('Controller/Widgets' => array(APP."Controller/Widgets")),APP::REGISTER);
/**
 * Custom Inflector rules, can be set to correctly pluralize or singularize table, model, controller names or whatever other
 * string is passed to the inflection functions
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */

/**
 * Plugins need to be loaded manually, you can either load them one by one or all of them in a single call
 * Uncomment one of the lines below, as you need. make sure you read the documentation on CakePlugin to use more
 * advanced ways of loading plugins
 *
 * CakePlugin::loadAll(); // Loads all plugins at once
 * CakePlugin::load('DebugKit'); //Loads a single plugin named DebugKit
 *
 */

//CakePlugin::loadAll();

require_once(APP . 'Config' . DS . 'constants.php');
Configure::load('settings', 'default');
Configure::write('App.mooCacheEngine', $mooCacheEngine);

App::uses('MooComponent','Lib');
App::uses('MooCore','Lib');
App::uses('MooPeople','Lib');
App::uses('MooLangISOConvert','Lib');
App::uses('MooSeo','Lib');
App::uses('CakeEventManager', 'Event');
//App::uses('AppExceptionHandler', 'Lib');
App::uses('AppExceptionRenderer', 'Lib/Error');
App::uses('TokenHasExpiredException', 'Lib/Error/Exceptions');
//load plugin here
if(file_exists(PLUGIN_CONFIG_PATH))
{
    $content = file_get_contents(PLUGIN_CONFIG_PATH);
    $xml = new SimpleXMLElement($content);
    $plugins = json_decode(json_encode($xml->plugins), true);

    if(isset($plugins['plugin']) && $plugins['plugin'] != null)
    {
        $plugins = $plugins['plugin'];
        if(isset($plugins[0]))
        {
            $datas = array();
            foreach($plugins as $plugin)
            {
                if(file_exists(APP . 'Plugin' . DS . $plugin['name']) && $plugin['enabled'] == 1)
                {
					CakePlugin::load(array(
                        $plugin['name'] => array('bootstrap' => (bool)$plugin['bootstrap'], 'routes' => (bool)$plugin['routes'])
                    ));
                    App::build(array($plugin['name'].'Plugin' => array(sprintf(PLUGIN_PATH, $plugin['name']))), App::REGISTER);
                }
            }
        }
        else
        {
            if(file_exists(APP . 'Plugin' . DS . $plugins['name']) && $plugins['enabled'] == 1)
            {
                CakePlugin::load(array(
                    $plugins['name'] => array('bootstrap' => (bool)$plugins['bootstrap'], 'routes' => (bool)$plugins['routes'])
                ));
                App::build(array($plugins['name'].'Plugin' => array(sprintf(PLUGIN_PATH, $plugin['name']))), App::REGISTER);
            }
        }
    }
}

CakePlugin::load(array('MooUpload'=>array('bootstrap' => true)));
CakePlugin::load(array('Minify' => array('routes' => true)));
CakePlugin::load(array('Storage' => array('routes' => true,'bootstrap' => true)));

/**
 * You can attach event listeners to the request lifecyle as Dispatcher Filter . By Default CakePHP bundles two filters:
 *
 * - AssetDispatcher filter will serve your asset files (css, images, js, etc) from your themes and plugins
 * - CacheDispatcher filter will read the Cache.check configure variable and try to serve cached content generated from controllers
 *
 * Feel free to remove or add filters as you see fit for your application. A few examples:
 *
 * Configure::write('Dispatcher.filters', array(
 *      'MyCacheFilter', //  will use MyCacheFilter class from the Routing/Filter package in your app.
 *      'MyPlugin.MyFilter', // will use MyFilter class from the Routing/Filter package in MyPlugin plugin.
 *      array('callable' => $aFunction, 'on' => 'before', 'priority' => 9), // A valid PHP callback type to be called on beforeDispatch
 *      array('callable' => $anotherMethod, 'on' => 'after'), // A valid PHP callback type to be called on afterDispatch
 *
 * ));
 */
Configure::write('Dispatcher.filters', array(
    'AssetDispatcher',
    'CacheDispatcher'
));

/**
 * Configures default file logging options
 */
App::uses('CakeLog', 'Log');
CakeLog::config('debug', array(
    'engine' => 'FileLog',
    'types' => array('notice', 'info', 'debug'),
    'file' => 'debug',
));
CakeLog::config('error', array(
    'engine' => 'FileLog',
    'types' => array('warning', 'error', 'critical', 'alert', 'emergency'),
    'file' => 'error',
));
CakeLog::config('error', array(
	'engine' => 'File',
	'types' => array('error'),
	'file' => 'error-'.date('Y-m-d'),
));


App::uses('MooListener','Lib');
CakeEventManager::instance()->attach(new MooListener());

App::uses('MooViewListener','Lib');
CakeEventManager::instance()->attach(new MooViewListener());

App::uses('ActivityListener','Lib');
CakeEventManager::instance()->attach(new ActivityListener());

App::uses('MooApiListener','Lib');
CakeEventManager::instance()->attach(new MooApiListener());

function summary( $str, $chars = 100 )
{
	return substr( strip_tags($str), 0, $chars );
}

function possession( $actor, $owner = null, $is_web = false )
{
	if ( Configure::read('Config.language') != 'eng' )
        return h($owner['name']);
        
    if ( empty( $owner ) || $actor['id'] == $owner['id'] ){
    	$genderTxt = __("his/her");
    	if ($actor['gender'] == 'Male'){
    		$genderTxt = __('his');
    	}else if ($actor['gender'] == 'Female'){
    		$genderTxt = __('her');
    	}
    	return $genderTxt;
    }

    if (!$is_web)
	    return h($owner['name']) . '\'s';
    else
        return '<a href="'.$owner['moo_href'].'">'.h($owner['name']) . '\'s'.'</a>';
}

function cleanJsString( $str )
{
	return addslashes(str_replace( array('"', "\n") , array('', ''), $str));
}

function moo_url_slug($str, $options = array()) {
	// Make sure string is in UTF-8 and strip invalid UTF-8 characters
	$str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());
	
	$defaults = array(
			'delimiter' => '-',
			'limit' => null,
			'lowercase' => true,
			'replacements' => array(),
			'transliterate' => false,
	);
	
	// Merge options
	$options = array_merge($defaults, $options);
	
	$char_map = array(
			// Latin
			'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
			'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
			'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
			'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
			'ß' => 'ss',
			'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
			'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
			'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
			'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
			'ÿ' => 'y',
			// Latin symbols
			'©' => '(c)',
			// Greek
			'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
			'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
			'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
			'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
			'Ϋ' => 'Y',
			'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
			'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
			'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
			'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
			'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
			// Turkish
			'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
			'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',
			// Russian
			'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
			'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
			'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
			'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
			'Я' => 'Ya',
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
			'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
			'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
			'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
			'я' => 'ya',
			// Ukrainian
			'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
			'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
			// Czech
			'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
			'Ž' => 'Z',
			'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
			'ž' => 'z',
			// Polish
			'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
			'Ż' => 'Z',
			'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
			'ż' => 'z',
			// Latvian
			'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
			'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
			'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
			'š' => 's', 'ū' => 'u', 'ž' => 'z'
	);
	
	// Make custom replacements
	$str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
	
	// Transliterate characters to ASCII
	if ($options['transliterate']) {
		$str = str_replace(array_keys($char_map), $char_map, $str);
	}
	
	// Replace non-alphanumeric characters with our delimiter
	$str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
	
	// Remove duplicate delimiters
	$str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);
	
	// Truncate slug to max. characters
	$str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');
	
	// Remove delimiter from ends
	$str = trim($str, $options['delimiter']);
	
	return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}

function seoUrl( $string, $limit = 70 ) 
{    
	return moo_url_slug($string,array('limit'=>$limit,'transliterate'=>true));	
}

function addDir( $path, &$zip, $dest )
{			
    $zip->addEmptyDir( $dest );
    $nodes = glob( $path . DS . '*' );
	
    foreach ($nodes as $node) 
    {
        if ( is_dir( $node ) ) {
            addDir( $node, $zip, $dest . DS . basename( $node ) );
        } else if ( is_file( $node ) )  {
            $zip->addFile( $node, $dest . DS . basename( $node ) );
        }
    }	 
}

function mooPluginSplit($name, $dotAppend = false, $plugin = null)
{
	if (strpos($name, '_') !== false) {
		$parts = explode('_', $name, 2);
		if ($dotAppend) {
			$parts[0] .= '.';
		}
		if (isset($parts[0]) && (strtolower($parts[0]) == 'core' || strtolower($parts[0]) == 'core.'))
			$parts[0] ='';
		$array = array_map('ucname', $parts);
		$array = str_replace("_", "", $array);
		return $array;
	}
	return array($plugin, ucfirst($name));
}

function ucname($string) {
    $string =ucwords(strtolower($string));

    foreach (array('_') as $delimiter) {
      if (strpos($string, $delimiter)!==false) {
        $string =implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
      }
    }
    return $string;
}