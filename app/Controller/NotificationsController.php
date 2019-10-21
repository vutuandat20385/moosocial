<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class NotificationsController extends AppController
{
	public $check_subscription = false;
	
	public function beforeFilter()
    {
		parent::beforeFilter();
		$this->loadModel('Notification');
	}

	public function ajax_show( $type = null )
	{
		$this->_checkPermission();		
		$uid = $this->Auth->user('id');

		$this->Notification->bindModel(
			array('belongsTo' => array(
					'Sender' => array(
						'className' => 'User',
						'foreignKey' => 'sender_id'
					)
				)
			)
		);
		$page = 1;
		
		$notifications = $this->Notification->find('all',array(
    		'conditions' => array('Notification.user_id'=>$uid),
    		'limit' => RESULTS_LIMIT,
    		'page' => $page,
    	));
    	
		$notifications_next = $this->Notification->find('all',array(
    		'conditions' => array('Notification.user_id'=>$uid),
    		'limit' => RESULTS_LIMIT,
    		'page' => $page + 1,
    	));
		$view_more = false;
		$view_more_url = '/notifications/ajax_show_more/page:' . ( $page + 1 );
    	if ($notifications_next)
    	{
    		$view_more = true;
    	}
		
    	$this->set('view_more',$view_more);
    	$this->set('view_more_url',$view_more_url);
		$this->set('notifications', $notifications);
		$this->set('type', $type);
	}
	
	public function ajax_show_more()
	{
		$page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
		$this->_checkPermission();		
		$uid = $this->Auth->user('id');
		
		$this->Notification->bindModel(
			array('belongsTo' => array(
					'Sender' => array(
						'className' => 'User',
						'foreignKey' => 'sender_id'
					)
				)
			)
		);
		
		$notifications = $this->Notification->find('all',array(
    		'conditions' => array('Notification.user_id'=>$uid),
    		'limit' => RESULTS_LIMIT,
    		'page' => $page,
    	));
    	
    	$notifications_next = $this->Notification->find('all',array(
    		'conditions' => array('Notification.user_id'=>$uid),
    		'limit' => RESULTS_LIMIT,
    		'page' => $page + 1,
    	));
		$view_more = false;
		$view_more_url = '/notifications/ajax_show_more/page:' . ( $page + 1 );
    	if ($notifications_next)
    	{
    		$view_more = true;
    	}
    	$this->set('view_more',$view_more);
    	$this->set('view_more_url',$view_more_url);
		$this->set('notifications', $notifications);
	}
        
        public function show() {

            $this->_checkPermission();
            $uid = $this->Auth->user('id');

            $this->Notification->bindModel(
                    array('belongsTo' => array(
                            'Sender' => array(
                                'className' => 'User',
                                'foreignKey' => 'sender_id'
                            )
                        )
                    )
            );
            $notifications = $this->Notification->findAllByUserId($uid);

            $this->set('notifications', array_slice($notifications,0, 10));
        }
        
        public function refresh(){
            $this->autoRender = false; 
            $cuser = $this->_getUser();
            $conversation_user_count = isset($cuser['conversation_user_count']) ? $cuser['conversation_user_count'] : 0;
            $notification_count = isset($cuser['notification_count']) ? $cuser['notification_count'] : 0;
            $data = array(
                'notification_count' => $notification_count,
                'conversation_count' => $conversation_user_count
            );
            $event = new CakeEvent('NotificationsController.refresh', $this,array('data' => $data,'user'=>$cuser));
            $this->getEventManager()->dispatch($event);
            if(!empty($event->result['data'])){
                $data = $event->result['data'];
            }
            echo json_encode($data);
        }

    public function ajax_view($id = null)
	{
		$id = intval($id);	
		$this->_checkPermission();

		$this->Notification->id = $id;
		$notification = $this->Notification->read();
		$this->_checkPermission( array( 'admins' => array( $notification['Notification']['user_id'] ) ) );

		$this->Notification->save( array( 'read' => 1 ) );
		$this->getEventManager()->dispatch(new CakeEvent('NotificationsController.beforeRedirectView', $this, array('notification'=>$notification)));		
		$link = $notification['Notification']['url'];
		if($this->isApp() && !empty($this->request->query['access_token']))
		{
			$link = $link.(strpos($link, '?') ? "&" : "?")."access_token=".$this->request->query['access_token'];
		}
		$this->redirect($link);
	}

	public function ajax_remove($id = null)
	{
		$id = intval($id);
		$this->autoRender = false;
		$this->_checkPermission();	

		$notification = $this->Notification->findById($id);
		$this->_checkPermission( array( 'admins' => array( $notification['Notification']['user_id'] ) ) );
		
		$this->Notification->delete($id);
	}
	
	public function ajax_clear()
	{ 
		$this->autoRender = false;	
		$this->_checkPermission();
		$uid = $this->Auth->user('id');
                
		$this->Notification->deleteAll( array( 'user_id' => $uid ), true, true );
		
		$this->loadModel("User");
		$this->User->id = $uid;
		$this->User->save(array('notification_count'=>0));
		
	}
        
        public function stop($item_type = null, $item_id = null){
            $this->loadModel('NotificationStop');
            $uid = $this->Auth->user('id');
            $current_type = $item_type;
            $current_id = $item_id;
            //hack one photo
            if ($item_type == 'activity')
            {
            	$this->loadModel('Activity');
            	$activity = $this->Activity->findById($item_id);
            	if (($activity['Activity']['item_type'] == 'Photo_Album' && $activity['Activity']['action'] == 'wall_post') ||
	              ($activity['Activity']['item_type'] == 'Photo_Photo' && $activity['Activity']['action'] == 'photos_add')) 
	            {
	            	 $photo_id = explode(',', $activity['Activity']['items']);
	            	 if (count($photo_id) == 1)
	            	 {
	            	 	$item_type = 'Photo_Photo';
	            	 	$item_id = $photo_id[0];
	            	 }
	            }
            }
            $count = $this->NotificationStop->find('count', array('conditions' => array('item_type' => $item_type,
                            'item_id' => $item_id,
                            'user_id' => $uid)
                            ));
            $this->set('notification_stop', $count);
            $this->set( 'item_type', $current_type );
            $this->set( 'item_id', $current_id );
        }
        
        public function ajax_save() {
            $this->autoRender = false;
            $this->_checkPermission();
            $uid = $this->Auth->user('id');
            $this->loadModel('NotificationStop');

            if (!empty($this->request->data)) {
                
                $uid = $this->Auth->user('id');

                $this->request->data['user_id'] = $uid;
				if ($this->request->data['item_type'] == 'activity')
				{
					$this->loadModel('Activity');
					$activity = $this->Activity->findById($this->request->data['item_id']);
					if (($activity['Activity']['item_type'] == 'Photo_Album' && $activity['Activity']['action'] == 'wall_post') ||
					  ($activity['Activity']['item_type'] == 'Photo_Photo' && $activity['Activity']['action'] == 'photos_add')) 
					{
						 $photo_id = explode(',', $activity['Activity']['items']);
						 if (count($photo_id) == 1)
						 {
							$this->request->data['item_type'] = 'Photo_Photo';
							$this->request->data['item_id'] = $photo_id[0];
						 }
					}
				}
				
				
				
                $this->NotificationStop->set($this->request->data);

                $count = $this->NotificationStop->find('count', array('conditions' => array('item_type' => $this->request->data['item_type'],
                        'item_id' => $this->request->data['item_id'],
                        'user_id' => $uid)
                        ));
                $response = array();
                if ($count){
                    $this->NotificationStop->deleteAll(array('NotificationStop.item_type' => $this->request->data['item_type'], 
                        'NotificationStop.item_id' => $this->request->data['item_id'],
                        'NotificationStop.user_id' => $uid));
                    $response['result'] = 1;
                    $response['is_stop'] = 0;
                    $response['message'] = __("You'll get notifications whenever this activity has new activity.");
                }
                else if ($this->NotificationStop->save()) { // successfully saved	
                    $response['result'] = 1;
                    $response['is_stop'] = 1;
                    $response['message'] = __("You'll no longer get notifications about this activity.");
                }
                
                // clear cache
                Cache::clearGroup('topic', 'topic');
                echo json_encode($response);
            }
        }
        
        public function mark_read(){
            $this->autoRender = false;
            $id = isset($this->request->data['id']) ? $this->request->data['id'] : 0;
            $status = isset($this->request->data['status']) ? $this->request->data['status'] : 0;
            $viewer_id = MooCore::getInstance()->getViewer(true);
            $this->loadModel('Notification');
            
            $notifications = $this->Notification->find('all', array('conditions' => array('Notification.id' => $id, 'Notification.user_id' => $viewer_id)));
            foreach ($notifications as $item){
                $this->Notification->clear();
                $this->Notification->id = $item['Notification']['id'];
                $this->Notification->save(array('read' => $status));
            }
                 
            echo json_encode(array('success' => true, 'status' => $status));
        }
        
        public function mark_all_read(){
            $this->autoRender = false;
            $viewer_id = MooCore::getInstance()->getViewer(true);
            $this->loadModel('Notification');
            $notifications = $this->Notification->find('all', array('conditions' => array('Notification.user_id' => $viewer_id)));
            
            foreach ($notifications as $item){
                $this->Notification->clear();
                $this->Notification->id = $item['Notification']['id'];
                $this->Notification->save(array('read' => 1));
            }
            
            $this->loadModel("User");
            $this->User->id = $viewer_id;
            $this->User->save(array('notification_count'=>0));
            
            echo json_encode(array('success' => true));
        }
        
         public function clear_all_notifications(){
            $viewer_id = MooCore::getInstance()->getViewer(true);
            $this->loadModel('Notification');
            $notifications = $this->Notification->find('all', array('conditions' => array('Notification.user_id' => $viewer_id)));
            
            foreach ($notifications as $item){
                $this->Notification->clear();
                $this->Notification->delete($item['Notification']['id']);
            }
            
            $this->loadModel("User");
            $this->User->id = $viewer_id;
            $this->User->save(array('notification_count'=>0));
            
        }

}

