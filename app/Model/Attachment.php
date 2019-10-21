<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class Attachment extends AppModel 
{
	public function getAttachments( $plugin_id, $target_id )
	{
		$attachments = $this->find('all', array('conditions' => array('plugin_id' => $plugin_id, 'target_id' => $target_id)));
		
		return $attachments;
	}
		
	public function deleteAttachment( $attachment )
	{
		if ( file_exists(WWW_ROOT . 'uploads' . DS . 'attachments' . DS . $attachment['Attachment']['filename']) )
			unlink(WWW_ROOT . 'uploads' . DS . 'attachments' . DS . $attachment['Attachment']['filename']);
			
		$this->delete( $attachment['Attachment']['id'] );
	}
}
 