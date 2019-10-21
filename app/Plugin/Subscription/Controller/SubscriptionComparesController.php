<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class SubscriptionComparesController extends SubscriptionAppController
{
    public function __construct($request = null, $response = null) 
    {
        parent::__construct($request, $response);
        $this->url = '/admin/subscription/subscription_compares/';
        $this->set('url', $this->url);
        $this->set('url_create', $this->url_create);
        $this->set('url_delete', $this->url_delete);
    }
    
    public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_checkPermission(array('super_admin' => 1));        
        $this->loadModel('Subscription.SubscriptionCompare');
	}
    
	public function admin_index($language = null)
    {
        $this->loadModel('Subscription.SubscriptionPackage');
        
        $this->loadModel("Language");
        $langs = $this->Language->find( 'all' );
        
        if (!$language)
        {
        	foreach ($langs as $lang)
        	{
        		$language = $lang['Language']['key'];
        		break;
        	}
        }
        $this->SubscriptionPackage->locale = $language;
        $columns = $this->SubscriptionPackage->find('all',array(
        		'conditions' => array(
        				'SubscriptionPackage.deleted <> ' => 1
        		)
        ));
        $tmp = array();
        foreach ($langs as $lang)
        {
        	$tmp[$lang['Language']['key']] = $lang['Language']['name'];
        }
        
        $this->set('languages', $tmp);
        $this->set('language', $language);
        $this->SubscriptionCompare->locale = $language;
        
        $compares = $this->SubscriptionCompare->find('all');
        if($compares != null)
        {
            foreach($compares as $k => $v)
            {
                $compares[$k]['SubscriptionCompare']['compare_value'] = json_decode($v['SubscriptionCompare']['compare_value'], true);
            }
        }
        $this->set('columns', $columns);
        $this->set('compares', $compares);
    }
    
    public function admin_save()
    {
        if ($this->request->is('post')) 
        {
            //debug($this->request->data);die;
        	$this->loadModel("Language");
        	$langs = $this->Language->getLanguages();
        	
            $count = -1;
            $data = array();
            $compares = $this->SubscriptionCompare->find('all');
            $ids = array();            
            foreach($this->request->data['SubscriptionCompare']['name'] as $index=>$compare)
            {
                $item = array();
                $count++;
                if($count > 0)
                {
                    if($compare != '')
                    {
                        $item['compare_name'] = $compare;
                        $value = array();
                        foreach($this->request->data['compare_type'] as $k => $v)
                        {
                            $value[$k]['type'] = $v[$count];
                        }
                        foreach($this->request->data['yesno_value'] as $k => $v)
                        {
                            if($value[$k]['type'] == 'yesno')
                            {
                                $value[$k]['value'] = $v[$count];
                            }
                        }
                        foreach($this->request->data['text_value'] as $k => $v)
                        {
                            if($value[$k]['type'] == 'text')
                            {
                                $value[$k]['value'] = $v[$count];
                            }
                        }
                        $item['compare_value'] = json_encode($value);
                        
                        if (!$this->request->data['SubscriptionCompare']['id'][$index])
                        {
                        	$this->SubscriptionCompare->create();
                        	$this->SubscriptionCompare->save($item);
                        	
                        	foreach (array_keys($langs) as $lKey) {
                        		$this->SubscriptionCompare->locale = $lKey;
                        		$this->SubscriptionCompare->saveField('compare_name', $item['compare_name']);
                        		$this->SubscriptionCompare->saveField('compare_value', $item['compare_value']);
                        	}
                        }
                        else 
                        {
                        	$ids[] = $this->request->data['SubscriptionCompare']['id'][$index];
                        	$this->SubscriptionCompare->id = $this->request->data['SubscriptionCompare']['id'][$index];
                        	$this->SubscriptionCompare->locale = $this->request->data['SubscriptionCompare']['language'];
                        	$this->SubscriptionCompare->save($item);
                        }
                    }
                }
            }
            foreach ($compares as $compare)
            {
            	if (!in_array($compare['SubscriptionCompare']['id'], $ids))
            	{
            		$this->SubscriptionCompare->delete($compare['SubscriptionCompare']['id']);
            	}
            }
            $this->Session->setFlash(__( 'Changes saved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->url.'index/'.$this->request->data['SubscriptionCompare']['language']);
        }
        else 
        {
            $this->redirect($this->url);
        }
    }
}
