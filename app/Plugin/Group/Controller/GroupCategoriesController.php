<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class GroupCategoriesController extends GroupAppController {

    public function beforeFilter() {
        parent::beforeFilter();
        $this->loadModel('Category');
    }

    public function admin_index() {

        $cond = array();
		$type = 'Group';
        $cond = array('Category.type' => $type);
        $categories = $this->Category->getCats(array('conditions' => $cond, 'order' => 'Category.type asc, Category.weight asc')) ;
        
        $this->set('categories', $categories);
        $this->set('type', $type);
        $this->set('title_for_layout', __('Categories Manager'));
    }

    public function admin_create() {
        $bIsEdit = false;
        if (!empty($id)) {
            $category = $this->Category->getCatById($id);
            $bIsEdit = true;
        } else {
            $category = $this->Category->initFields();
            $category['Category']['active'] = 1;
        }

        $headers = $this->Category->find('list', array('conditions' => array('Category.type' => 'Group', 'Category.header' => 1), 'fields' => 'Category.name'));
        $headers[0] = '';
        
        // get all roles
        $this->loadModel('Role');
        $roles = $this->Role->find('all');

        $this->set('roles', $roles);
        $this->set('category', $category);
        $this->set('headers', $headers);
        $this->set('bIsEdit', $bIsEdit);
    }

    public function admin_save() {
        $this->autoRender = false;
        $bIsEdit = false;
        if (!empty($this->data['id'])) {
            $bIsEdit = true;
            $this->Category->id = $this->request->data['id'];
        }
        if ($this->request->data['header'])
            $this->request->data['parent_id'] = 0;

        $this->request->data['create_permission'] = (empty($this->request->data['everyone'])) ? implode(',', $_POST['permissions']) : '';

        $this->Category->set($this->request->data);

        $this->_validateData($this->Category);

        $this->Category->save();
        if (!$bIsEdit) {
            foreach (array_keys($this->Language->getLanguages()) as $lKey) {
                $this->Category->locale = $lKey;
                $this->Category->saveField('name', $this->request->data['name']);
            }
        }

        // clear cache
        Cache::delete('categories', 'group');

        $this->Session->setFlash(__('Category has been successfully saved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));

        $response['result'] = 1;
        echo json_encode($response);
    }

}
