<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class LanguagesController extends AppController 
{
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_checkPermission( array('super_admin' => true) ); 
    } 
        
    public function admin_index()
    {
        $langs = $this->Language->find( 'all' );
        $installed_langs = array();
        
        foreach ( $langs as $lang )
            $installed_langs[] = $lang['Language']['key'];
        
        $all_langs  = scandir( APP . 'Locale' );      
        $not_installed_langs = array();
            
        foreach ( $all_langs as $lang )
            if ( !in_array( $lang, $installed_langs ) && !in_array($lang, array( '.', '..' ) ) && is_dir(APP . 'Locale' . DS . $lang) && strlen($lang) == 3 )
                $not_installed_langs[] = $lang;
        
        $this->set('languages', $langs);
        $this->set('not_installed_languages', $not_installed_langs);
    }    
    public function updateI18n($key) {
        $this->loadModel('I18nModel');
        $translations = $this->I18nModel->find('all', array('conditions' => array('I18nModel.locale' => 'eng')));
        foreach($translations as $translation) {
            $data[]['I18nModel'] =  array('locale' => $key,
                                        'model' => $translation['I18nModel']['model'],
                                        'foreign_key' => $translation['I18nModel']['foreign_key'],
                                        'field' => $translation['I18nModel']['field'],
                                        'content' => $translation['I18nModel']['content'],
                                        );
        }
        $this->I18nModel->saveMany($data);
    }
    
    public function removeI18n($key){
        $this->loadModel('I18nModel');
        $this->I18nModel->deleteAll(array('I18nModel.locale' => $key));
        
        $this->loadModel("User");
        $this->User->updateAll(array('lang'=>'"eng"'),array('lang'=>$key), false, false);
    }
    public function admin_do_install( $key )
    {
        if ( file_exists( APP . 'Locale' . DS . $key ) )
        {
            if ( $this->Language->save( array( 'name' => ucfirst($key), 'key' => $key ) ) )
            {
                Cache::delete('site_langs');
                Cache::delete('site_rtl');

                $this->Session->setFlash(__('Language has been successfully installed'));
            }
            else
                $this->Session->setFlash( __('An error has occured'), 'default', array( 'class' => 'error-message') );
        }
        else {
            $this->Session->setFlash( __('Cannot read theme info file'), 'default', array( 'class' => 'error-message') );
        }
        // update language i18n
        $this->updateI18n($key);
        // end
        $this->redirect( $this->referer() );
    }
    
    public function admin_do_uninstall( $id )
    {
        $language = $this->Language->findById( $id );
        $this->_checkExistence( $language );
              
        if ( !$language['Language']['key'] != 'eng' )
        {
            $this->Language->delete( $id );   
            $this->removeI18n($language['Language']['key']);
            //remove setting default
            if (Configure::read('Config.language') == $language['Language']['key'])
            {
            	$this->loadModel("Setting");
            	$this->Setting->updateAll(
            			array('Setting.value_actual'=>'"eng"'),
            			array('Setting.name' => 'default_language')
            			);
            }
            
            Cache::delete('site_langs');
            Cache::delete('site_rtl');

            $this->Session->setFlash(__('Language has been successfully uninstalled'));
        }
        else
            $this->Session->setFlash( __('Core language cannot be uninstalled'), 'default', array( 'class' => 'error-message') );
        
        $this->redirect( $this->referer() );
    }

    public function admin_ajax_edit( $id = null )
    {
        $language = $this->Language->findById( $id );
        $this->_checkExistence($language);        
      
        $this->set('language', $language);         
    }

    public function admin_ajax_save()
    {    
        $this->autoRender = false;
        $this->Language->id = $this->request->data['id'];
        
        $this->Language->set( $this->request->data );
        $this->_validateData( $this->Language );
        
        $this->Language->save( $this->request->data );     
        Cache::delete('site_langs');
        Cache::delete('site_rtl');

        $this->Session->setFlash(__('Language has been successfully updated'));
        
        $response['result'] = 1;
        echo json_encode($response);
    }
}
    