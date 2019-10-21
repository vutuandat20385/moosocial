<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class TopicPluginsController extends TopicAppController {

    public function beforeFilter() {
        parent::beforeFilter();
        $this->loadModel('Topic.Topic');
        $this->loadModel('Category');
    }

    public function admin_index() {


        $cond = array();

        if (!empty($this->request->data['keyword']))
            $cond['MATCH(Topic.title) AGAINST(? IN BOOLEAN MODE)'] = $this->request->data['keyword'];

        $categories = $this->Category->getCategoriesListItem('Topic');
        $topics = $this->paginate('Topic', $cond);

        $this->set('topics', $topics);
        $this->set('categories', $categories);
        
        $this->set('title_for_layout', __('Topics Manager'));
    }

    public function admin_delete() {
        $this->_checkPermission(array('super_admin' => 1));

        if (!empty($_POST['topics'])) {
            
            $topics = $this->Topic->findAllById( $_POST['topics'] );
            
            foreach ($topics as $topic){
                
                $this->Topic->deleteTopic($topic);
                
                $cakeEvent = new CakeEvent('Plugin.Controller.Topic.afterDeleteTopic', $this, array('item' => $topic));
                $this->getEventManager()->dispatch($cakeEvent);
            }

            $this->Session->setFlash( __( 'Topics have been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ) );
        }

        $this->redirect( array(
            'plugin' => 'topic',
            'controller' => 'topic_plugins',
            'action' => 'admin_index'
        ) );
    }
    
    public function admin_move() {
        if (!empty($_POST['topics']) && !empty($this->request->data['category'])) {
            foreach ($_POST['topics'] as $topic_id) {
                $topic = $this->Topic->findById($topic_id);
                if (!empty($topic)) {
                    if (!empty($topic['Topic']['group_id'])) {
                        $this->Session->setFlash( __( 'Cannot move topics in groups, please un-check those topics'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ) );
                        $this->redirect( $this->referer() );
                    }
                }
                $this->Topic->id = $topic_id;
                $this->Topic->save(array('category_id' => $this->request->data['category']));
            }

            $this->Session->setFlash( __( 'Topic has been moved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ) );
        }

        $this->redirect($this->referer());
    }

}
