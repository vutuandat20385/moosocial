<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class CurrenciesController extends BillingAppController 
{
    public $components = array('Paginator');

    public function __construct($request = null, $response = null) 
    {
        parent::__construct($request, $response);
        $this->url = '/admin/billing/currencies/';
        $this->url_create = $this->url.'create/';
        $this->url_delete = $this->url.'delete/';
        $this->set('url', $this->url);
        $this->set('url_create', $this->url_create);
        $this->set('url_delete', $this->url_delete);
    }
    
    public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_checkPermission(array('super_admin' => 1));
	}
    
    public function admin_index()
    {
        $this->Paginator->settings = array(
            'limit' => 10,
            'order' => array(
                'id' => 'DESC'
            )
        );
        $currencies = $this->Paginator->paginate('Currency');
        $this->set('currencies', $currencies);
    }
    
    public function admin_create($id = null)
    {
        if((int)$id > 0 && !$this->Currency->hasAny(array('id' => (int)$id)))
        {
            $this->Session->setFlash(__('This currency does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
            $this->redirect($this->referer());
        }
        else 
        {
            if(!empty($id))
            {
                $currency = $this->Currency->findById($id);
            }
            else
            {
                $currency = $this->Currency->initFields();
            }
            
            $this->set('currency', $currency['Currency']);
        }
    }

    public function admin_save()
    {
        if ($this->request->is('post')) 
        {
            if($this->request->data['Currency']['id'] > 0)
            {
                $this->url_create .= $this->request->data['Currency']['id'];
            }

            //validate
            $this->Currency->set($this->request->data);
            if (!$this->Currency->validates())
            {
                $errors = $this->Currency->validationErrors;
                $this->Session->setFlash(current(current($errors)), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                
                $this->redirect($this->url_create);
            }

            //save data
            $this->Currency->id = $this->request->data['Currency']['id'];
            if ($this->Currency->save($this->request->data)) 
            {
                $this->Session->setFlash(__('Successfully saved.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                $this->redirect($this->url);
            }
            $this->Session->setFlash(__('Something went wrong! Please try again..'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
            $this->redirect($this->url_create);
        }
        else 
        {
            $this->redirect($this->url_create);
        }
    }
    
    public function admin_do_active($id)
    {
        $this->do_active($id, 1, 'is_active');
    }
    
    public function admin_do_unactive($id)
    {
        $this->do_active($id, 0, 'is_active');
    }
    
    public function admin_do_default($id)
    {
        $this->do_default($id);
    }

    private function do_active($id, $value = 1, $task)
    {
        if(!$this->Currency->hasAny(array('id' => (int)$id)))
        {
            $this->Session->setFlash(__('This currency does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $this->Currency->id = $id;
            $this->Currency->save(array($task => $value));
            $this->Session->setFlash(__('Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        $this->redirect($this->referer());
    }
    
    private function do_default($id)
    {
        if(!$this->Currency->hasAny(array('id' => (int)$id)))
        {
            $this->Session->setFlash(__('This currency does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
        }
        else 
        {
            $this->Currency->updateAll(array('is_default' => 0));
            $this->Currency->id = $id;
            $this->Currency->save(array('is_default' => 1));
            $this->Session->setFlash(__('Successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }
        $this->redirect($this->referer());
    }
    
    public function admin_delete($id)
    {
        if(!$this->Currency->hasAny(array('id' => (int)$id)))
        {
            $this->Session->setFlash(__('This currency does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->url);
        }
        else
        {
            $this->Currency->delete($id);
            $this->Session->setFlash(__('Successfully deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->url);
        }
    }
}
