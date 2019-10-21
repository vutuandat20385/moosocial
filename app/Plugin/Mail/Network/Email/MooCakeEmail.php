<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEmail', 'Network/Email');
class MooCakeEmail extends CakeEmail
{
	public function reset()
	{
		$this->_to = array();
		$this->_replyTo = array();
		$this->_readReceipt = array();
		$this->_returnPath = array();
		$this->_cc = array();
		$this->_bcc = array();
		$this->_messageId = true;
		$this->_subject = '';
		$this->_headers = array();
		$this->_layout = 'default';
		$this->_template = '';
		$this->_viewRender = 'View';
		$this->_viewVars = array();
		$this->_theme = null;
		$this->_helpers = array('Html');
		$this->_textMessage = '';
		$this->_htmlMessage = '';
		$this->_message = '';
		$this->_emailFormat = 'text';
		$this->_transportName = 'Mail';
		//$this->charset = 'utf-8';
		$this->headerCharset = null;
		$this->_attachments = array();
		$this->_emailPattern = self::EMAIL_PATTERN;
		return $this;
	}

    protected function _encode($text) {
        //fix header
        $return = parent::_encode($text);
        if ($this->headerCharset == 'UTF-8') {
            $return = '=?'.$this->headerCharset.'?B?'.base64_encode($text).'?=';
        }
        return $return;
    }
}