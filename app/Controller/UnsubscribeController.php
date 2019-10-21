<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class UnsubscribeController extends AppController {
	public $check_subscription = true;
	public $check_force_login = true;
	
	public function index()
	{
		$q = $this->request->query('q');
		$e = $this->request->query('e');
		
		if (!$q || !$e)
		{
			$this->Session->setFlash(__("We can't find you in our system"), 'default', array('class' => 'error-message'));
			return $this->redirect('/');
		}
		
		$md5 = md5($e.Configure::read('Security.salt'));
		
		if ($md5 != $q)
		{
			$this->Session->setFlash(__("We can't find you in our system"), 'default', array('class' => 'error-message'));
			return $this->redirect('/');
		}
		
		$this->loadModel("UserUnsubscribe");
		$email = $this->UserUnsubscribe->findByEmail($e);
		if (!$email)
		{
			$this->UserUnsubscribe->clear();
			$this->UserUnsubscribe->save(array('email'=>$e));
		}
		
		$this->Session->setFlash(__('You have successfully unsubscribed.'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
		$this->redirect('/');
	}
}