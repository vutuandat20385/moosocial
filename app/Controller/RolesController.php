<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class RolesController extends AppController 
{
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_checkPermission(array('super_admin' => 1));
    }
        
    public function admin_index()
    {
        $roles = $this->Role->find('all');
        
        $this->set('roles', $roles);
        $this->set('title_for_layout', __('Roles Manager'));
    }
    
    public function admin_ajax_create($id = null)
    {
        if (!empty($id))
            $role = $this->Role->findById($id);
        else
            $role = $this->Role->initFields();
        
        $permissions = explode(',', $role['Role']['params']);
        
        // get acos
        $this->loadModel('Aco');
        $acos = $this->Aco->find('all');
        $aco_groups = array();
        
        foreach ( $acos as $aco )
            $aco_groups[$aco['Aco']['group']][] = $aco;

        $this->set('role', $role);
        $this->set('permissions', $permissions);
        $this->set('aco_groups', $aco_groups);
    }
    
    public function admin_ajax_save()
    {
        $this->autoRender = false;

        if ( !empty( $this->data['id'] ) )
            $this->Role->id = $this->request->data['id'];
        
        $params = array();
        
        foreach ( $this->request->data as $key => $val )
            if ( strpos($key, 'param_' ) !== false && !empty($val) )
                $params[] = substr($key, 6); 
            
        $this->request->data['params'] = implode(',', $params);

        $this->Role->set( $this->request->data );
        $this->_validateData( $this->Role );
        
        $this->Role->save();
        
        $events = new CakeEvent('Controller.Role.afterSave',$this, array('role_id' => $this->Role->id));
        $this->getEventManager()->dispatch($events);
        
        $this->Session->setFlash(__('Role has been successfully updated'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        Cache::delete('guest_role');
        
        $response['result'] = 1;
        echo json_encode($response);
    }
    
    public function admin_delete(){
        $this->autoRender = false;
        $roles = isset($this->request->data['roles']) ? $this->request->data['roles'] : array();
        
        $msg = '';
        $this->loadModel('User');
        foreach ($roles as $role_id){
            $role = $this->Role->findById($role_id);
            $users = $this->User->findByRoleId($role_id);
            
            // only delete role when this role dont have any users
            if (empty($users)){
                $this->Role->delete($role_id);
                $msg .= $role['Role']['name']. " " . __('has been successfully deleted.') . "<br />";
            }
            else{
                $msg .= $role['Role']['name']. " " . __('not deleted.') . "<br />";
            }
            
        }
        
        $events = new CakeEvent('Controller.Role.afterDelete',$this, array('roleIds' => $roles));
        $this->getEventManager()->dispatch($events);
        
        if (empty($msg)){
            $msg = __('Roles have been successfully deleted');
        }
        
        $this->Session->setFlash($msg, 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        $this->redirect($this->referer());
    }


    public function admin_export()
    {
    	$this->loadModel('Aco');
    	$acos = $this->Aco->find('all');    	
    	$list_message = array();
    	foreach ($acos as $aco)
    	{
    		
    		$list_message[] = Inflector::humanize($aco['Aco']['group']);
    		$list_message[] = $aco['Aco']['description'];
    	}
    	
    	
    	$list_message = array_unique($list_message);
    	$path = APP.'tmp'.DS.'logs'.DS.'permission.po';
    	MooCore::getInstance()->exportTranslate($list_message,$path);    	
    	$this->viewClass = 'Media';
        // Download app/outside_webroot_dir/example.zip
        $params = array(
            'id'        => 'permission.po',
            'name'      => 'permission',
            'download'  => true,
            'extension' => 'po',
            'path'      => APP.'tmp'.DS.'logs'.DS
        );
        $this->set($params);
    }
}
