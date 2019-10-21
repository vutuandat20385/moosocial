<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class AdminNotificationsController extends AppController
{
    public $paginate = array('limit' => RESULTS_LIMIT);

	public function admin_ajax_view($id = null)
	{
        if(!empty($id))
        {
            $this->_checkPermission();

            $this->AdminNotification->id = $id;
            $notification = $this->AdminNotification->read();
            $this->AdminNotification->save( array( 'read' => 1 ) );

            if ( !empty( $notification['AdminNotification']['message'] ) )
                $this->set('notification', $notification);
            else
                $this->redirect( FULL_BASE_URL .  $notification['AdminNotification']['url'] );
        }
        else
            $this->redirect('/admin');
	}

	public function admin_ajax_clear()
	{
		$this->autoRender = false;
		$this->_checkPermission(array('super_admin' => 1));
		
		$this->AdminNotification->deleteAll( array('AdminNotification.id > 0') );
	}

    public function admin_index()
    {
        $cond = array();

        $requests = $this->paginate('AdminNotification', $cond);

        $this->set('requests', $requests);
        $this->set('title_for_layout', 'Admin Notifications');
    }

    public function admin_delete()
    {
        $this->_checkPermission(array('super_admin' => 1));

        if ( !empty( $_POST['requests'] ) )
        {
            $this->AdminNotification->deleteAll(array('AdminNotification.id' => $_POST['requests']));

            $this->Session->setFlash( __('Notifications have been deleted') , 'default', array('class' => 'Metronic-alerts alert alert-success fade in' ));
        }

        $this->redirect( array(
            'controller' => 'admin_notifications',
            'action' => 'admin_index'
        ) );
    }
}

