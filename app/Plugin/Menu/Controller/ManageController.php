<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class ManageController extends MenuAppController
{

    public function __construct($request = null, $response = null)
    {
        parent::__construct($request, $response);
        $this->loadModel('Menu.CoreMenu');
        $this->loadModel('Menu.CoreMenuItem');
        $this->loadModel('Menu.CoreMenuMenu');
        $this->loadModel('Role');
        $this->loadModel('Page.Page');
        $this->loadModel('CoreBlock');
    }

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->_checkPermission(array('super_admin' => 1));

    }

    public function admin_index()
    {
        $this->set('title_for_layout', __('Menu Manager'));

        $aMenus = $this->CoreMenu->find('all');

        $firstMenu = $this->CoreMenu->find('first');

        $aPages = $this->Page->find('all', array('conditions' => 
                array('Page.url NOT LIKE' => "%$%",
                    'NOT' => array('Page.alias' => array('site_header', 'site_footer')))));

        $this->set('aMenus', $aMenus);
        $this->set('firstMenu', $firstMenu);
        $this->set('aPages', $aPages);
        
        if ($firstMenu) {
            $roles = $this->Role->find('all');
            
            $menu_data = $this->CoreMenuItem->find('all', array('conditions' => array('CoreMenuItem.menu_id' => $firstMenu['CoreMenu']['id']), 'order' => array('CoreMenuItem.menu_order ASC')));

            // find depth of each menu item
            foreach ($menu_data as $key => $item) {
                $depth = $this->getDepth($item['CoreMenuItem']['id']);
                $menu_data[$key]['CoreMenuItem']['depth'] = $depth;
            }

            $this->set('roles', $roles);
            $this->set('menu_item', $menu_data);
        }
        
    }

    public function admin_ajax_add_menu()
    {
        if ($this->request->is('ajax')) {
            $menu_item = $this->request->data['menu-item'];

            $menu_id = $this->request->data['menu'];
            $temp = array_shift(array_values($menu_item));
            $menu_item_type = isset($temp['menu-item-type']) ? $temp['menu-item-type'] : '';
            
            $roles = $this->Role->find('all');
            
            // root level for Tree
            $coreMenu = $this->CoreMenu->findById($menu_id);

            $menu_data = array();
            $roleIds = array();
            foreach ($roles as $item){
                $roleIds[] = $item['Role']['id'];
            }
            
            foreach ($menu_item as $key => $item) {
                
                $this->CoreMenuItem->create();
                $this->CoreMenuItem->save(array(
                    'parent_id' => NULL,
                    'name' => isset($item['menu-item-title']) ? $item['menu-item-title'] : '',
                    'original_name' => isset($item['menu-item-title']) ? $item['menu-item-title'] : '',
                    'title_attribute' => '',
                    'font_class' => '',
                    'is_active' => 1,
                    'new_blank' => 0,
                    'role_access' => json_encode($roleIds),
                    'menu_id' => $menu_id,
                    'type' => isset($item['menu-item-type']) ? $item['menu-item-type'] : '',
                    'url' => isset($item['menu-item-url']) ? $item['menu-item-url'] : '',
                    'menu_order' => 999
                ));
                $coreMenuItem = $this->CoreMenuItem->findById($this->CoreMenuItem->id);
                
                // translation
                $this->loadModel('Language');
                foreach (array_keys($this->Language->getLanguages()) as $lKey) {
                    $this->CoreMenuItem->locale = $lKey;
                    $this->CoreMenuItem->saveField('name', $item['menu-item-title']);
                }
                
                
                array_push($menu_data, $coreMenuItem);
            }

            $this->set('roles', $roles);
            $this->set('menu_item', $menu_data);
            $this->set('menu_item_type', $menu_item_type);
        }
    }
    
    public function admin_translate($menu_item_id){
        if (!empty($menu_item_id)){
            $this->loadModel('Menu.CoreMenuItem');
            
            $menu_item = $this->CoreMenuItem->findById($menu_item_id);
                        
            $this->set('menu_item', $menu_item);
            $this->set('languages', $this->Language->getLanguages());
        }
    }
    
    public function admin_translate_save(){
        $this->autoRender = false;
        if ($this->request->is('post') || $this->request->is('put')) {
            $this->loadModel('Menu.CoreMenuItem');
            if (!empty($this->request->data)) {
                // we are going CoreMenuItem save the german version
                $this->CoreMenuItem->id = $this->request->data['id'];
                foreach ($this->request->data['name'] as $lKey => $sContent) {
                    $this->CoreMenuItem->locale = $lKey;
                    if ($this->CoreMenuItem->saveField('name', $sContent)) {
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

    public function admin_edit_menu($id = null)
    {

        $this->set('title_for_layout', __('Menu Manager'));
        $aMenus = $this->CoreMenu->find('all');
        $firstMenu = $this->CoreMenu->findById($id);
        $aPages = $this->Page->find('all', array('conditions' => 
                array('Page.url NOT LIKE' => "%$%",
                    'NOT' => array('Page.alias' => array('site_header', 'site_footer')))));

        $this->set('aMenus', $aMenus);
        $this->set('firstMenu', $firstMenu);
        $this->set('aPages', $aPages);

        if (!$firstMenu || !$id) {
            $this->Session->setFlash(__('Invalid menu.'), 'default',
                array('class' => 'alert alert-success fade in'));

            $this->redirect('/admin/menu/manage/');
        }

        $roles = $this->Role->find('all');
       
        $menu_data = $this->CoreMenuItem->find('all', array('conditions' => array('CoreMenuItem.menu_id' => $firstMenu['CoreMenu']['id'], 'CoreMenuItem.is_active' => true), 'order' => array('CoreMenuItem.menu_order ASC')));
        
        // find depth of each menu item
        foreach ($menu_data as $key => $item) {
            $depth = $this->getDepth($item['CoreMenuItem']['id']);
            $menu_data[$key]['CoreMenuItem']['depth'] = $depth;
        }
        
        $this->set('roles', $roles);
        $this->set('menu_item', $menu_data);
    }

    public function admin_update_menu()
    {
        if ($this->request->is('post')) {
            
            $menu_id = $this->request->data['id'];
            $name = $this->request->data['name'];
            $style = $this->request->data['style'];

            if (!$menu_id) {
                $this->Session->setFlash(__('Invalid menu.'), 'default',
                    array('class' => 'alert alert-success fade in'));

            } else if (!$name || !$style) {
                $this->Session->setFlash(__('Menu Name and Menu Style are required.'), 'default',
                    array('class' => 'alert alert-success fade in'));
            } else {

                // update core menu
                $this->CoreMenu->id = $menu_id;
                $this->CoreMenu->save(array(
                    'name' => $name,
                    'style' => $style
                ));

                // update core menu items
                $menu_item_title = isset($this->request->data['menu-item-title']) ? $this->request->data['menu-item-title'] : array();
                $menu_item_link = isset($this->request->data['menu-item-link']) ? $this->request->data['menu-item-link'] : array();
                $menu_item_attr_title = isset($this->request->data['menu-item-attr-title']) ? $this->request->data['menu-item-attr-title'] : array();
                $menu_item_classes = isset($this->request->data['menu-item-classes']) ? $this->request->data['menu-item-classes'] : array();
                $menu_item_active = isset($this->request->data['menu-item-active']) ? $this->request->data['menu-item-active'] : array();
                $menu_item_target = isset($this->request->data['menu-item-target']) ? $this->request->data['menu-item-target'] : array();
                $menu_item_group_access = isset($this->request->data['menu-item-group-access']) ? $this->request->data['menu-item-group-access'] : array();
                $menu_item_parent_id = isset($this->request->data['menu-item-parent-id']) ? $this->request->data['menu-item-parent-id'] : array();
                $menu_item_position = isset($this->request->data['menu-item-position']) ? $this->request->data['menu-item-position'] : array();

                
                // delete menu item
                $currentMenuItem = $this->CoreMenuItem->find('all', array('conditions' => array('CoreMenuItem.menu_id' => $menu_id), 'fields' => array('CoreMenuItem.id')));
                $deleteItem = array();
                foreach ($currentMenuItem as $key => $item){
                    if (in_array($item['CoreMenuItem']['id'], array_keys($menu_item_title))){
                        unset($currentMenuItem[$key]);
                    }else{
                        array_push($deleteItem, $item['CoreMenuItem']['id']);
                    }
                }
                $this->CoreMenuItem->deleteAll(array('CoreMenuItem.id' => $deleteItem));
                
                // update menu item
                foreach ($menu_item_title as $key => $item) {
                    if (empty($menu_item_title[$key])){
                        $this->Session->setFlash(__('Navigation Label can not be NULL'), 'default',
                                array('class' => 'alert alert-danger fade in'));
                        $this->redirect($this->referer());
                        exit;
                    }
                    $this->CoreMenuItem->id = $key;
                    
                    $data = array(
                        'parent_id' => isset($menu_item_parent_id[$key]) ? $menu_item_parent_id[$key] : null,
                        'name' => isset($menu_item_title[$key]) ? $menu_item_title[$key] : '',
                        'url' => isset($menu_item_link[$key]) ? $menu_item_link[$key] : '',
                        'title_attribute' => isset($menu_item_attr_title[$key]) ? $menu_item_attr_title[$key] : '',
                        'font_class' => isset($menu_item_classes[$key]) ? $menu_item_classes[$key] : '',
                        'is_active' => isset($menu_item_active[$key]) ? $menu_item_active[$key] : 0,
                        'new_blank' => isset($menu_item_target[$key]) ? $menu_item_target[$key] : 0,
                        'role_access' => isset($menu_item_group_access[$key]) ? json_encode($menu_item_group_access[$key]) : '',
                        'menu_order' => isset($menu_item_position[$key]) ? $menu_item_position[$key] : 0,
                    );

                    $this->CoreMenuItem->save($data);
                    
                }

                // clear cache
                Cache::clearGroup('menu', 'menu');

                // create a widget
                $this->Session->setFlash(__('Menu is updated successful.'), 'default',
                    array('class' => 'alert alert-success fade in'));
            }

            $this->redirect($this->referer());
        }
    }

    public function admin_ajax_create()
    {

        if ($this->request->is('post')) {
            $name = $this->request->data['name'];
            $style = $this->request->data['style'];

            if (!$name || !$style) {
                echo json_encode(array(
                    'result' => false,
                    'message' => __('Menu Name and Menu Style are required.')
                ));

            } else {

                // create a menu
                $this->CoreMenu->save(array(
                    'name' => $name,
                    'style' => $style
                ));
                $menu_id = $this->CoreMenu->id;
                if ($menu_id) {
                    // updated 	menuid
                    $this->CoreMenu->id = $menu_id;
                    $this->CoreMenu->save(array(
                        'menuid' => 'menu-' . $menu_id
                    ));

                    // create a widget
                    $coreBlockMenu = array();
                    $coreBlockMenu[] = array(
                        "label" => "Title",
                        "input" => "text",
                        "value" => $name,
                        "name" => "title"
                    );
                    $coreBlockMenu[] = array(
                        "label" => "Plugin",
                        "input" => "hidden",
                        "value" => 'Menu',
                        "name" => 'plugin'
                    );
                    $coreBlockMenu[] = array(
                        "label" => "Menu ID",
                        "input" => "hidden",
                        "value" => $this->CoreMenu->id,
                        "name" => 'menu_id'
                    );
                    $this->CoreBlock->save(array(
                        'name' => $name,
                        'path_view' => 'menu.widget',
                        'params' => json_encode($coreBlockMenu),
                        'is_active' => true,
                        'group' => 'menu',
                        'plugin' => 'Menu'
                    ));

                    $this->Session->setFlash(__('Menu is created successful.'), 'default',
                        array('class' => 'alert alert-success fade in'));

                    echo json_encode(array(
                        'result' => true,
                        'id' => $this->CoreMenu->id
                    ));

                } else {
                    echo json_encode(array(
                        'result' => false,
                        'message' => __('Error while creating menu.')
                    ));
                }
            }
            exit;
        }
    }

    public function admin_do_delete($id = null)
    {
        $id = intval($id);

        $menu = $this->CoreMenu->findById($id);

        // check exist core menu
        $this->_checkExistence($menu);

        // check permission to delete core menu
        if (!$this->checkPermission($menu)) {
            $this->Session->setFlash(__('You not allow delete Main Menu and Footer Menu'), 'default',
                array('class' => 'alert alert-success fade in'));

            $this->redirect('/admin/menu/manage/');
        }

        $this->CoreMenu->delete($id);

        // delete core block
        $this->CoreBlock->deleteAll(array(
            "CoreBlock.params LIKE " => '%"value":"'.$id.'","name":"menu_id"%'
        ));

        $this->Session->setFlash(__('Menu has been deleted'), 'default',
            array('class' => 'alert alert-success fade in'));

        $this->redirect('/admin/menu/manage/');
    }

    private function checkPermission($aMenu)
    {
        // Admin not allow delete Main Menu and Footer Menu
        if (in_array($aMenu['CoreMenu']['alias'], array('main-menu', 'footer-menu'))) {
            return false;
        }

        return true;
    }

    protected function getDepth($item_id = null)
    {
        $parent_id = $this->CoreMenuItem->field('parent_id', array('CoreMenuItem.id' => $item_id));

        if ($parent_id) {
            return 1 + $this->getDepth($parent_id);
        } else {
            return 0;
        }
    }

}
