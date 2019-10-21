<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class SettingGroupsController extends AppController 
{
    public function __construct($request = null, $response = null) 
    {
        parent::__construct($request, $response);
    }
    
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_checkPermission(array('super_admin' => 1));
	}

	public function admin_index()
	{		
        //group setting
        $setting_groups = $this->SettingGroup->find('threaded');

        $this->set('setting_groups', $setting_groups);
	}
    
    public function admin_create($id = null)
    {
        if(!empty($id) && !$this->SettingGroup->isIdExist($id))
        {
            $this->Session->setFlash(__('This group does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->referer());
        }
        else 
        {
            if(!empty($id))
            {
                $setting_group = $this->SettingGroup->find('first', array(
                    'conditions' => array('id' => $id)
                ));
            }
            else
            {
                $setting_group = $this->SettingGroup->initFields();
            }
            
            //combo setting group
            $groups = $this->SettingGroup->find('all', array(
                'conditions' => array('parent_id' => 0),
                'fields' => array('id', 'parent_id', 'name'),
            ));

            $cbSettingGroups = array();
            foreach($groups as $group)
            {
                if($group['SettingGroup']['id'] != $setting_group['SettingGroup']['id'])
                {
                    $cbSettingGroups[$group['SettingGroup']['id']] = $group['SettingGroup']['name'];
                }
            }
            $this->set('setting_group', $setting_group['SettingGroup']);
            $this->set('cbSettingGroups', $cbSettingGroups);
        }
    }

    public function admin_save()
    {
        if ($this->request->is('post')) 
        {
            if(!empty($this->request->data['name']) && $this->SettingGroup->isNameExist($this->request->data['name'], $this->request->data['id']))
            {
                $this->Session->setFlash(__('This name already exists'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect(array('controller' => 'settinggroups', 'action' => 'create/'.$this->request->data['id']));
            }
            else 
            {
                //validate
                $this->SettingGroup->set($this->request->data);
                if (!$this->SettingGroup->validates() )
                {
                    $errors = $this->SettingGroup->validationErrors;
                    $this->Session->setFlash(current(current($errors)), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                    $this->redirect(array('controller' => 'settinggroups', 'action' => 'create'));
                }
                
                //save data
                $this->SettingGroup->id = $this->request->data['id'];
                if ($this->SettingGroup->save($this->request->data)) 
                {
                    $this->Session->setFlash(__('Successfully saved.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
                    $this->redirect(array('controller' => 'settinggroups', 'action' => 'admin_index'));
                }
                $this->Session->setFlash(__('Unable to add setting.'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ));
                $this->redirect(array('controller' => 'settinggroups', 'action' => 'create'));
            }
        }
        else 
        {
            $this->redirect(array('controller' => 'pluginsettings', 'action' => 'create'));
        }
    }
    
    public function admin_delete($id)
    {
        $setting_group = $this->SettingGroup->findById($id);
        if(!$this->SettingGroup->isIdExist($id))
        {
            $this->Session->setFlash(__('This group does not exist'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->referer());
        }
        else if(!$this->SettingGroup->canDelete($id))
        {
            $this->Session->setFlash(__('You can not delete this group.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->referer());
        }
        else if($this->Setting->isGroupHasSettings($id))
        {
            $this->Session->setFlash(__('There are some settings in this group. Can\'t delete it.'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect($this->referer());
        }
        else
        {
            $this->SettingGroup->delete($id);
            $this->Session->setFlash(__('Successfully deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
            $this->redirect( $this->referer() );
        }
    }
}