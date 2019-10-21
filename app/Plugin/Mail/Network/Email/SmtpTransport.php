<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('AbstractTransport', 'Network/Email');
require_once APP_PATH.DS.'Plugin'.DS.'Mail'.DS.'Lib'.DS.'swiftmailer'.DS.'swift_required.php';
class SmtpTransport extends AbstractTransport {
	protected $_mailer = null;
	public function getMailer($config = array())
	{
		if (!$this->_mailer)
		{
			$transporter = Swift_SmtpTransport::newInstance($config['mail_smtp_host'], $config['mail_smtp_port'], $config['mail_smtp_ssl'])
						  ->setUsername($config['mail_smtp_username'])
						  ->setPassword($config['mail_smtp_password']);
			$this->_mailer = Swift_Mailer::newInstance($transporter);
		}
		return $this->_mailer;
	}
	public function send(CakeEmail $email)
	{
		$mailer = $this->getMailer($email->config());
		
		$message = Swift_Message::newInstance($email->subject());
		$message->setFrom($email->from(),$email->sender());
		$message->setTo($email->to());
		$message->setBody($email->message('html'),'text/html');
		
		$headers = $email->getHeaders();
		if (isset($headers['Message-ID']))
		{
			$msgId = $message->getHeaders()->get('Message-ID');
			$id = $headers['Message-ID'];
			$id = str_replace('<','',$id);
			$id = str_replace('>','',$id);
			$msgId->setId($id);			
		}
		
		$attachments = $email->attachments();
		
		foreach ($attachments as $filename => $fileInfo) {
			if ($fileInfo['data'])
			{
				$message->attach(
				  Swift_Attachment::newInstance($fileInfo['data'],$filename,$fileInfo['mimetype'])
				);
			}
			else
			{
				$message->attach(
				  Swift_Attachment::fromPath($fileInfo['file'])->setFilename($filename),
				  $fileInfo['mimetype']
				);
			}
		}
		
		$result = $mailer->send($message);
	}
}
