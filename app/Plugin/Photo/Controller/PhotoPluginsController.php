<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class PhotoPluginsController extends PhotoAppController {

    public function beforeFilter() {
        parent::beforeFilter();
        $this->loadModel('Category');
        $this->loadModel('Photo.Photo');
        $this->loadModel('Photo.Album');
    }

    public function admin_index() {

        if (!empty($this->request->data['keyword']))
            $this->redirect('/admin/photos/index/keyword:' . $this->request->data['keyword']);

        $cond = array();
        if (!empty($this->request->named['keyword']))
            $cond['MATCH(Album.title) AGAINST(? IN BOOLEAN MODE)'] = $this->request->named['keyword'];

        $photos = $this->paginate('Album', $cond);


        $categories = $this->Category->getCategoriesListItem('Photo');

        $this->set('photos', $photos);
        $this->set('categories', $categories);
        $this->set('title_for_layout', __('Albums Manager'));
    }

    public function admin_delete() {
        $this->_checkPermission(array('super_admin' => 1));
        
        if (!empty($_POST['albums'])) {
            
            $albums = $this->Album->findAllById( $_POST['albums'] );
            
            foreach ($albums as $album){
                
                $this->Album->deleteAlbum($album);
                
                $cakeEvent = new CakeEvent('Plugin.Controller.Album.afterDeleteAlbum', $this, array('item' => $album));
                $this->getEventManager()->dispatch($cakeEvent);
            }

            $this->Session->setFlash(__( 'Albums have been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        }

        $this->redirect( array(
            'plugin' => 'photo',
            'controller' => 'photo_plugins',
            'action' => 'admin_index'
        ) );
    }

    public function admin_move() {
        
        if (!empty($_POST['albums']) && !empty($this->request->data['category'])) {
            foreach ($_POST['albums'] as $album_id) {
                $this->Album->id = $album_id;
                $this->Album->save(array('category_id' => $this->request->data['category']));
            }
            $this->Session->setFlash(__( 'Albums moved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        }

        $this->redirect($this->referer());
    }

}
