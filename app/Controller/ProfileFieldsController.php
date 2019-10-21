<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ProfileFieldsController extends AppController
{
	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->_checkPermission(array('super_admin' => 1));
		
		$this->loadModel("ProfileType");
	}
	
	/*
	 * Render listing fields
	 */
	public function admin_index()
	{
		$fields = $this->ProfileType->find( 'all' );
		$this->set('fields', $fields);
		$this->set('title_for_layout', __('Profile Types'));
	}
	
	public function admin_profile_fields($id = null)
	{
		$fields = $this->ProfileField->find( 'all' ,array('conditions'=>array(
			'ProfileField.profile_type_id' => $id
		)));
		$profile_type = $this->ProfileType->findById($id);
		$this->set('id',$id);
		$this->set('profile_type',$profile_type);
		$this->set('fields', $fields);
		$this->set('title_for_layout', __('Custom Profile Fields'));
	}
	
	public function admin_ajax_type_create($id = null)
	{
		$bIsEdit = false;
		if (!empty($id))
		{
			$field = $this->ProfileType->findById($id);
			$bIsEdit = true;
		}
		else
			$field = $this->ProfileType->initFields();
		
			$this->set('bIsEdit',$bIsEdit);
			$this->set('field', $field);
	}
	
	public function admin_ajax_type_save( )
	{
		$this->autoRender = false;
		$bIsEdit = false;
		if ( !empty( $this->data['id'] ) )
		{
			$bIsEdit = true;
			$this->ProfileType->id = $this->request->data['id'];
		}
	
		$this->ProfileType->set( $this->request->data );
		$this->_validateData( $this->ProfileType );
	
		$this->ProfileType->save( $this->request->data );
		
		if (!$bIsEdit) {
			foreach (array_keys($this->Language->getLanguages()) as $lKey) {
				$this->ProfileType->locale = $lKey;
				$this->ProfileType->saveField('name', $this->request->data['name']);
			}
		}
	
		$this->Session->setFlash(__('Profile type has been successfully saved'),'default',
				array('class' => 'Metronic-alerts alert alert-success fade in' ));
	
		$response['result'] = 1;
		echo json_encode($response);
	}
	
	public function admin_ajax_type_translate($id) {
		
		if (!empty($id)) {
			$profile = $this->ProfileType->findById($id);
			$this->set('profile', $profile);
			$this->set('languages', $this->Language->getLanguages());
		} else {
			// error
		}
	}
	
	public function admin_ajax_type_translate_save() {
		
		$this->autoRender = false;
		if ($this->request->is('post') || $this->request->is('put')) {
			if (!empty($this->request->data)) {
				// we are going to save the german version
				$this->ProfileType->id = $this->request->data['id'];
				foreach ($this->request->data['name'] as $lKey => $sContent) {
					$this->ProfileType->locale = $lKey;
					if ($this->ProfileType->saveField('name', $sContent)) {
						$response['result'] = 1;
					} else {
						$response['result'] = 0;
					}
				}
			} else {
				$response['result'] = 0;
			}
		} else {
			$response['result'] = 0;
		}
		echo json_encode($response);
	}
	
	public function admin_save_type_order()
	{
		$this->_checkPermission(array('super_admin' => 1));
		$this->autoRender = false;
		foreach ($this->request->data['order'] as $id => $order) {
			$this->ProfileType->id = $id;
			$this->ProfileType->save(array('order' => $order));
		}
		$this->Session->setFlash(__('Order saved'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
		echo $this->referer();
	}
	
	/*
	 * Render add/edit field
	 * @param mixed $id Id of field to edit
	 */
	public function admin_ajax_create($profile_type_id, $id = null )
	{
		$bIsEdit = false;
		if (!empty($id))
		{
			$field = $this->ProfileField->findById($id);
			$bIsEdit = true;
		}
		else
			$field = $this->ProfileField->initFields();
		
		$this->set('profile_type_id',$profile_type_id);
		$this->set('bIsEdit',$bIsEdit);
		$this->set('field', $field);
	}
	
	/*
	 * Handle add/edit field submission
	 */
	public function admin_ajax_save( )
	{
		$this->autoRender = false;
		$bIsEdit = false;
		if ( !empty( $this->data['id'] ) )
		{
			$bIsEdit = true;
			$this->ProfileField->id = $this->request->data['id'];
		}

		$this->ProfileField->set( $this->request->data );
		$this->_validateData( $this->ProfileField );

		$type = $this->request->data['type'];
		$event = new CakeEvent('Profile.Field.getType',$this);
		$result = $this->getEventManager()->dispatch($event);
		$this->request->data['plugin'] = '';
		if ($result->result)
		{
			if (isset($result->result[$type]) && isset($result->result[$type]['plugin']))
			{
				$this->request->data['plugin'] = $result->result[$type]['plugin'];
			}
		}
		
		if ($this->ProfileField->save( $this->request->data ))
		{
			$field_id = $this->ProfileField->id;
			$this->ProfileField->saveFieldSearch($field_id);
		}	
        
        if ( $this->request->data['type'] == 'heading' && empty( $this->request->data['id'] ) ) // insert dummy value
        {
            $this->loadModel('ProfileFieldValue');
            $this->ProfileFieldValue->save( array( 'profile_field_id' => $this->ProfileField->id ) );
        }
        
        if (!$bIsEdit) {
        	foreach (array_keys($this->Language->getLanguages()) as $lKey) {
        		$this->ProfileField->locale = $lKey;
        		$this->ProfileField->saveField('name', $this->request->data['name']);
        	}
		}

        $this->Session->setFlash(__('Profile field has been successfully saved'),'default',
			array('class' => 'Metronic-alerts alert alert-success fade in' ));
			
		if (!$bIsEdit && ($this->request->data['type'] == 'list' || $this->request->data['type'] == 'multilist'))
		{
			$id = $this->ProfileField->id;
			$response['redirect'] = $this->request->base.'/admin/profile_fields/profile_field_options/'. $id;
			$response['result'] = 0;
		}
		else
		{
			$response['result'] = 1;
		}

        echo json_encode($response);
	}
	
	public function admin_ajax_reorder()
	{
		$this->_checkPermission(array('super_admin' => 1));
		$this->autoRender = false;
		foreach ($this->request->data['order'] as $id => $order) {
			$this->ProfileField->id = $id;
			$this->ProfileField->save(array('weight' => $order));
		}
		$this->Session->setFlash(__('Order saved'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
		echo $this->referer();
	}
	
	public function admin_delete( $id )
	{
		$this->autoRender = false;
		$this->loadModel("ProfileFieldValue");
		$this->loadModel('ProfileFieldOption');
		$this->ProfileField->delete( $id );
		$this->ProfileFieldValue->deleteAll( array( 'ProfileFieldValue.profile_field_id' => $id ), false, false );
		$this->ProfileFieldOption->deleteProfileFieldOption($id);
		$this->ProfileField->deleteFieldSearch($id);
		
		$this->Session->setFlash(__('Field deleted'),'default',
            array('class' => 'Metronic-alerts alert alert-success fade in' ));
		$this->redirect( $this->referer() );
	}
	
	public function admin_ajax_translate($id) {
	
		if (!empty($id)) {
			$profile = $this->ProfileField->findById($id);
			$this->set('profile', $profile);
			$this->set('languages', $this->Language->getLanguages());
		} else {
			// error
		}
	}
	
	public function admin_ajax_translate_save() {
	
		$this->autoRender = false;
		if ($this->request->is('post') || $this->request->is('put')) {
			if (!empty($this->request->data)) {
				// we are going to save the german version
				$this->ProfileField->id = $this->request->data['id'];
				foreach ($this->request->data['name'] as $lKey => $sContent) {
					$this->ProfileField->locale = $lKey;
					if ($this->ProfileField->saveField('name', $sContent)) {
						$response['result'] = 1;
					} else {
						$response['result'] = 0;
					}
				}
			} else {
				$response['result'] = 0;
			}
		} else {
			$response['result'] = 0;
		}
		echo json_encode($response);
	}
	
	public function admin_type()
	{
		$this->loadModel('ProfileType');
		$fields_type = $this->ProfileType->find( 'all' );
		$this->set('fields', $fields_type);
		$this->set('title_for_layout', __('Profile Type'));
		$this->set('count_profilt_type', count($fields_type));
	}
	
	public function admin_ajax_create_type( $id = Null)
	{
		$this->loadModel('ProfileType');
		if (!empty($id))
			$field = $this->ProfileType->findById($id);
			else
				$field = $this->ProfileType->initFields();
	
				$this->set('field', $field);
	}
	
	public function admin_ajax_save_type()
	{
		$this->autoRender = false;
		$this->loadModel('ProfileType');
		if ( !empty( $this->data['id'] ) )
			$this->ProfileType->id = $this->request->data['id'];
	
			$this->ProfileType->set( $this->request->data );
			$this->_validateData( $this->ProfileType );
	
			$this->ProfileType->save( $this->request->data );
			$this->Session->setFlash(__('Profile type has been successfully saved'),'default',
					array('class' => 'Metronic-alerts alert alert-success fade in' ));
	
			$response['result'] = 1;
			echo json_encode($response);
	}
	
	public function admin_delete_type( $id )
	{
		$this->autoRender = false;
	
		$fields = $this->ProfileField->findAllByProfileTypeId($id);
		$ids = array();
		foreach( $fields as $field ){
			$ids[] = $field['ProfileField']['id'];
			$this->ProfileField->delete( $field['ProfileField']['id'] );
			$this->ProfileField->deleteFieldSearch($field['ProfileField']['id']);			
		}
		$this->loadModel("ProfileFieldValue");
		$this->loadModel("ProfileFieldOption");
		if (count($ids))
		{
			$this->ProfileFieldValue->deleteAll( array( 'ProfileFieldValue.profile_field_id' => $ids ), false, false );
			$this->ProfileFieldOption->deleteProfileFieldOption($ids);
		}
		$this->loadModel('ProfileType');
		$this->ProfileType->delete( $id );
	
		$this->Session->setFlash(__('Profile type deleted'),'default',
				array('class' => 'Metronic-alerts alert alert-success fade in' ));
		$this->redirect( $this->referer() );
	}

	public function admin_profile_field_options($profile_field_id)
	{
		$this->set('title_for_layout', __('Profile Field Options'));
		$this->loadModel('ProfileFieldOption');
		$field_options = $this->ProfileFieldOption->findAllByProfileFieldId($profile_field_id);
		$profile_field = $this->ProfileField->findById($profile_field_id);

		$this->set('field_options', $field_options);
		$this->set('profile_field', $profile_field);
		$this->set('profile_field_id', $profile_field_id);
	}

	public function admin_ajax_create_option($profile_field_id, $profile_field_option_id = null)
	{
		$this->loadModel('ProfileFieldOption');
		$bIsEdit = false;
		if (!empty($profile_field_option_id))
		{
			$field = $this->ProfileFieldOption->findById($profile_field_option_id);
			$bIsEdit = true;
		}
		else
			$field = $this->ProfileFieldOption->initFields();
		
		$this->set('bIsEdit',$bIsEdit);
		$this->set('field', $field);
		$this->set('profile_field_id', $profile_field_id);
	}
	
	public function admin_ajax_save_option()
	{
		$this->autoRender = false;
		$this->loadModel('ProfileFieldOption');

		$bIsEdit = false;
		if ( !empty( $this->data['id'] ) )
		{
			$bIsEdit = true;
			$this->ProfileFieldOption->id = $this->request->data['id'];
		}
	
		$this->ProfileFieldOption->set( $this->request->data );
		$this->_validateData( $this->ProfileFieldOption );

		if ($this->ProfileFieldOption->save( $this->request->data ))
		{
			$field_id = $this->request->data['profile_field_id'];
			$this->ProfileField->saveFieldSearch($field_id, true);
		}

		if (!$bIsEdit) {
        	foreach (array_keys($this->Language->getLanguages()) as $lKey) {
        		$this->ProfileFieldOption->locale = $lKey;
        		$this->ProfileFieldOption->saveField('name', $this->request->data['name']);
        	}
		}
		
		$this->Session->setFlash(__('Profile field options has been successfully saved'),'default',
				array('class' => 'Metronic-alerts alert alert-success fade in' ));

		$response['result'] = 1;
		echo json_encode($response);
	}

	public function admin_ajax_translate_option($profile_field_option_id)
	{
		$this->loadModel('ProfileFieldOption');
		if (!empty($profile_field_option_id)) {
			$field_option = $this->ProfileFieldOption->findById($profile_field_option_id);
			$this->set('field_option', $field_option);
			$this->set('languages', $this->Language->getLanguages());
		} else {
			// error
		}
	}

	public function admin_ajax_reorder_options()
	{
		$this->loadModel('ProfileFieldOption');
		$this->_checkPermission(array('super_admin' => 1));
		$this->autoRender = false;
		foreach ($this->request->data['order'] as $id => $order) {
			$this->ProfileFieldOption->id = $id;
			$this->ProfileFieldOption->save(array('order' => $order));
		}
		$this->Session->setFlash(__('Order saved'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
		echo $this->referer();
	}

	public function admin_ajax_translate_save_option() 
	{
		$this->loadModel('ProfileFieldOption');
		$this->autoRender = false;
		if ($this->request->is('post') || $this->request->is('put')) {
			if (!empty($this->request->data)) {
				// we are going to save the german version
				$this->ProfileFieldOption->id = $this->request->data['id'];
				foreach ($this->request->data['name'] as $lKey => $sContent) {
					$this->ProfileFieldOption->locale = $lKey;
					if ($this->ProfileFieldOption->saveField('name', $sContent)) {
						$response['result'] = 1;
					} else {
						$response['result'] = 0;
					}
				}
			} else {
				$response['result'] = 0;
			}
		} else {
			$response['result'] = 0;
		}
		echo json_encode($response);
	}

	public function admin_delete_option( $profile_field_option_id )
	{
		$this->autoRender = false;
		$this->loadModel("ProfileFieldOption");
		$this->ProfileFieldOption->delete( $profile_field_option_id );
		
		$this->Session->setFlash(__('Field option deleted'),'default',
            array('class' => 'Metronic-alerts alert alert-success fade in' ));
		$this->redirect( $this->referer() );
	}

	private function upgrade_profile_value($id)
	{
		$this->loadModel("ProfileFieldValue");
		$this->loadModel("ProfileFieldOption");

		$options = $this->ProfileFieldOption->find('list', array(
			'conditions' => array('ProfileFieldOption.profile_field_id' => $id),
			'fields' => array('ProfileFieldOption.name')
		));

		$values = $this->ProfileFieldValue->find('list', array(
			'conditions' => array('ProfileFieldValue.profile_field_id' => $id),
			'fields' => array('ProfileFieldValue.value')
		));

		$data = array();
		foreach ($values as $key => $value)
		{
			if (!empty($value))
			{
				$data[$key] = explode(', ', $value);
			}
		}

		$values = $data;

		if (!empty($values) && !empty($options))
		{
			$data2 = array();
			foreach ($values as $k_v => $value)
			{
				foreach ($value as $item)
				{
					foreach ($options as $k_o => $option)
					{
						if ($option == $item)
						{
							$data2[$k_v][] = $k_o;
						}
					}
				}
				
			}

			foreach ($data2 as $key => $data)
			{
				$value = implode(', ', $data);
				$this->ProfileFieldValue->clear();
				$this->ProfileFieldValue->id = $key;
				$this->ProfileFieldValue->save(array('value' => $value));
			}
		}
	}

	public function upgrade_search_user()
	{
		$this->autoRender = false;
		$mSetting = MooCore::getInstance()->getModel('Setting');
		$table_prefix = $mSetting->tablePrefix;
		$db = ConnectionManager::getDataSource("default");

		//save column search type == list
		$columns = $this->ProfileField->find('list', array(
			'conditions' => array('ProfileField.type !=' => 'heading'),
			'fields' => 'ProfileField.id',
			'order' => 'ProfileField.id ASC'
		));
		foreach ($columns as $column)
		{
			try {
				$db->query("ALTER TABLE `" . $table_prefix . "profile_field_searches` ADD COLUMN `field_". $column ."` VARCHAR(255) NULL");
			} 
			catch(Exception $e)
			{
				
			}
		}
	}

	public function upgrade_option_value()
	{
		$this->autoRender = false;
		$this->loadModel("ProfileFieldOption");
		$mSetting = MooCore::getInstance()->getModel('Setting');
		$table_prefix = $mSetting->tablePrefix;
		$db = ConnectionManager::getDataSource("default");

		//save options and change field search type == list
		$list = $this->ProfileField->find('list', array(
			'conditions' => array('ProfileField.type' => 'list'),
			'fields' => 'ProfileField.id, ProfileField.values'
		));   
		foreach ($list as $key => $value)
		{
			$field_values = explode( "\n", $value );
			foreach ($field_values as $field_value)
			{
				$data = array(
					'profile_field_id' => $key,
					'name' => trim($field_value)
				);
				$this->ProfileFieldOption->clear();
				if($this->ProfileFieldOption->save($data)){
					foreach (array_keys($this->Language->getLanguages()) as $lKey) {
						$this->ProfileFieldOption->locale = $lKey;
						$this->ProfileFieldOption->saveField('name', $data['name']);
					}
				}
			}

			$option_ids = $this->ProfileFieldOption->find('list', array(
				'conditions' => array(
					'ProfileFieldOption.profile_field_id' => $key
				),
				'fields' => 'ProfileFieldOption.id'
			));

			if ($option_ids)
			{
				$option_ids = array_map('strval',$option_ids);
				$option_ids = "'" . implode("','", $option_ids). "'";
				try {
					$db->query("ALTER TABLE `" . $table_prefix . "profile_field_searches` CHANGE `field_". $key ."` `field_". $key ."` ENUM(". $option_ids .") NULL");
				}
				catch(Exception $e)
				{
					
				}
			}

			$this->upgrade_profile_value($key);
		}

		//save options and change field search type == multilist
		$list = $this->ProfileField->find('list', array(
			'conditions' => array('ProfileField.type' => 'multilist'),
			'fields' => 'ProfileField.id, ProfileField.values'
		));
		foreach ($list as $key => $value)
		{
			$field_values = explode( "\n", $value );
			foreach ($field_values as $field_value)
			{
				$data = array(
					'profile_field_id' => $key,
					'name' => trim($field_value)
				);
				$this->ProfileFieldOption->clear();
				if($this->ProfileFieldOption->save($data)){
					foreach (array_keys($this->Language->getLanguages()) as $lKey) {
						$this->ProfileFieldOption->locale = $lKey;
						$this->ProfileFieldOption->saveField('name', $data['name']);
					}
				}
			}

			$option_ids = $this->ProfileFieldOption->find('list', array(
				'conditions' => array(
					'ProfileFieldOption.profile_field_id' => $key
				),
				'fields' => 'ProfileFieldOption.id'
			));

			if ($option_ids)
			{
				$option_ids = array_map('strval',$option_ids);
				$option_ids = "'" . implode("','", $option_ids). "'";
				try {
					$db->query("ALTER TABLE `" . $table_prefix . "profile_field_searches` CHANGE `field_". $key ."` `field_". $key ."` SET(". $option_ids .") NULL");
				}
				catch(Exception $e)
				{
					
				}
			}

			$this->upgrade_profile_value($key);
		}
	}
	
	public function upgrade_search_value()
	{
		$this->autoRender = false;
		$this->loadModel("User");
		$this->loadModel("ProfileFieldSearch");
		$user_ids = $this->User->find('list', array('fields' => 'id', 'order' => 'id ASC'));
		foreach ($user_ids as $user_id)
		{
			$this->ProfileFieldSearch->saveSearchValue($user_id);
		}
	}
}