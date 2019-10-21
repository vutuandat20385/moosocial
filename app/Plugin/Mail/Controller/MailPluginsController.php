<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MailPluginsController extends MailAppController{
	public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_checkPermission( array('super_admin' => true) ); 
    } 
    
    public function admin_index($language = null,$mailtemplate_id = null)
    {
    	$this->loadModel('Mail.Mailtemplate');
    	$langs = $this->Language->find( 'all' );
    	
    	if (!$language)
    	{
    		foreach ($langs as $lang)
    		{
    			$language = $lang['Language']['key'];
    			break;
    		}
    	}
        $this->set('languages', $langs);
        $this->set('language', $language);
        $this->Mailtemplate->locale = $language;
        
        $templetes = $this->Mailtemplate->find('all');
        $this->set('templetes',$templetes);   
    	$templete = null;     
        if ($mailtemplate_id)
        {
        	$this->Mailtemplate->locale = $language;
        	$templete = $this->Mailtemplate->findById($mailtemplate_id);
        }        
        if ($this->request->is('post')) {
        	if ($templete)
        	{
        		$this->Mailtemplate->id = $mailtemplate_id;
                $this->Mailtemplate->save($this->request->data);
				Cache::delete($this->MooMail->buidKey($templete['Mailtemplate']['type'],$language),'mail');
                $this->Session->setFlash(__('Your changes have been saved.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        		return $this->redirect('/admin/mail/mail_plugins/index/'.$language.'/'.$mailtemplate_id);
        	}
        }

        $this->set('templete',$templete);    
        $this->set('title_for_layout', __('Mails'));
    }
}