<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class EventPluginsController extends EventAppController{

    public function beforeFilter(){
        parent::beforeFilter();
        $this->loadModel('Event.Event');
    }
    public function admin_index()
    {

        if ( !empty( $this->request->data['keyword'] ) )
            $this->redirect( '/admin/event/event_plugins/index/keyword:' . $this->request->data['keyword'] );

        $cond = array();
        if ( !empty( $this->request->named['keyword'] ) )
            $cond['MATCH(Event.title) AGAINST(? IN BOOLEAN MODE)'] = $this->request->named['keyword'];

        $events = $this->paginate( 'Event', $cond );

        $this->loadModel('Category');
        $categories = $this->Category->getCategoriesListItem( 'Event' );


        $this->set('events', $events);
        $this->set('categories', $categories);
        $this->set('title_for_layout', __('Events Manager'));
    }

    public function admin_delete()
    {
        $this->_checkPermission(array('super_admin' => 1));

        if ( !empty( $_POST['events'] ) )
        {
            $events = $this->Event->findAllById( $_POST['events'] );

            foreach ( $events as $event ){
                $this->Event->deleteEvent( $event );
                
                $cakeEvent = new CakeEvent('Plugin.Controller.Event.afterDeleteEvent', $this, array('item' => $event));
                $this->getEventManager()->dispatch($cakeEvent);
            }

            $this->Session->setFlash( __('Events have been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' )  );
        }

        $this->redirect( array(
            'plugin' => 'event',
            'controller' => 'event_plugins',
            'action' => 'admin_index'
        ) );
    }

    public function admin_move()
    {
        if ( !empty( $_POST['events'] ) && !empty( $this->request->data['category'] ) )
        {
            foreach ( $_POST['events'] as $event_id )
            {
                $this->Event->id = $event_id;
                $this->Event->save( array( 'category_id' => $this->request->data['category'] ) );
            }

            $this->Session->setFlash( __('Events moved'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ) );
        }

        $this->redirect( $this->referer() );
    }
}