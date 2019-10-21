<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class Notification extends AppModel {	

	public $belongsTo = array( 'User'  => array( 'counterCache' => true,
												 'counterScope' => array( 'Notification.read' => 0 )		
							) );
	
	public $validate = array( 'user_id' => array( 'rule' => 'notBlank'),
							  'sender_id' => array( 'rule' => 'notBlank'),
							  'action' => array( 'rule' => 'notBlank' ),
							  'url' => array( 'rule' => 'notBlank' )
						 );
							  
	public $order = 'Notification.id desc';
	
	public $limit = RESULTS_LIMIT;
	
	/*
	 * Record a notification
	 * @params Array $params
	 */
	public function record($params = array()) {
            if (empty($params['recipients'])){
                return;
            }

            if (empty($params['params'])){
                $params['params'] = '';
            }

            $data = array();
            if (!is_array($params['recipients'])){
                $params['recipients'] = array($params['recipients']);
            }

            foreach ($params['recipients'] as $recipient_id) { // save notification
                if(!empty($params['like_url'])){
                    $unread = $this->getUnreadNotification($recipient_id, $params['sender_id'], $params['like_url'], $params['action'], 1);
                }else{
                    $unread = $this->getUnreadNotification($recipient_id, $params['sender_id'], $params['url'], $params['action']);
                }

                if (!$unread){
                    $data[] = array('user_id' => $recipient_id,
                        'sender_id' => $params['sender_id'],
                        'action' => $params['action'],
                        'url' => $params['url'],
                        'params' => $params['params'],
                        'plugin' => isset($params['plugin']) ? $params['plugin'] : '' 
                    );
                }
            }

            if (!empty($data)){
                $this->saveAll($data);
            }
        }

        public function getRecentNotifications()
	{
		$this->bindModel(
			array('belongsTo' => array(
					'Sender' => array(
						'className' => 'User',
						'foreignKey' => 'sender_id'
					)
				)
			)
		);
		
		$time = date('Y-m-d H:i:s',strtotime('- '.Configure::read("core.summary_day_login").' days'));
		
		$notifications = $this->find( 'all', array( 'conditions' => array( 'User.notification_email' => 1,
																			'User.active' => 1,
																			'User.approved' => 1,
																			'User.confirmed' => 1,
																		   'Notification.read' => 0,
																  		   'DATE_SUB(CURDATE(),INTERVAL 1 DAY) <= Notification.created',
																			'User.last_login > ' => $time
									) ) );
		
		return $notifications;
	}

    public function getUnreadNotification( $uid, $sender_id , $url, $action, $check_like = 0)
    {
        $cond = array( 'Notification.user_id' => $uid,
            'Notification.sender_id' => $sender_id,
            'Notification.action' => $action,
            'Notification.read' => 0
        );
        if($check_like){
            $cond['Notification.url LIKE'] = $url.'%';
        }else{
            $cond['Notification.url'] = $url;
        }
        $noti = $this->find( 'count', array( 'conditions' => $cond) );
        return $noti;
    }

}
