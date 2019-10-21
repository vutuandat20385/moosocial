<?php
	MooCache::getInstance()->setCache('mail', array('groups' => array('mail')));
	MooComponent::register('Mail.MooMail'); 
?>