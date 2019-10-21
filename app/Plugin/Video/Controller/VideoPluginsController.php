<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class VideoPluginsController extends VideoAppController{
    public function beforeFilter(){
        parent::beforeFilter();
        $this->loadModel("Video.Video");
    }
    public function admin_index()
    {
        if ( !empty( $this->request->data['keyword'] ) )
            $this->redirect( '/admin/video/video_plugins/index/keyword:' . $this->request->data['keyword'] );

        $cond = array();
        if ( !empty( $this->request->named['keyword'] ) )
            $cond['MATCH(Video.title) AGAINST(? IN BOOLEAN MODE)'] = $this->request->named['keyword'];

        $videos = $this->paginate( 'Video', $cond );

        $this->loadModel('Category');
        $categories = $this->Category->getCategoriesListItem( 'Video' );

        $this->set('videos', $videos);
        $this->set('categories', $categories);
        $this->set('title_for_layout', __('Videos Manager'));
    }

    public function admin_delete()
    {
        $this->_checkPermission(array('super_admin' => 1));

        if ( !empty( $_POST['videos'] ) )
        {
            $videos = $this->Video->findAllById( $_POST['videos'] );

            foreach ( $videos as $video ){
                $this->Video->deleteVideo( $video );
                
                $cakeEvent = new CakeEvent('Plugin.Controller.Video.afterDeleteVideo', $this, array('item' => $video));
                $this->getEventManager()->dispatch($cakeEvent);
            }

            $this->Session->setFlash( __( 'Videos have been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ) );
        }

        $this->redirect( array(
            'plugin' => 'video',
            'controller' => 'video_plugins',
            'action' => 'admin_index'
        ) );
    }

    public function admin_move()
    {
        if ( !empty( $_POST['videos'] ) && !empty( $this->request->data['category'] ) )
        {
            foreach ( $_POST['videos'] as $video_id )
            {
                $video = $this->Video->findById($video_id);
                if (!empty($video)) {
                    if (!empty($video['Video']['group_id'])) {
                        $this->Session->setFlash( __( 'Cannot move videos in groups, please un-check those videos'), 'default', array('class' => 'Metronic-alerts alert alert-danger fade in' ) );
                        $this->redirect( $this->referer() );
                    }
                }
                $this->Video->id = $video_id;
                $this->Video->save( array( 'category_id' => $this->request->data['category'] ) );
            }

            $this->Session->setFlash( __( 'Videos moved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ) );
        }

        $this->redirect( $this->referer() );
    }
}