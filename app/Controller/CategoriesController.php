<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class CategoriesController extends AppController {

    public function beforeFilter() {
        parent::beforeFilter();
        $this->_checkPermission(array('super_admin' => true));
    }

    public function admin_index($type = null) {
        
    }

    /*
     * Render add/edit category
     * @param mixed $id Id of category to edit
     */

    public function admin_ajax_create($id = null,$type = null) {

        $bIsEdit = false;
        if (!empty($id)) {
            $category = $this->Category->getCatById($id);
            $bIsEdit = true;
        } else {
            $category = $this->Category->initFields();
            $category['Category']['active'] = 1;
        }

        if(!empty($type))
        {
            $this->set('type', $type);
            $headers = $this->Category->find('list', array('conditions' => array('Category.type' => $type, 'Category.header' => 1), 'fields' => 'Category.name'));
        }
        else
            $headers = $this->Category->find('list', array('conditions' => array('header' => 1), 'fields' => 'Category.name'));


        $headers[0] = '';

        // get all roles
        $this->loadModel('Role');
        $roles = $this->Role->find('all');

        $this->set('roles', $roles);
        $this->set('category', $category);
        $this->set('headers', $headers);
        $this->set('bIsEdit', $bIsEdit);
    }
    
    public function admin_load_parent_categories($type = 'album'){
        $parent_categories = $this->Category->find('all', array('conditions' => array('Category.header' => 1, 'Category.type' => $type)));
        $this->set('parent_categories', $parent_categories);
    }

    public function admin_ajax_translate($id) {

        if (!empty($id)) {
            $category = $this->Category->getCatById($id);
            $this->set('category', $category);
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
                $this->Category->id = $this->request->data['id'];
                foreach ($this->request->data['name'] as $lKey => $sContent) {
                    $this->Category->locale = $lKey;
                    if ($this->Category->saveField('name', $sContent)) {
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

    /*
     * Handle add/edit category submission
     */

    public function admin_ajax_save() {
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
        $this->Session->setFlash(__('Category has been successfully saved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));

        $response['result'] = 1;
        echo json_encode($response);
    }

    public function admin_ajax_reorder() {
        
        $this->autoRender = false;

        $i = 1;
        foreach ($this->request->data['cats'] as $cat_id) {
            $this->Category->id = $cat_id;
            $this->Category->save(array('weight' => $i));

            $i++;
        }
    }

    public function admin_save_order()
    {
        $categoryModel = MooCore::getInstance()->getModel('Category');
        $this->autoRender = false;
        foreach ($this->request->data['cats'] as $cat_id => $weight) {
            $categoryModel->id = $cat_id;
            $categoryModel->save(array('weight' => $weight));
        }
        //clear cache
        $data = $categoryModel->read('type',$categoryModel->id);
        $type = $data['Category']['type'];
        Cache::delete(lcfirst($type).'.category',lcfirst($type));

        $this->Session->setFlash(__('Order saved'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
        echo $this->referer();
    }

    public function admin_delete($id) {
        $this->autoRender = false;

        $category = $this->Category->findById($id);

        switch ($category['Category']['type']) {
            case 'Group':
                $this->loadModel('Group.Group');
                $groups = $this->Group->findAllByCategoryId($id);
                foreach ($groups as $group){
                    $cakeEvent = new CakeEvent('Plugin.Controller.Group.beforeDelete', $this, array('aGroup' => $group));
                    $this->getEventManager()->dispatch($cakeEvent);

                    $this->Group->delete($group['Group']['id']);
                }

                // clear cache
                Cache::delete('categories', 'group');
                break;

            case 'Topic':
                $this->loadModel('Topic.Topic');
                $topics = $this->Topic->findAllByCategoryId($id);
                foreach ($topics as $topic){
                    $this->Topic->deleteTopic($topic);
                }
                //clear cache
                Cache::delete('topic', 'topic');
                break;
                
           case 'Blog':
                $this->loadModel('Blog.Blog');
                $blogs = $this->Blog->findAllByCategoryId($id);
                foreach ($blogs as $blog){
                    $this->Blog->deleteBlog($blog);
                }
                //clear cache
                Cache::delete('blog', 'blog');
                break;

            case 'Video':
                $this->loadModel('Video.Video');
                $videos = $this->Video->findAllByCategoryId($id);
                foreach ($videos as $video){
                    $this->Video->deleteVideo($video);
                }
                //clear cache
                Cache::delete('video', 'video');
                break;

            case 'Photo':
                $this->loadModel('Photo.Album');
                $albums = $this->Album->findAllByCategoryId($id);
                
                foreach ($albums as $album){
                    $this->Album->deleteAlbum($album);
                    
                    $cakeEvent = new CakeEvent('Plugin.Controller.Album.afterDeleteAlbum', $this, array('item' => $album));
                    $this->getEventManager()->dispatch($cakeEvent);
                }
                
                //clear cache
                Cache::delete('photo', 'photo');
                break;

            case 'Event':
                $this->loadModel('Event.Event');
                $events = $this->Event->findAllByCategoryId($id);
                foreach ($events as $event){
                    $this->Event->deleteEvent($event);
                }
                //clear cache
                Cache::delete('event', 'event');
                break;
            default:
                $cakeEvent = new CakeEvent('Plugin.Controller.Category.beforeDelete', $this, array('category' => $category));
                $this->getEventManager()->dispatch($cakeEvent);
                break;
        }

        // delete child category
        $this->Category->deleteAll(array('Category.parent_id' => $category['Category']['id']));
        
        // delete this category
        $this->Category->delete($id);
		
        $this->Session->setFlash(__('Category deleted'),'default',array('class' => 'Metronic-alerts alert alert-success fade in'));
        $this->redirect($this->referer());
    }

    public function admin_import($type) {
        $plugins = array(
            '0' => __('Select plugin'),
            'Blog' => __('Blog'),
            'Group' => __('Group'),
            'Event' => __('Event'),
            'Photo' => __('Photo'),
            'Topic' => __('Topic'),
            'Video' => __('Video'),
        );

        if(isset($plugins[$type])){
            unset($plugins[$type]);
        }
        $this->set('plugins', $plugins);
        $this->set('type', $type);
    }

    public function admin_do_import(){
        $this->autoRender = false;
        $response['result'] = 0;
        if(!empty($this->request->data)) {

            if($this->request->data['plugin'] == '0'){
                $response['result'] = 0;
                $response['message'] = __('Please select plugin');
            }else{
                $categories = $this->Category->find('all', array('conditions' => array('Category.type' => $this->request->data['plugin'])));
                $parent_ids = array();
                $child_ids = array();

                foreach ($categories as $category) {
                    $this->Category->clear();
                    $this->Category->set(array(
                        'type' => $this->request->data['type'],
                        'parent_id' => $category['Category']['parent_id'],
                        'name' => $category['Category']['name'],
                        'description' => $category['Category']['description'],
                        'active' => $category['Category']['active'],
                        'weight' => $category['Category']['weight'],
                        'header' => $category['Category']['header'],
                        'create_permission' => $category['Category']['create_permission'],
                    ));
                    $this->Category->save();

                    $cat_id = $this->Category->id;
                    if($category['Category']['parent_id'] == 0){
                        $parent_ids[$category['Category']['id']] = $cat_id;
                    }else{
                        $child_ids[] = $cat_id;
                    }

                    foreach ($category['nameTranslation'] as $translate) {
                        $this->Category->locale = $translate['locale'];
                        $this->Category->saveField('name', $translate['content']);
                    }
                }
                //update child parent id
                $child_categories = $this->Category->find('all', array('conditions' => array('Category.id' => $child_ids)));
                foreach ($child_categories as $child) {
                    $cat_id = $parent_ids[$child['Category']['parent_id']];
                    $this->Category->clear();
                    $this->Category->id = $child['Category']['id'];
                    $this->Category->saveField('parent_id', $cat_id);
                }
                $this->Session->setFlash(__('Categories has been successfully imported'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
                $response['result'] = 1;
            }
        }

        echo json_encode($response);
    }

}
