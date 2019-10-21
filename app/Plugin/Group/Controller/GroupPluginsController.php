<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class GroupPluginsController extends GroupAppController {
    
    public function beforeFilter() {
        parent::beforeFilter();
        $this->loadModel('Group.Group');
        $this->loadModel('Category');
    }

    public function admin_index() {
        
        $cond = array();
        
        if (!empty($this->request->data['keyword']))
            $cond['MATCH(Group.name) AGAINST(? IN BOOLEAN MODE)'] = $this->request->data['keyword'];

        $groups = $this->paginate('Group', $cond);
        
        $categories = $this->Category->getCategoriesListItem('Group');

        $this->set('categories', $categories);
        $this->set('groups', $groups);
        $this->set('title_for_layout', __('Groups Manager'));
    }

    public function admin_delete() {
        $this->_checkPermission(array('super_admin' => 1));
        
        if (!empty($_POST['groups'])) {
            
            foreach ($_POST['groups'] as $group_id){
                $group = $this->Group->findById($group_id);
                
                $cakeEvent = new CakeEvent('Plugin.Controller.Group.beforeDelete', $this, array('aGroup' => $group));
                $this->getEventManager()->dispatch($cakeEvent);

                $this->Group->delete($group_id);
                
                $cakeEvent = new CakeEvent('Plugin.Controller.Group.afterDeleteGroup', $this, array('item' => $group));
                $this->getEventManager()->dispatch($cakeEvent);
                
            }

            $this->Session->setFlash(__( 'Groups have been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        }

        $this->redirect( array(
            'plugin' => 'group',
            'controller' => 'group_plugins',
            'action' => 'admin_index'
        ) );
    }
    
    public function admin_move() {
        if (!empty($_POST['groups']) && !empty($this->request->data['category'])) {
            foreach ($_POST['groups'] as $group_id) {
                $this->Group->id = $group_id;
                $this->Group->save(array('category_id' => $this->request->data['category']));
            }
            $this->Session->setFlash(__( 'Groups moved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        }

        $this->redirect($this->referer());
    }

}
