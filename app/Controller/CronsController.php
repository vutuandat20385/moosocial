<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class CronsController extends AppController
{
	public function beforeFilter() {}	
	
	public function run()
	{
		$this->autoRender = false;
		$this->response->type('image/jpg');	
			
		$this->loadModel('MailQueue');
	
		$mails = $this->MailQueue->findAllByStatus(0);
		
		foreach ( $mails as $mail)
		{
			$this->_sendEmail( $mail['MailQueue']['email'], 
							   $mail['MailQueue']['subject'], 
							   'notification', 
							   array( 'text' 	  => $mail['MailQueue']['subject'],
							   		  'comment'   => $mail['MailQueue']['comment'],
									  'url' 	  => $mail['MailQueue']['url'],
							   		  'view_text' => $mail['MailQueue']['view_text']
							)	);
							
			$this->MailQueue->id = $mail['MailQueue']['id'];
			$this->MailQueue->saveField( 'status', 1 );
		}
	}

}

