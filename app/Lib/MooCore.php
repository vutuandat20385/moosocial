<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('AppController', 'Controller');
App::uses('MobileDetect', 'Lib');
class MooCore
{
   	private $_subject = null;
   	private $_models = array();
	private $_items = array();
	private $_component = array();
	private $_helpers = array();	
	private $_viewer = null;
	private $_moo_view = null;
	private $_plugins = null;
	
	public function getMooView()
	{
		if ($this->_moo_view === null)
		{
			$this->_moo_view = new MooView(new AppController());
		}
		return $this->_moo_view;
	}
   	
	public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new MooCore();
        }

        return $instance;
    }
   	
	public function getSubject()
    {
    	return $this->_subject;
    }
    
    public function setSubject($subject)
    {
    	$this->_subject = $subject;
    }
    
    public function getSubjectType()
    {
    	$subject = $this->getSubject();
    	if ($subject)
    	{
    		return key($subject);
    	}
    	return null;
    }
    
    public function getViewer($idOnly = false)
    {
        if(empty($this->_viewer)) return false;
        if(empty($this->_viewer['User']['id'])) return false;
        if($idOnly) return $this->_viewer['User']['id'];
    	return $this->_viewer;
    }
    
    public function setViewer($user)
    {
    	$this->_viewer = $user;
    }
    
	public function getModel($type, $clear = true)
    {
    	if (!isset($this->_models[$type]))
    	{
	    	list($plugin, $modelClass) = mooPluginSplit($type, true);
	
			$model = ClassRegistry::init(array(
				'class' => $plugin . $modelClass,
			));
			
			$this->_models[$type] = $model;
    	}
    	else
    	{
    		$model = $this->_models[$type];
    	}
		
		if ($model && $clear)
		{
			$model->clear();
		}
		
    	return $model;
    }
    
    public function getComponent($key,$setting = array())
    {
    	if (!isset($this->_component[$key]))
    	{
	    	list($plugin, $name) = pluginSplit($key, true);
			$componentClass = $name . 'Component';
			App::uses($componentClass, $plugin . 'Controller/Component');
			
			$component = new $componentClass(new ComponentCollection(),$setting);
			$this->_component[$key] = $component;
    	}
    	else 
    	{
    		$component = $this->_component[$key];
    	}
    	
    	return $component;
    }
    
    public function getHelper($plugin,$settings = array())
    {
        $helper = null;
        $array  = explode('_', $plugin);
        $plugin_name = $array[0];
        $helper_name = $array[1];

        if (!isset($this->_helpers[$helper_name])) {
            $plugin_helper_name = $helper_name . 'Helper';
            if ($plugin_name == 'Core'){
                App::uses($plugin_helper_name, 'View/Helper');
            }else{
                App::uses($plugin_helper_name, $plugin_name . '.View/Helper');
            }
            if (class_exists($plugin_helper_name)) {
                $helper = new $plugin_helper_name($this->getMooView(), $settings);
                $this->_helpers[$helper_name] = $helper;
            }
        } else {
            $helper = $this->_helpers[$helper_name];
        }
        
        return $helper;
    }
    
	public function getItemByType($type,$id)
    {
    	$model = $this->getModel($type);
    	
    	if (!isset($this->_items[$type][$id]))
    	{
    		$object = $model->findById($id);
    	}
    	else
    	{
    		$object = $this->_items[$type][$id];
    	}
		
		
		return $object;
    }
    
    public function checkShowSubjectActivity($subject)
    {
    	$name = key($subject);
		$show_subject = true;
	    $subject_view = $this->getSubject();
	    if ($subject_view)
	    {
	    	$type = MooCore::getInstance()->getSubjectType();
	    	$show_subject = ($subject[$name]['moo_type'] !=$subject_view[$type]['moo_type']) || ($subject[$name]['id'] != $subject_view[$type]['id']);
	    }
	    
	    return $show_subject;
    }
    
    public function getListPluginEnable()
    {
    	if ($this->_plugins === null)
    	{
	    	$plugins = CakePlugin::loaded();
	    	$tmp = array();
	    	foreach ($plugins as $plugin)
	    	{
	    		$helper = $this->getHelper($plugin.'_'.$plugin);
	    		if ($helper)
	    		{
	    			if (method_exists($helper,'getEnable'))
	    			{
	    				$enable = $helper->getEnable();
	    				if (!$enable)
	    				{
	    					continue;
	    				}
	    			}    			
	    		}
	    		$tmp[] = $plugin;
	    	}
	    	$this->_plugins = $tmp;
    	}
    	
    	return $this->_plugins;
    }
    
    public function checkPermission($cuser,$name)
    {
    	if (!empty($cuser)) {
    		$params = explode(',', $cuser['Role']['params']);
    	} else {
    		$params = Cache::read('guest_role');
    	
    		if (empty($params)) {
    			$roleModel = $this->getModel('Role');
    			$guest_role = $roleModel->findById(ROLE_GUEST);
    	
    			$params = explode(',', $guest_role['Role']['params']);
    			Cache::write('guest_role', $params);
    		}
    	}
    	
    	return in_array($name, $params);
    }
    
    public function exportTranslate($array_message,$path)
    {
    	$output = "# LANGUAGE translation of CakePHP Application\n";
		$output .= "# Copyright YEAR NAME <EMAIL@ADDRESS>\n";
		$output .= "#\n";
		$output .= "#, fuzzy\n";
		$output .= "msgid \"\"\n";
		$output .= "msgstr \"\"\n";
		$output .= "\"Project-Id-Version: PROJECT VERSION\\n\"\n";
		$output .= "\"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\\n\"\n";
		$output .= "\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
		$output .= "\"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\n\"\n";
		$output .= "\"MIME-Version: 1.0\\n\"\n";
		$output .= "\"Content-Type: text/plain; charset=utf-8\\n\"\n";
		$output .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";

		$tmp = array();
    	foreach ($array_message as $message)
    	{
    		$message = str_replace('"', '\"', $message);
    		$sentence = '';
    		$sentence .= "msgid \"{$message}\"\n";
			$sentence .= "msgstr \"{{$message}}\"\n\n";
			$tmp[] = $sentence;
    	}
    	
    	$array_message = $tmp;
    	
    	foreach ($array_message as $header) {
			$output .= $header;
		}
		
		$File = new File($path);
		$File->write($output);
		$File->close();
    }
    
    
    // return full content
    public function getHtmlContent($url) {
        if (filter_var($url, FILTER_VALIDATE_URL) === false){
            return false;
        }
        
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POSTREDIR, 3);
        $data = curl_exec($ch);
        curl_close($ch);
        
        return $data;
    }
    
    // get header
    public function getHeader($url){
        if (filter_var($url, FILTER_VALIDATE_URL) === false){
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $content = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        
        return array(
            'contentType' => $contentType,
            'contentLength' => $contentLength
        );
    }
    
    public function isRecaptchaEnabled(){
        $recaptcha_publickey = Configure::read('core.recaptcha_publickey');
        $recaptcha_privatekey = Configure::read('core.recaptcha_privatekey');
        
        if ( Configure::read('core.recaptcha') && !empty($recaptcha_publickey) && !empty($recaptcha_privatekey) ){
            return true;
        }
        
        return false;
    }
    
    public  function _getMaxFileSize()
    {
    	$max_upload = ini_get('upload_max_filesize');
    	if ($max_upload == -1)
    	{
    		$max_upload = '999999M';
    	}
    	$max_upload = $this->__return_bytes($max_upload);
        //select post limit
        $max_post = ini_get('post_max_size');
        if ($max_post== -1)
        {
        	$max_post= '999999M';
        }
        $max_post = $this->__return_bytes($max_post);
        //select memory limit
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit == -1)
        {
        	$memory_limit = '999999M';
        }
        $memory_limit = $this->__return_bytes($memory_limit);
        
        // get photo max upload size
        $upload_setting = Configure::read('core.photo_max_upload_size') . 'M';
        $photo_max_upload_size = $this->__return_bytes($upload_setting);
         
        // return the smallest of them, this defines the real limit
        return min($max_upload, $max_post, $memory_limit, $photo_max_upload_size);
    }
    
    // list of photo extension allowed to uploaded
    public function _getPhotoAllowedExtension(){
        return array('jpg', 'jpeg', 'png', 'gif');
    }
    
    // list of video extension allowed to uploaded
    public function _getVideoAllowedExtension(){
        return array('flv', 'mp4', 'wmv', '3gp', 'mov', 'avi');
    }
    
    // list of attachment extension allowed to uploaded
    public function _getFileAllowedExtension(){
        return array('jpg', 'jpeg', 'png', 'gif', 'zip', 'txt', 'pdf', 'doc', 'docx');
    }


    public  function _getMaxVideoUpload()
    { 
    	$max_upload = ini_get('upload_max_filesize');
    	if ($max_upload == -1)
    	{
    		$max_upload = '999999M';
    	}
    	$max_upload = $this->__return_bytes($max_upload);
    	//select post limit
    	$max_post = ini_get('post_max_size');
    	if ($max_post== -1)
    	{
    		$max_post= '999999M';
    	}
    	$max_post = $this->__return_bytes($max_post);
    	//select memory limit
    	$memory_limit = ini_get('memory_limit');
    	if ($memory_limit == -1)
    	{
    		$memory_limit = '999999M';
    	}
    	$memory_limit = $this->__return_bytes($memory_limit);
        
        $uploadEnable = Configure::read('UploadVideo.uploadvideo_enabled');
        if ($uploadEnable){
            // max video upload from setting
            $upload_setting = Configure::read('UploadVideo.video_common_setting_max_upload') . 'M';
            $max_upload_setting = $this->__return_bytes($upload_setting);

            // fix videoMaxUpload
            //return min($max_upload, $max_post, $memory_limit, $max_upload_setting);
            return $max_upload_setting;
        }
        
        
        // return the smallest of them, this defines the real limit
        return min($max_upload, $max_post, $memory_limit);
    }

    private function __return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $number=substr($val,0,-1);
        switch($last)
        {
            case 'g':
                return $number*pow(1024,3);
            case 'm':
                return $number*pow(1024,2);
            case 'k':
                return $number*1024;
            default:
                return $val;
        }


    }
    
    public function isMobile($request)
    {
        $detect = new MobileDetect();
        return $detect->isMobile();
    }
    
    /**
     * Get content from the Url using CURL.
     */
    public function getUrlContent($url, $params = array(), $method = 'POST', $cookie = null)
    {
        $aHost = parse_url($url);
        $sPost = '';
        foreach ($params as $sKey => $sValue){
            $sPost .= '&' . $sKey . '=' . $sValue;
        }
  
        if (extension_loaded('curl') && function_exists('curl_init')){
            $hCurl = curl_init();

            curl_setopt($hCurl, CURLOPT_URL, (($method == 'GET' && !empty($sPost)) ? $url . '?' . ltrim($sPost, '&') : $url));
            curl_setopt($hCurl, CURLOPT_HEADER, false);
            curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($hCurl, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($hCurl, CURLOPT_FOLLOWLOCATION, true);

            if ($method != 'GET'){
                curl_setopt($hCurl, CURLOPT_POST, true);
                curl_setopt($hCurl, CURLOPT_POSTFIELDS, $sPost);
            }
           curl_setopt($hCurl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');

            if (!empty($cookie) && is_array($cookie)){
                $sLine = "\n";
                foreach ($cookie as $sKey => $sValue){
                    if ($sKey == 'PHPSESSID'){
                        continue;
                    }
                    $sLine .= '' . $sKey . '=' . $sValue . '; ';
                }
                $sLine = trim(rtrim($sLine, ';'));
                curl_setopt($hCurl, CURLOPT_COOKIE, $sLine);
            }

            // Run the exec
            $sData = curl_exec($hCurl);
            // Close the curl connection
            $info = curl_getinfo($hCurl);
         //   print_r($sData);die();
            curl_close($hCurl);
            
            if(isset($info['http_code']) && $info['http_code'] == 302 && !empty($info['redirect_url'])){
                return $this->getUrlContent($info['redirect_url'],array(), 'GET', $_SERVER['HTTP_USER_AGENT']);
            }else{
                return trim($sData);
            }

        }

        if ($method == 'GET' && ini_get('allow_url_fopen') && function_exists('file_get_contents'))
        {
            $sData = file_get_contents($url . "?" . ltrim($sPost, '&'));
            return trim($sData);
        }

        if (!isset($sData))
        {
            $hConnection = fsockopen($aHost['host'], 80, $errno, $errstr, 30);
            if (!$hConnection)
            {
                return false;
            }
            else
            {
                if ($method == 'GET')
                {
                    $url = $url . '?' . ltrim($sPost, '&');
                }

                $sSend = "{$method} {$url}  HTTP/1.1\r\n";
                $sSend .= "Host: {$aHost['host']}\r\n";
                $sSend .= "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . " (" . $_SERVER['HTTP_USER_AGENT'] . ")\r\n";
                $sSend .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $sSend .= "Content-Length: " . strlen($sPost) . "\r\n";
                $sSend .= "Connection: close\r\n\r\n";
                $sSend .= $sPost;
                fwrite($hConnection, $sSend);
                $sData = '';
                while (!feof($hConnection))
                {
                    $sData .= fgets($hConnection, 128);
                }

                $aResponse = preg_split("/\r\n\r\n/", $sData);
                $sHeader = $aResponse[0];
                $sData = $aResponse[1];

                if(!(strpos($sHeader,"Transfer-Encoding: chunked")===false))
                {
                    $aAux = split("\r\n", $sData);
                    for($i=0; $i<count($aAux); $i++)
                    {
                        if($i==0 || ($i%2==0))
                        {
                            $aAux[$i] = '';
                        }
                        $sData = implode("",$aAux);
                    }
                }

                return chop($sData);
            }
        }

        return false;
    }
    
    public function getTooltipClass($blockedUsers = null, $uid){
        $moo_tooltip_class = '';
        if (empty($blockedUsers) || !in_array($uid, $blockedUsers)) {
            $moo_tooltip_class = 'moocore_tooltip_link';
        }
        return $moo_tooltip_class;
    }
    
    public function checkViewPermission($privacy, $owner, $areFriends = null)
    {
        $uid = $this->getViewer(true);
        if ($uid == $owner) // owner
        {
            return true;
        }

        $viewer = $this->getViewer();
        if (!empty($viewer) && $viewer['Role']['is_admin']) {
            return true;
        }

        if(empty($privacy)){
            $privacy = PRIVACY_ME;
            $user_modal = $this->getModel('User');
            $owner_user = $user_modal->findById($owner);
            if($owner_user){
                $privacy = $owner_user['User']['privacy'];
            }
        }
        
        $per = false;
        switch ($privacy) {
            case PRIVACY_EVERYONE:
                $per = true;
                if(!empty($uid)){
                    $block_modal = $this->getModel('UserBlock');
                    $user_blocks = $block_modal->getBlockedUsers($owner);     
                    if (in_array($uid, $user_blocks)) {
                        $per = false;
                    }
                }
                break;
            case PRIVACY_FRIENDS:
                if (empty($areFriends)) {
                    if (!empty($uid)) //  check if user is a friend
                    {                   
                        $friend_modal = $this->getModel('Friend');
                        $per = $friend_modal->areFriends($uid, $owner);
                    }
                }else{
                    $per = $areFriends;
                }

                break;

            case PRIVACY_ME:
                break;
        }
        
        return $per;
    }
}