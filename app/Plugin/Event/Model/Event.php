<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('EventAppModel','Event.Model');
class Event extends EventAppModel {
	public $mooFields = array('title','href','plugin','type','url', 'thumb', 'privacy');
	
    public $actsAs = array(
        'Activity' => array(
            'type' => 'user',
            'action_afterCreated'=>'event_create',
            'item_type'=>'Event_Event',
            'query'=>1,
            'params' => 'item',
    		'privacy_field' => 'type',
    		'default_privacy' => array(
    			'1' => PRIVACY_EVERYONE,
				'2' => PRIVACY_ME
    		)
        ),
        'MooUpload.Upload' => array(
            'photo' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}events{DS}{field}{DS}',
            )
        ),
        'Hashtag'=>array(
            'field_created_get_hashtag'=>'description',
            'field_updated_get_hashtag'=>'description',
        ),
        'Storage.Storage' => array(
            'type'=>array('events'=>'photo'),
        ),
    );
	public $belongsTo = array( 'User' => array('counterCache' => true),
                               'Category' => array( 'counterCache' => 'item_count', 
                                                    'counterScope' => array( 'Event.type' => PRIVACY_PUBLIC,
                                                                             'Category.type' => 'Event') )
                    );

	public $hasMany = array( 'Activity' => array(
											'className' => 'Activity',
											'foreignKey' => 'target_id',
											'conditions' => array('Activity.type' => 'Event_Event'),
											'dependent'=> true
										),
						  	 'EventRsvp' => array(
						  					'className' => 'Event.EventRsvp',
						  					'dependent'=> true
										),
						);

	public $order = 'Event.from asc';

	public $validate = array(
							'title' => 	array(
								'rule' => 'notBlank',
								'message' => 'Title is required'
							),
							'category_id' =>     array(
                                'rule' => 'notBlank',
                                'message' => 'Category is required'
                            ),
							'location' => array(
								'rule' => 'notBlank',
								'message' => 'Location is required'
							),
							'from' => 	array(
								'rule' => array('date','ymd'),
								'message' => 'From is not a valid date format (yyyy-mm-dd)',
								'allowEmpty' => false
							),
							'to' => 	array(
								'rule' => array('date','ymd'),
								'message' => 'To is not a valid date format (yyyy-mm-dd)',
								'allowEmpty' => false
							),
							'description' => array(
								'rule' => 'notBlank',
								'message' => 'Description is required'
							),
							'user_id' => array( 'rule' => 'notBlank')
	);

	/*
	 * Get events based on type
	 * @param string $type - possible value: index (default), past
	 * @param int $page - page number
	 * @return array $events
	 */
	public function getEvents($type = null, $param = null, $page = 1, $role_id = null, $event_id = null) {
            $pp = Configure::read('Event.event_item_per_pages');
            $limit = (!empty($pp)) ? $pp : RESULTS_LIMIT;
            $viewer = MooCore::getInstance()->getViewer();
            $viewer_id = MooCore::getInstance()->getViewer(true);
            $isAdmin = isset($viewer['Role']['is_admin']) ? $viewer['Role']['is_admin'] : false;
            $cond = array();

            switch ($type) {
                // Get all past events that have public view access
                case 'category':
                    if ($isAdmin) {
                        $cond = array(
                            'Event.category_id' => $param,
                            //'Event.to >= CURDATE()'
                        );
                    } else {
                        $cond = array('Event.category_id' => $param,
                            //'Event.to >= CURDATE()',
                            'Event.type' => PRIVACY_PUBLIC
                        );
                    }
                    break;

                // Get all past events that have public view access
                case 'past':
                    if ($isAdmin) {
                        $cond = array(
                            'Event.to < CURDATE()'
                        );
                    } else {
                        $cond = array('Event.to < CURDATE()',
                            'Event.type' => PRIVACY_PUBLIC
                        );
                    }
                    break;

                case 'search':
                    if ($isAdmin){
                    	$cond = array('Event.title LIKE'=>$param . "%");
                    }else{                       
                        // get curent viewer
                        $eventRsvpModel = MooCore::getInstance()->getModel('Event.EventRsvp');
                        $joinedEvents = $eventRsvpModel->getMyEventsList($viewer_id);
                        $cond = array(
                            'OR' => array(
                                array(
                                	'Event.title LIKE' => $param . '%', 
                                    'Event.type' => PRIVACY_EVERYONE
                                    ),
                                array(
                                	'Event.title LIKE' => $param . '%',
                                    'Find_In_Set(Event.id,"' . $event_id . '")'
                                ),
                                array(
                                	'Event.title LIKE' => $param . '%',
                                    'Event.id' => $joinedEvents
                                )
                            )
                        );
                        
                    }
                    break;

                case 'user':
                    if ($param) {
                        $eventRsvpModel = MooCore::getInstance()->getModel('Event.EventRsvp');
                        $joinedEvents = $eventRsvpModel->getMyEventsList($param);
                        if ($isAdmin || $param == $viewer_id){ //viewer is admin or owner himself
                            $cond = array(
                                'OR' => array(
                                    array(
                                        'Event.user_id' => $param
                                        ),
                                    array(
                                        'Event.id' => $joinedEvents
                                    )
                                )
                            );
                        }
                        else{ // normal viewer
                            $cond = array(
                                'OR' => array(
                                    array(
                                        'Event.user_id' => $param,
                                        'Event.type' => PRIVACY_PUBLIC
                                        ),
                                    array(
                                        'Event.id' => $joinedEvents,
                                        'Event.type' => PRIVACY_PUBLIC
                                    )
                                )
                            );
                        }
                    }
                    
                    break;
                case 'upcoming':
                    
                    if ($isAdmin) {
                        $cond = array();
                    } else { 
                        $cond = array(
                            'OR' => array(
                                array(
                                    'Event.type' => PRIVACY_PUBLIC,
                                ),
                                array(
                                    'Event.user_id' => $param,
                                ),
                                array(
                                    'Find_In_Set(Event.id,"' . $event_id . '")',
                                )
                            ),
                        );
                    }
                    $cond[] = array(
                        'Event.to >= CURDATE()'
                    );
                    break;

                default:
                    if ($isAdmin) {
                        $cond = array();
                    } else { 
                        $cond = array(
                            'OR' => array(
                                array(
                                    'Event.type' => PRIVACY_PUBLIC,
                                ),
                                array(
                                    'Event.user_id' => $param
                                ),
                                array(
                                    'Find_In_Set(Event.id,"' . $event_id . '")',
                                )
                            ),
                        );
                    }
            }

            //get events of active user
            $cond['User.active'] = 1;
            //$cond = $this->addBlockCondition($cond);
            $events = $this->find('all', array('conditions' => $cond, 'limit' => $limit, 'page' => $page));

            return $events;
        }

        public function getUpcoming($limit = 5){
        $cond = array( 'Event.to >= CURDATE()',
            'Event.type' => PRIVACY_PUBLIC,
            'User.active' => 1
        );
        //$cond = $this->addBlockCondition($cond);
        $events = $this->find( 'all', array( 'conditions' => $cond, 'limit' => intval($limit) ) );
        return $events;
    }
	/*
	 * Get popular public events
	 * @return array $events
	 */
	public function getPopularEvents( $limit = 5, $days = null )
	{
		$cond = array( 'Event.to >= CURDATE()', 'Event.type' => PRIVACY_PUBLIC );

		if ( !empty( $days ) )
			$cond['DATE_ADD(CURDATE(),INTERVAL ? DAY) >= Event.to'] = intval($days);

        //get events of active user
        $cond['User.active'] = 1;
        //$cond = $this->addBlockCondition($cond);
		$events = $this->find( 'all', array( 'conditions' => $cond,
											 'order' => 'Event.event_rsvp_count desc',
											 'limit' => intval($limit)
							 ) 	);
		return $events;
	}

	public function deleteEvent( $event )
	{
        $activityModel = MooCore::getInstance()->getModel('Activity');
        // delete activity attend
        $rsvpModel = MooCore::getInstance()->getModel("Event.EventRsvp");
        $rsvps = $rsvpModel->find('all',array(
            'conditions'=>array(
                'EventRsvp.event_id' => $event['Event']['id'],
                'EventRsvp.rsvp' => RSVP_ATTENDING
            )
        ));
        foreach ($rsvps as $rsvp)
        {
            $user_id = $rsvp['EventRsvp']['user_id'];
            $activity = $activityModel->getRecentActivity('event_attend', $user_id);
            if ($activity) {
                $items = array_filter(explode(',',$activity['Activity']['items']));
                $items = array_diff($items,array($event['Event']['id']));

                if (!count($items))
                {
                    $activityModel->delete($activity['Activity']['id']);
                }
                else
                {
                    $activityModel->id = $activity['Activity']['id'];
                    $activityModel->save(
                        array('items' => implode(',',$items))
                    );
                }
            }
        }
        // delete activity
        $parentActivity = $activityModel->find('list', array('fields' => array('Activity.id') , 'conditions' =>
            array('Activity.item_type' => 'Event_Event', 'Activity.item_id' => $event['Event']['id'])));

        $activityModel->deleteAll( array( 'Activity.item_type' => 'Event_Event', 'Activity.item_id' => $event['Event']['id'] ), true, true );
        $activityModel->deleteAll( array( 'Activity.target_id' => $event['Event']['id'], 'Activity.type' => 'Event' ), true, true );

        // delete child activity
        $activityModel->deleteAll(array('Activity.item_type' => 'Event_Event', 'Activity.parent_id' => $parentActivity));

        $this->delete( $event['Event']['id'] );
	}

    public function countEventByCategory($category_id){
        $num_events = $this->find('count',array(
            'conditions' => array(
                'Event.category_id' => $category_id,
                'User.active' => 1
            )
        ));
        return $num_events;
    }

    public function getEventHashtags($qid, $limit = RESULTS_LIMIT,$page = 1){
        $cond = array(
            'Event.id' => $qid,
        );

        //get events of active user
        $cond['User.active'] = 1;
        //$cond = $this->addBlockCondition($cond);
        $events = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
        return $events;
    }

    public function afterSave($creates,$options = array()){
        Cache::clearGroup('event');
    }
    public function afterDelete(){
        Cache::clearGroup('event');
    }
    
    public function getHref($row)
    {
    	$request = Router::getRequest();
    	if (isset($row['title']) && isset($row['id']))
    		return $request->base.'/events/view/'.$row['id'].'/'.seoUrl($row['title']);
    	else 
    		return '';
    }
    
    public function getThumb($row){

        return 'photo';
    }
    
    public function getPrivacy($row){
        if (isset($row['type'])){
            return $row['type'];
        }
        return false;
    }

    public function getTitle(&$row)
    {
        if (isset($row['title']))
        {
            $row['title'] = htmlspecialchars($row['title']);
            return $row['title'];
        }
        return '';
    }

}
