<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ThemesController extends AppController 
{
    public $helpers  = array('Form','Html');
	public function beforeFilter()
	{
		parent::beforeFilter();
        $this->_checkPermission(array('super_admin' => 1));
	} 
		
	public function admin_index()
	{
		$themes = $this->Theme->find( 'all' );
        $installed_themes = array();
        
        foreach ( $themes as $theme )
            $installed_themes[] = $theme['Theme']['key'];
		
		$all_themes  = scandir( WWW_ROOT . DS . 'theme' );
        $not_installed_themes = array();
		foreach ( $all_themes as $theme )
			if ( !in_array( $theme, $installed_themes ) && !in_array($theme, array( '.', '..', 'index.html', 'empty','adm','install','mooApp' ) ) )
				$not_installed_themes[] = $theme;
		
		$this->set('themes', $themes);
		$this->set('not_installed_themes', $not_installed_themes);
                $default_theme = 'default';
                $mSetting = MooCore::getInstance()->getModel('Setting');
                $setting = $mSetting->findByName('default_theme');
                if ($setting)
                {
                    $multiValue = json_decode($setting['Setting']['value_actual'], true);
                    foreach($multiValue as $k => $multi)
                    {
                        if($multiValue[$k]['select'] == 1){
                            $default_theme = $multiValue[$k]['value'];
                            break;
                        }                             
                    }               
                }
                $this->set('default_theme', $default_theme);
	}
	
	public function admin_setting( $id = null )
	{
		$this->set('title_for_layout', __('Themes'));
		if (empty( $id ) )
		{
                    $this->_showError('No theme for setting');
                }
		
                $theme = $this->Theme->findById( $id );

			
                if(!empty($theme)){
                    $custom_css = $theme['Theme']['custom_css'];
                    
                    if(!empty($custom_css)){
                        $custom_css_arr = json_decode($custom_css, true);                      
                        $this->set('custom_css_arr', $custom_css_arr);
                    }
                }
					                		
		$this->set('theme', $theme);
	}
        
        public function admin_save_custom_css()
	{
                $this->autoRender = false;
		$data = $this->request->data;
                if(empty($data['Theme']['theme_id'])){
                    return false;
                }
                                            
                $id = $data['Theme']['theme_id'];
		$custom_css_enable = $data['Theme']['custom_css_enable'];
                $apply_to_landing_page = $data['Theme']['apply_to_landing_page'];
                
                $theme = $this->Theme->findById( $id );
                $this->Theme->id = $id;

                $header_background_image = '';
                if(isset($data['header_background_image'])){
                    $header_background_image = $data['header_background_image'];
                }
                
                if (isset($_FILES['Filedata']) && !empty($_FILES['Filedata']['name'])){
                    App::uses('Sanitize', 'Utility');
                    $current_custom_css = json_decode($theme['Theme']['custom_css'], true);
                    
                    if(isset($current_custom_css['header_background_image']) && !empty($current_custom_css['header_background_image'])){
                        $curBackImage = $current_custom_css['header_background_image'];       
                        // remove background image
                        if ($curBackImage && file_exists(WWW_ROOT . $curBackImage)){
                            unlink(WWW_ROOT . $curBackImage);
                        }
                    }
                               
                    if ( isset($_FILES['Filedata']) && is_uploaded_file($_FILES['Filedata']['tmp_name']) )
                    {
                        @ini_set('memory_limit', '500M');
                        $maxFileSize = MooCore::getInstance()->_getMaxFileSize();
                        App::import('Vendor', 'secureFileUpload');
                        $secureUpload = new SecureImageUpload(
                            array(
                               'fileKeyName' =>  'Filedata',
                                'path'=>WWW_ROOT.'uploads' . DS,
                                'whitelist'=>array('extensions'=>array('jpg','jpeg','gif','png','JPG','JPEG','GIF','PNG'),'type'=>array('image/png', 'image/JPG', 'image/jpeg', 'image/gif'),),
                                'maxSize' => $maxFileSize,
                                'scaleUp'=>true,
                            )
                        );
                        if($secureUpload->execute()){
                            $header_background_image = 'uploads/'. $secureUpload->getFileName();
                        }else{                        
                            $this->Session->setFlash(__($secureUpload->getMessage()), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                            $this->redirect( '/admin/themes/setting/' . $theme['Theme']['id']);
                        }
                            }
                }
                                
                $save_data = array();
                $save_data['custom_css_enable'] = $custom_css_enable;
                
                unset($data['Theme']);
                foreach ($data as $k=>$v) {
                    if($v == '#'){
                        $data[$k] = '';
                    }
                }
                $data['apply_to_landing_page'] = $apply_to_landing_page;
                $data['header_background_image'] = $header_background_image;
                
               
                $save_data['custom_css'] = json_encode($data);
                
                $this->Theme->set($save_data);
                $this->Theme->save();
                Cache::delete('theme_custom_enable_'.$theme['Theme']['key']);
                
                if($custom_css_enable == 1){
                    // Write to CSS custom file
                    $file_content = $this->Theme->getCustomCss($data);
                    $path = WWW_ROOT . DS . 'theme' . DS . $theme['Theme']['key'] . DS . 'css' . DS . 'theme-setting.css';
                    file_put_contents( $path, $file_content );
                }                         
                
                $this->Session->setFlash( __('Successfully saved.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
                $this->redirect( '/admin/themes/setting/' . $theme['Theme']['id']);
	}
        
        public function admin_ajax_reset_header_image() {
            $this->autoRender = false;

            if (empty($this->data['theme_id'])) {
               return false;
            }
            $id = $this->data['theme_id'];
                
            $theme = $this->Theme->findById( $id );
           
            if(!empty($theme['Theme']['custom_css'])){
                $this->Theme->id = $id;
                $current_custom_css = json_decode($theme['Theme']['custom_css'], true);
                if(isset($current_custom_css['header_background_image']) && !empty($current_custom_css['header_background_image'])){
                    $curBackImage = $current_custom_css['header_background_image'];       
                        // remove background image
                    if ($curBackImage && file_exists(WWW_ROOT . $curBackImage)){
                        unlink(WWW_ROOT . $curBackImage);
                    }
                }
                
                $current_custom_css['header_background_image'] = '';
                unset($theme['Theme']['id']);
                $theme['Theme']['custom_css'] = json_encode($current_custom_css);
                $this->Theme->set($theme['Theme']);
                $this->Theme->save();
                
                
            }
            $response['result'] = 1;
            echo json_encode($response);
        }
        
        public function admin_ajax_change_default_theme(){
            $this->autoRender = false;

            if (empty($this->data['key'])) {
               return false;
            }
            
            $this->loadModel('Setting');
            $setting = $this->Setting->findByName('default_theme');
            if ($setting){
                $multiValue = json_decode($setting['Setting']['value_actual'], true);
                foreach($multiValue as $k => $multi){
                    if($multi['value'] == $this->data['key']){
                        $multiValue[$k]['select'] = 1;
                    } else {
                        $multiValue[$k]['select'] = 0;
                    }
                }    
                $values['value_actual'] = json_encode($multiValue);
                $this->Setting->id = $setting['Setting']['id'];
                $this->Setting->save($values);
            }
            $response['result'] = 1;
            echo json_encode($response);
        }
        
        public function admin_editor( $id = null )
	{
		$this->set('title_for_layout', __('Themes'));
		if ( !empty( $id ) )
		{
			$theme = $this->Theme->findById( $id );
			
			$view_path = APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($theme['Theme']['key']);
			$css_path = WWW_ROOT . DS . 'theme' . DS . $theme['Theme']['key'] . DS . 'css';
			
			// get css files	
			$css_files  = scandir( $css_path );
			
			foreach ( $css_files as $key => $val )
			if ( in_array($val, array( '.', '..', 'index.html' ) ) )
				unset( $css_files[$key] );
			
			// get view files		
			$view_files  = scandir( $view_path );	

			// get theme info
			$content = file_get_contents( WWW_ROOT . DS . 'theme' . DS . $theme['Theme']['key'] . DS . 'info.xml' );
			$info = new SimpleXMLElement($content);
			
			$this->set('css_files', $css_files);
			$this->set('info', $info);
		}
		else 
		{
			$theme['Theme']['key'] = '';
			$theme['Theme']['name'] = 'mooSocial Base Theme';
			
			$view_path = APP . 'View';
			
			// get view files		
			$view_files  = scandir( $view_path );	
			
			// get installed themes
			$installed_themes = $this->Theme->find( 'list', array( 'fields' => array( 'Theme.key', 'Theme.name' ) ) );
			
			$this->set('installed_themes', $installed_themes);
		}		
		
		foreach ( $view_files as $key => $val )
			if ( in_array($val, array( '.', '..', 'AdminNotifications', 'Categories', 'Helper', 'Install', 'ProfileFields', 'Scaffolds', 'Settings', 'Themed', 'Themes', 'Tools', 'Upgrade' ) ) )
				unset( $view_files[$key] );
		
		$this->set('theme', $theme);
		$this->set('view_files', $view_files);		
	}
	
        public function admin_elements( $id = null )
	{
		$this->set('title_for_layout', __('Themes'));
		if ( !empty( $id ) )
		{
			$theme = $this->Theme->findById( $id );
			
			$view_path = APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($theme['Theme']['key']);
			$css_path = WWW_ROOT . DS . 'theme' . DS . $theme['Theme']['key'] . DS . 'css';
			
			// get css files	
			$css_files  = scandir( $css_path );
			
			foreach ( $css_files as $key => $val )
			if ( in_array($val, array( '.', '..', 'index.html' ) ) )
				unset( $css_files[$key] );
			
			// get view files		
			$view_files  = scandir( $view_path );	

			// get theme info
			$content = file_get_contents( WWW_ROOT . DS . 'theme' . DS . $theme['Theme']['key'] . DS . 'info.xml' );
			$info = new SimpleXMLElement($content);
			
			$this->set('css_files', $css_files);
			$this->set('info', $info);
		}
		else 
		{
			$theme['Theme']['key'] = '';
			$theme['Theme']['name'] = 'mooSocial Base Theme';
			
			$view_path = APP . 'View';
			
			// get view files		
			$view_files  = scandir( $view_path );	
			
			// get installed themes
			$installed_themes = $this->Theme->find( 'list', array( 'fields' => array( 'Theme.key', 'Theme.name' ) ) );
			
			$this->set('installed_themes', $installed_themes);
		}		
		
		foreach ( $view_files as $key => $val )
			if ( in_array($val, array( '.', '..', 'AdminNotifications', 'Categories', 'Helper', 'Install', 'ProfileFields', 'Scaffolds', 'Settings', 'Themed', 'Themes', 'Tools', 'Upgrade' ) ) )
				unset( $view_files[$key] );
		
		$this->set('theme', $theme);
		$this->set('view_files', $view_files);		
	}
        
	public function admin_ajax_open_file()
	{
		$this->autoRender = false;
		
		$key = $this->request->data['key'];
		$path = $this->request->data['path'];
		
		switch ( $this->request->data['type'] )
		{
			case 'css':			
				if ( !empty( $key ) )	
					$content = file_get_contents( WWW_ROOT . DS . 'theme' . DS . $key . DS . 'css' . DS . $path );
				else
					$content = file_get_contents( WWW_ROOT . DS . 'css' . DS . $path );				
					
				echo $content;				
				break;
				
			case 'view':	
				if ( !empty( $key ) )				
					$content = file_get_contents( APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($key) . DS . $path );
				else 
					$content = file_get_contents( APP . 'View' . DS . $path );
						
				echo $content;				
				break;
		}
	}
	
	public function admin_ajax_open_folder()
	{		
		$key = $this->request->data['key'];
		$path = $this->request->data['path'];
		
		switch ( $this->request->data['type'] )
		{
			case 'css':			
				if ( !empty( $key ) )		
					$files  = scandir( WWW_ROOT . DS . 'theme' . DS . $key . DS . 'css' . DS . $path );
				else
					$files  = scandir( WWW_ROOT . DS . 'css' . DS . $path );
								
				break;
				
			case 'view':		
				if ( !empty( $key ) )		
					$files  = scandir( APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($key) . DS . $path );
				else
					$files  = scandir( APP . 'View' . DS . $path );
								
				break;
		}
		
		foreach ( $files as $key => $val )
			if ( in_array($val, array( '.', '..', 'index.html', 'empty' ) ) || strpos( $val, 'admin_' ) !== false )
				unset( $files[$key] );
		
		$this->set('files', $files);
		$this->set('path', $path);
		$this->set('type', $this->request->data['type']);
		
		$this->render('/Elements/misc/themed_files');
	}
	
	public function admin_ajax_save_file()
	{
		$this->autoRender = false;
		
		$key = $this->request->data['key'];
		$path = $this->request->data['path'];
		$content = $this->request->data['content'];
		
		switch ( $this->request->data['type'] )
		{
			case 'css':			
				if ( !empty( $key ) )	
					file_put_contents( WWW_ROOT . DS . 'theme' . DS . $key . DS . 'css' . DS . $path, $content );
				else
					file_put_contents( WWW_ROOT . DS . 'css' . DS . $path, $content );				
									
				break;
				
			case 'view':	
				if ( !empty( $key ) )				
					file_put_contents( APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($key) . DS . $path, $content );
				else 
					file_put_contents( APP . 'View' . DS . $path, $content );
									
				break;
		}
	}
	
	public function admin_ajax_create()	
	{
		$installed_themes = $this->Theme->find( 'list', array( 'fields' => array( 'Theme.key', 'Theme.name' ) ) );
		
		$this->set('installed_themes', $installed_themes);
	}
	
	public function admin_ajax_save()
	{
		$this->autoRender = false;	
		$key = $this->request->data['key'];
		$theme = $this->request->data['theme'];

		$this->Theme->set( $this->request->data );
		$this->_validateData( $this->Theme );
		
		if ( file_exists( APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($key) ) || file_exists( WWW_ROOT . DS . 'theme' . DS . $key ) )
		{
			$this->_jsonError($key . ' folder already exists');
			die();
		}
		
			
		if ( $this->Theme->save() )
		{				
			// create folders
			mkdir( APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($key), 0755 );
			mkdir( WWW_ROOT . DS . 'theme' . DS . $key, 0755 );
			
			// copy folders
			App::uses('Folder', 'Utility');
			
			$dir = new Folder( APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($theme) );
			$dir->copy( APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($key) );
			
			$dir = new Folder( WWW_ROOT . DS . 'theme' . DS . $theme );
			$dir->copy( WWW_ROOT . DS . 'theme' . DS . $key );
			
			// create xml file
			$content = '<?xml version="1.0" encoding="utf-8"?>
<info>
	<name>' . $this->request->data['name'] . '</name>
	<key>' . $this->request->data['key'] . '</key>
	<version>' . $this->request->data['version'] . '</version>
	<description>' . $this->request->data['description'] . '</description>
	<author>' . $this->request->data['author'] . '</author>
	<website>' . $this->request->data['website'] . '</website>
</info>';
		
			file_put_contents(WWW_ROOT . DS . 'theme' . DS . $key . DS . 'info.xml', $content);
            
            // delete cache file
            Cache::delete('site_themes');
			
            $response['result'] = 1;
			$response['id'] = $this->Theme->id;

            // update theme setting select
            $this->loadModel('Setting');
            $this->loadModel('Theme');
            $themes = $this->Theme->find('all');
            $data = array();
            foreach ($themes as $item){
                $data[] = array(
                    'name' => $item['Theme']['name'],
                    'value' => $item['Theme']['key'],
                    'select' => $item['Theme']['core']
                );
            }

            $this->Setting->updateAll(array('Setting.value_actual' => "'" . json_encode($data) . "'") , array('Setting.name' => 'default_theme'));

            echo json_encode($response);
		}
	}
	
	public function admin_do_download( $key )
	{
		$zip = new ZipArchive;
		
		if ( $zip->open( WWW_ROOT . DS . 'uploads' . DS . 'tmp' . DS . $key . '.zip', ZipArchive::CREATE ) === TRUE ) 
		{
		    addDir( WWW_ROOT . DS . 'theme' . DS . $key, $zip, 'webroot' . DS . 'theme' . DS . $key );
			addDir( APP . 'View' . DS . 'Themed' . DS . Inflector::camelize($key), $zip, 'View' . DS . 'Themed' . DS . Inflector::camelize($key) );
			
		    if ( !$zip->close() )
                $this->_showError('Cannot create zip file');
			
			$this->redirect( '/uploads/tmp/' . $key . '.zip' );
		}
        else
            $this->_showError('Cannot create zip file');
	}
	
	public function admin_ajax_copy()
	{
		$this->autoRender = false;
		
		$key = Inflector::camelize($this->request->data['key']);
		$path = $this->request->data['path'];
		
		$tmp = explode( DS, $path );
		array_pop( $tmp );
		$tmp = implode( DS, $tmp );
		
		if ( !file_exists( APP . 'View' . DS . 'Themed' . DS . $key . DS . $tmp ) )		
			mkdir( APP . 'View' . DS . 'Themed' . DS . $key . DS . $tmp, 0755, true );		
		
		copy( APP . 'View' . DS . $path, APP . 'View' . DS . 'Themed' . DS . $key . DS . $path );		
	}
    
    public function admin_do_install( $key )
    {
        if ( file_exists( WWW_ROOT . DS . 'theme' . DS . $key . DS . 'info.xml' ) )
        {
            $content = file_get_contents( WWW_ROOT . DS . 'theme' . DS . $key . DS . 'info.xml' );
            $info = new SimpleXMLElement($content);
            
            if ( $this->Theme->save( array( 'name' => (String)$info->name, 'key' => (String)$info->key ) ) )
            {
                Cache::delete('site_themes');
                
                $this->Session->setFlash(__('Theme has been successfully installed'));
            }
            else
                $this->Session->setFlash(__('An error has occured'), 'default', array( 'class' => 'error-message') );
        }
        else
            $this->Session->setFlash( __('Cannot read theme info file'), 'default', array( 'class' => 'error-message') );
        
        // update theme setting select
        
        $this->loadModel('Setting');
        $this->loadModel('Theme');
        $themes = $this->Theme->find('all');
        $data = array();
        foreach ($themes as $item){
            $data[] = array(
                'name' => $item['Theme']['name'],
                'value' => $item['Theme']['key'],
                'select' => $item['Theme']['core']
            );
        }
        
        $this->Setting->updateAll(array('Setting.value_actual' => "'" . json_encode($data) . "'") , array('Setting.name' => 'default_theme'));
         
        
        $this->redirect( $this->referer() );
    }
    
    public function admin_do_uninstall( $id )
    {
        $theme = $this->Theme->findById( $id );
        $this->_checkExistence( $theme );
        
        if ( !$theme['Theme']['core'] )
        {
            $this->Theme->delete( $id );   
            Cache::delete('site_themes');
                     
            $this->Session->setFlash(__('Theme has been successfully uninstalled'));
        }
        else
            $this->Session->setFlash( __('Core theme cannot be uninstalled'), 'default', array( 'class' => 'error-message') );
        
        
        // update theme setting select
        $this->loadModel('Setting');
        $this->loadModel('Theme');
        $themes = $this->Theme->find('all');
        $data = array();
        foreach ($themes as $item){
            $data[] = array(
                'name' => $item['Theme']['name'],
                'value' => $item['Theme']['key'],
                'select' => $item['Theme']['core']
            );
        }
        
        $this->Setting->updateAll(array('Setting.value_actual' => "'" . json_encode($data) . "'") , array('Setting.name' => 'default_theme'));
        
        $this->redirect( $this->referer() );
    }
}
	
