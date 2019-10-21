<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class MailSettingsController extends MailAppController{
	public $components = array('QuickSettings');
	
	public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_checkPermission( array('super_admin' => true) ); 
    } 
    
    public function admin_index($id = null)
    {
    	$this->QuickSettings->run($this, array("Mail"), $id);
    }
}