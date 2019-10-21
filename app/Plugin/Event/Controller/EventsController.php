<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');

class EventsController extends EventAppController
{

    public $paginate = array(
        'order' => array(
            'Event.id' => 'desc'
        ),
        'findType' => 'translated',
    );

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->loadModel('Event.Event');
    }

    public function index($cat_id = null)
    {
        $cat_id = intval($cat_id);

        $event = new CakeEvent('Plugin.Controller.Event.index', $this);
        $this->getEventManager()->dispatch($event);

        $this->loadModel('Event.EventRsvp');
        $eventId = $this->EventRsvp->findAllByUserId($this->Auth->user('id'), array('event_id'));
        if (!empty($eventId)) {
            $eventId = implode(',', Hash::extract($eventId, '{n}.EventRsvp.event_id'));
        } else {
            $eventId = '';
        }

        $role_id = $this->_getUserRoleId();
        $more_result = 0;
        if (!empty($cat_id)) {
            $events = $this->Event->getEvents('category', $cat_id);
            $more_events = $this->Event->getEvents('category', $cat_id, 2);
        } else {
            $events = $this->Event->getEvents('upcoming', $this->Auth->user('id'), 1, $role_id, $eventId);
            $more_events = $this->Event->getEvents('upcoming', $this->Auth->user('id'), 2, $role_id, $eventId);
        }

        if (!empty($more_events)) $more_result = 1;
        //$events = Hash::sort($events, '{n}.Event.from');
        $this->set('events', $events);
        $this->set('cat_id', $cat_id);
        $this->set('title_for_layout', '');
        $this->set('more_result', $more_result);
    }

    /*
	 * Browse events based on $type
	 * @param string $type - possible value: all (default), my, home, friends, past
	 */
    public function browse($type = null, $param = null,$isRedirect = true)
    {

            if($isRedirect) {
                    $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            }
            else {
                $page = $this->request->query('page') ? $this->request->query('page') : 1;
            }
            $url = (!empty($param)) ? $type . '/' . $param : $type;
            $role_id = $this->_getUserRoleId();
            $more_result = 0;

            switch ($type) {
                case 'home':
                case 'my':
                case 'mypast':
                case 'friends':
                    $this->_checkPermission();
                    $uid = $this->Auth->user('id');

                    $this->loadModel('Event.EventRsvp');
                    $events = $this->EventRsvp->getEvents($type, $uid, $page, $role_id);
                    $more_events = $this->EventRsvp->getEvents($type, $uid, $page + 1, $role_id);

                    break;
                default: // all, past, category
                    $this->loadModel('Event.EventRsvp');
                    $eventId = $this->EventRsvp->findAllByUserId($this->Auth->user('id'), array('event_id'));
                    if (!empty($eventId)) {
                        $eventId = implode(',', Hash::extract($eventId, '{n}.EventRsvp.event_id'));
                    } else
                        $eventId = '';


                    $events = $this->Event->getEvents($type, $param, $page, $role_id, $eventId);
                    //$events = Hash::sort($events, '{n}.Event.from', ' asc');
                    $more_events = $this->Event->getEvents($type, $param, $page + 1, $role_id, $eventId);
            }

            if (!empty($more_events)) $more_result = 1;
            $this->set('events', $events);
            $this->set('more_url', '/events/browse/' . h($url) . '/page:' . ($page + 1));
            $this->set('more_result', $more_result);

        if($isRedirect &&  $this->theme != "mooApp"){
            if ($page == 1 && $type == 'home') {
                $this->render('/Elements/ajax/home_event');
            } else {
                if ($this->request->is('ajax')) {
                    $this->render('/Elements/lists/events_list');
                } else {
                    $this->render('/Elements/lists/events_list_m');
                }
            }
        }
        else {
            if($type == 'category') $this->set('categoryId', $param);
            $this->set('type', $type);
        }
    }

    /*
     * Show add/edit event form
     * @param int $id - event id to edit
     */
    public function create($id = null)
    {
        $id = intval($id);
        $this->_checkPermission(array('confirm' => true));
        $this->_checkPermission(array('aco' => 'event_create'));

        $event = new CakeEvent('Plugin.Controller.Event.create', $this);
        $this->getEventManager()->dispatch($event);

        if (!empty($id)) // editing
        {
            $event = $this->Event->findById($id);
            $this->_checkExistence($event);
            $this->_checkPermission(array('admins' => array($event['User']['id'])));

            $this->set('title_for_layout', __('Edit Event'));
        } else // adding new event
        {
            $event = $this->Event->initFields();
            $this->set('title_for_layout', __('Add New Event'));
        }

        $this->set('event', $event);
    }

    /*
     * Save add/edit form
     */
    public function save($isReturn = false)
    {
        $this->_checkPermission(array('confirm' => true));

        $this->autoRender = false;
        $uid = $this->Auth->user('id');
        if (!empty($this->request->data['id'])) {
            // check edit permission
            $event = $this->Event->findById($this->request->data['id']);
            $this->_checkExistence($event);
            $this->_checkPermission(array('admins' => array($event['User']['id'])));
            $this->Event->id = $this->request->data['id'];
        } else
            $this->request->data['user_id'] = $uid;

        $this->Event->set($this->request->data);

        $this->_validateData($this->Event);

        if ($this->Event->save()) // successfully saved
        {
            //update field 'type' again because conflict with upload behavior
            $this->Event->id;
            $this->Event->save(array('type' => $this->request->data['type'], 'id' => $this->Event->id));

            if (empty($this->request->data['id'])) // add event
            {
                // rsvp the creator
                $this->loadModel('Event.EventRsvp');
                $this->EventRsvp->save(array('user_id' => $uid, 'event_id' => $this->Event->id, 'rsvp' => RSVP_ATTENDING));

                $event = new CakeEvent('Plugin.Controller.Event.afterSaveEvent', $this, array(
                    'uid' => $uid,
                    'id' => $this->Event->id,
                    'type' => $this->request->data['type']));

                $this->getEventManager()->dispatch($event);

            }
            if(!$isReturn) {
                $response['result'] = 1;
                $response['id'] = $this->Event->id;
                echo json_encode($response);
            }
            else {
                return $this->Event->id;
            }
        }
    }

    /*
     * View Event
     * @param int $id - event id to view
     */
    public function view($id = null,$name = null, $invite_checksum = null,$isRedirect = true)
    {
        $id = intval($id);
        $uid = $this->Auth->user('id');

        $this->Event->recursive = 2;
        $event= $this->Event->findById($id);
        if ($event['Category']['id'])
        {
        	foreach ($event['Category']['nameTranslation'] as $translate)
        	{
        		if ($translate['locale'] == Configure::read('Config.language'))
        		{
        			$event['Category']['name'] = $translate['content'];
        			break;
        		}
        	}
        }
        $this->Event->recursive = 0;

        $this->_checkExistence($event);
        $role_id = $this->_getUserRoleId();
        $this->_checkPermission(array('aco' => 'event_view'));
        //$this->_checkPermission( array('user_block' => $event['Event']['user_id']) );
        $this->loadModel('Event.EventRsvp');

        if ($uid) {
            $my_rsvp = Cache::read('eventrsvp.myrsvp.' . $uid . '.event.' . $id, 'event');
            if (empty($my_rsvp)) {
                $my_rsvp = $this->EventRsvp->getMyRsvp($uid, $id);
                Cache::write('eventrsvp.myrsvp.' . $uid . '.event.' . $id, $my_rsvp, 'event');
            }
            $this->set('my_rsvp', $my_rsvp);
        }
        $isExist = null;
        if($invite_checksum != null)
        {
        	$eventUserInviteModel = MooCore::getInstance()->getModel('Event.EventUserInvite');
        	$isExist = $eventUserInviteModel->find('first',array(
        		'conditions' => array(
        			'EventUserInvite.event_id' => $id,
        			'EventUserInvite.invite_checksum' => $invite_checksum,
        		)
        	));
        	if(!empty($isExist))
        	{
        		$this->Session->write('event_invite_checksum',$isExist['EventUserInvite']['invite_checksum']);
        	}
        }

        // check if user can view this event
        if (empty($my_rsvp) && $event['Event']['type'] == PRIVACY_PRIVATE && $role_id != ROLE_ADMIN && empty($isExist)) {
            if($isRedirect) {
                $this->redirect('/pages/no-permission');
            }
            else {
                $this->throwErrorCodeException('private_event');
                return $error = array(
                    'code' => 400,
                    'message' => __('This is private event . You do not have permission to view'),
                );

            }
        }

        $attending = array();
        $maybe = array();
        $not_attending = array();
        $awaiting = array();

        // get rsvp
        $awaiting = $this->EventRsvp->getRsvp($id, RSVP_AWAITING, null, 5);
        $attending = $this->EventRsvp->getRsvp($id, RSVP_ATTENDING, null, 5);
        $not_attending = $this->EventRsvp->getRsvp($id, RSVP_NOT_ATTENDING, null, 5);
        $maybe = $this->EventRsvp->getRsvp($id, RSVP_MAYBE, null, 5);

        $awaiting_count = $this->EventRsvp->getRsvpCount($id, RSVP_AWAITING);
        $attending_count = $this->EventRsvp->getRsvpCount($id, RSVP_ATTENDING);
        $not_attending_count = $this->EventRsvp->getRsvpCount($id, RSVP_NOT_ATTENDING);
        $maybe_count = $this->EventRsvp->getRsvpCount($id, RSVP_MAYBE);

        MooCore::getInstance()->setSubject($event);

        $cakeEvent = new CakeEvent('Plugin.Controller.Event.view', $this, array('id' => $id, 'uid' => $uid));
        $this->getEventManager()->dispatch($cakeEvent);

        $this->set(compact('attending', 'attending_count', 'maybe', 'maybe_count', 'not_attending', 'not_attending_count', 'awaiting', 'awaiting_count'));

        $this->set('event', $event);
        $this->set('title_for_layout', $event['Event']['title']);
        $description = $this->getDescriptionForMeta($event['Event']['description']);
        if ($description) {
            $this->set('description_for_layout', $description);
            $this->set('mooPageKeyword', $this->getKeywordsForMeta($description));
        }

        // set og:image
        if ($event['Event']['photo']) {
            $mooHelper = MooCore::getInstance()->getHelper('Core_Moo');
            $this->set('og_image', $mooHelper->getImageUrl($event, array('prefix' => '850')));
        }
        $this->set('eventActivities', $this->Feeds->get());
    }

    /*
     * RSVP event
     */
    public function do_rsvp($isRedirect = true)
    {
    	$uid = $this->Auth->user('id');
    	if (!$uid && $this->Session->read('event_invite_checksum'))
    	{
    		$this->Session->write('event_invite_status',$this->request->data['rsvp']);
    		$this->redirect('/users/register');
    	}
        $this->_checkPermission(array('confirm' => true));

        $role_id = $this->_getUserRoleId();
        $this->request->data['user_id'] = $uid;
        $event = $this->Event->findById($this->request->data['event_id']);
        //$this->_checkPermission( array('user_block' => $event['Event']['user_id']) );
        // find existing and update if necessary
        $this->loadModel('Event.EventRsvp');
        $my_rsvp = $this->EventRsvp->getMyRsvp($uid, $this->request->data['event_id']);

        // check if user was invited
        if (empty($my_rsvp) && $event['Event']['type'] == PRIVACY_PRIVATE && $role_id != ROLE_ADMIN) {
            if($isRedirect) {
                $this->redirect('/pages/no-permission');
            }
            else{
                $this->throwErrorCodeException('private_event');
                return $error = array(
                    'code' => 400,
                    'message' => __('This is private event . You do not have permission to view'),
                );
            }
        }

        if (!empty($my_rsvp)) {
            $this->EventRsvp->id = $my_rsvp['EventRsvp']['id'];

            // user changed rsvp from attending to something else
            if ($my_rsvp['Event']['type'] != PRIVACY_PRIVATE && $my_rsvp['EventRsvp']['rsvp'] == RSVP_ATTENDING && isset($this->request->data['rsvp']) && $this->request->data['rsvp'] != RSVP_ATTENDING) {
                $cakeEvent = new CakeEvent('Plugin.Controller.Event.changeRsvpFromAttending', $this, array('uid' => $uid, 'event_id' => $this->request->data['event_id']));
                $this->getEventManager()->dispatch($cakeEvent);
            }
        } else {
            // first time rsvp
            if ($event['Event']['type'] == PRIVACY_PUBLIC && isset($this->request->data['rsvp']) && $this->request->data['rsvp'] == RSVP_ATTENDING) // attending
            {
                $cakeEvent = new CakeEvent('Plugin.Controller.Event.firstTimeRsvp', $this, array('uid' => $uid, 'event' => $event));
                $this->getEventManager()->dispatch($cakeEvent);
            }
        }

        $this->EventRsvp->save($this->request->data);
        if($isRedirect) {
            $this->redirect('/events/view/' . $this->request->data['event_id']);
        }
    }

    /*
     * Show invite form
     * @param int $event_id
     */
    public function invite($event_id = null)
    {
        $event_id = intval($event_id);
        $this->_checkPermission(array('confirm' => true));

        $this->set('event_id', $event_id);
        $this->render('Event.Events/invite');
    }

    /*
     * Handle invite submission
     */
    public function sendInvite($isRedirect = true) {
        $this->autoRender = false;
        $this->_checkPermission(array('confirm' => true));
        $cuser = $this->_getUser();
        
        $event = $this->Event->findById($this->request->data['event_id']);

        // check if user can invite
        if ($event['Event']['type'] == PRIVACY_PRIVATE && ($cuser['id'] != $event['User']['id']))
        	if($isRedirect) return;
                else {
                    $this->throwErrorCodeException('private_event');
                    return $error = array(
                        'code' => 400,
                        'message' => __('This is private event . Can not invite'),
                    );
                }
        $this->loadModel('Event.EventRsvp');
        if ($this->request->data['invite_type_event'] == 1)
        {
        	if (!empty($this->request->data['friends'])) {
                $data = array();
                $friends = explode(',', $this->request->data['friends']);
                $rsvp_list = $this->EventRsvp->getRsvpList($this->request->data['event_id']);

                foreach ($friends as $friend_id)
                    if (!in_array($friend_id, $rsvp_list))
                        $data[] = array('event_id' => $this->request->data['event_id'], 'user_id' => $friend_id);

                if (!empty($data)) {
                    $this->EventRsvp->saveAll($data);

                    $cakeEvent = new CakeEvent('Plugin.Controller.Event.sentInvite', $this, array('friends' => $friends, 'cuser' => $cuser, 'event_id' => $this->request->data['event_id'], 'event' => $event));
                    $this->getEventManager()->dispatch($cakeEvent);

                }
            }
            else
            {
            	if($isRedirect) {
                    $this->_jsonError(__('Recipient is required'));
                }
                else {
                    return $error = array(
                        'code' => 400,
                        'message' => __('Recipient is required'),
                    );
                }
            }
        }
        else 
        {
        	if (!empty($this->request->data['emails'])) {
        		// check captcha
		        $checkRecaptcha = MooCore::getInstance()->isRecaptchaEnabled();
		        $recaptcha_privatekey = Configure::read('core.recaptcha_privatekey');
		        $is_mobile = $this->viewVars['isMobile'];
		        if ( $checkRecaptcha && !$is_mobile)
		        {
		            App::import('Vendor', 'recaptchalib');
		            $reCaptcha = new ReCaptcha($recaptcha_privatekey);
		            $resp = $reCaptcha->verifyResponse(
		                    $_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"]
		            );
					
		            if ($resp != null && !$resp->success) {
		                return	$this->_jsonError(__( 'Invalid security code'));
		            }
		        }
                $emails = explode(',', $this->request->data['emails']);

                $i = 1;
                $rsvp_list = $this->EventRsvp->getRsvpList($this->request->data['event_id']);
                $this->loadModel("User");
                foreach ($emails as $email) {
                    if ($i <= 10) {
                        if (Validation::email(trim($email))) {
                        	$user = $this->User->findByEmail(trim($email));
                        	if ($user)
                        	{
                        		if (!in_array($user['User']['id'], $rsvp_list))
                        		{
                        			$this->EventRsvp->clear();
                        			$this->EventRsvp->save(array(
                        				'event_id' => $this->request->data['event_id'], 'user_id' => $user['User']['id']
                        			));

                        			$cakeEvent = new CakeEvent('Plugin.Controller.Event.sentInvite', $this, array('friends' => $user['User']['id'], 'cuser' => $cuser, 'event_id' => $this->request->data['event_id'], 'event' => $event));
                        			$this->getEventManager()->dispatch($cakeEvent);

                        			$ssl_mode = Configure::read('core.ssl_mode');
                        			$http = (!empty($ssl_mode)) ? 'https' : 'http';
                        			$this->MooMail->send(trim($email), 'event_invite_none_member',
                        				array(
                        					'event_title' => $event['Event']['moo_title'],
                        					'event_link' => $http . '://' . $_SERVER['SERVER_NAME'] . $event['Event']['moo_href'],
                        					'email' => trim($email),
                        					'sender_title' => $cuser['name'],
                        					'sender_link' => $http . '://' . $_SERVER['SERVER_NAME'] . $cuser['moo_href'],
                        				)
                        			);
                        		}
                        		continue;
                        	}
                        	$invite_checksum = uniqid();
                        	$eventUserInvitedModel = MooCore::getInstance()->getModel('Event.EventUserInvite');
                        	$eventUserInvitedModel->create();
                        	$eventUserInvitedModel->set(array('event_id' => $this->request->data['event_id'],'invite_checksum' => $invite_checksum));
                        	$eventUserInvitedModel->save();

                            $ssl_mode = Configure::read('core.ssl_mode');
                            $http = (!empty($ssl_mode)) ? 'https' : 'http';
                            $this->MooMail->send(trim($email), 'event_invite_none_member',
                                array(
                                    'event_title' => $event['Event']['moo_title'],
                                    'event_link' => $http . '://' . $_SERVER['SERVER_NAME'] . $event['Event']['moo_href'].'/'.$invite_checksum,
                                    'email' => trim($email),
                                    'sender_title' => $cuser['name'],
                                    'sender_link' => $http . '://' . $_SERVER['SERVER_NAME'] . $cuser['moo_href'],
                                )
                            );


                        }
                    }
                    $i++;
                }
            }
            else
            {
            	if($isRedirect) {
                    $this->_jsonError(__('Recipient is required'));
                }
                else {
                    return $error = array(
                        'code' => 400,
                        'message' => __('Recipient is required'),
                    );
                }
            }
        }
        if($isRedirect) {
            $response = array();
            $response['result'] = 1;
            if($this->theme != "mooApp"){
                $response['msg'] = __('Your invitations have been sent.') . ' <a href="javascript:void(0)" onclick="$(\'#langModal .modal-content\').load(\'' . $this->request->base . '/events/invite/' . $this->request->data['event_id'] . '\');"">' . __('Invite more friends') . '</a>';
            }
            else {
                $response['msg'] = __('Your invitations have been sent.');
            }
            echo json_encode($response);
        }
    }

    /*
     * Show RSVP list
     * @param int $event_id
     */
    public function showRsvp($event_id = null, $rsvp_type = RSVP_ATTENDING,$isRedirect = true)
    {
        $event_id = intval($event_id);
        $this->loadModel('Event.EventRsvp');
        if($isRedirect) {
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        }
        else {
            $page = $this->request->query('page') ? $this->request->query('page') : 1;
        }

        $more_result = 0;
        $users = $this->EventRsvp->getRsvp($event_id, $rsvp_type, $page);
        $more_users = $this->EventRsvp->getRsvp($event_id, $rsvp_type, $page + 1);
        if (!empty($more_users))
            $more_result = 1;

        $rsvp_count = $this->EventRsvp->getRsvpCount($event_id, $rsvp_type);

        $this->set('more_url', '/events/showRsvp/' . $event_id . '/' . $rsvp_type . '/page:' . ($page + 1));
        $this->set(compact('users', 'page', 'rsvp_type', 'more_result', 'rsvp_count'));
        if($isRedirect) {
            $this->render('/Elements/ajax/user_overlay');
        }
    }

    /*
	 * Delete event
	 * @param int $id - event id to delete
	 */
    public function do_delete($id = null,$isRedirect = true)
    {
        $id = intval($id);
        $event = $this->Event->findById($id);
        $this->_checkExistence($event);
        $this->_checkPermission(array('admins' => array($event['User']['id'])));

        $this->Event->deleteEvent($event);

        $cakeEvent = new CakeEvent('Plugin.Controller.Event.afterDeleteEvent', $this, array('item' => $event));
        $this->getEventManager()->dispatch($cakeEvent);
        if($isRedirect) {
            $this->Session->setFlash(__('Event has been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
            $this->redirect('/events');
        }
    }

    public function popular()
    {
        if ($this->request->is('requested')) {
            $num_item_show = $this->request->named['num_item_show'];
            return $this->Event->getPopularEvents($num_item_show, Configure::read('core.popular_interval'));
        }
    }

    public function upcomingAll()
    {
        if ($this->request->is('requested')) {
            $num_item_show = $this->request->named['num_item_show'];
            return $this->Event->getUpcoming($num_item_show);
        }
    }

    public function upcomming()
    {
        if ($this->request->is('requested')) {
            $aid = $this->request->named['uid'];
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            $this->loadModel('Event.EventRsvp');
            $events = $this->EventRsvp->getEvents('my', $aid, $page);
            return $events;
        }
    }

    public function _getUserRoleId()
    {
        return parent::_getUserRoleId();
    }

    public function show_g_map($id = null)
    {
        if (!empty($id)) {
            $event = $this->Event->findById($id);
            $this->set('event', $event);
            $this->render('Event.Widgets/events/show_g_map');
        }
    }

    public function ajax_event_joined()
    {
        $activity_id = $this->request->named['activity_id'];
        $this->loadModel('Activity');
        $activity = $this->Activity->findById($activity_id);
        if (!empty($activity)) {
            $items = $activity['Activity']['items'];
            $ids = explode(',', $items);
            $this->loadModel('Event.Event');
            $events = $this->Event->find('all', array('conditions' => array(
                'Event.id' => $ids
            )));
            $this->set(compact('events'));
        }
        $this->render('/Elements/ajax/ajax_event_joined');
    }

    public function categories_list($isRedirect = true)
    {
        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();
        $categories = $this->Category->getCategories('Event', $role_id);
        if ($this->request->is('requested')) {
            return $categories;
        }
        if($isRedirect && $this->theme == "mooApp") {
            $this->render('/Elements/lists/categories_list');
        }
    }

    public function profile_user_event($uid = null,$isRedirect=true)
    {
        $uid = intval($uid);

        if($isRedirect) {
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        }
        else {
                $page = $this->request->query('page') ? $this->request->query('page') : 1;
        }

        $events = $this->Event->getEvents('user', $uid, $page);

        $more_events = $this->Event->getEvents('user', $uid, $page + 1);
        $more_result = 0;
        if (!empty($more_events))
            $more_result = 1;

        $this->set('events', $events);
        $this->set('more_url', '/events/profile_user_event/' . $uid . '/page:' . ($page + 1));
        $this->set('user_id', $uid);
        $this->set('user_event', true);
        $this->set('more_result', $more_result);

        if($isRedirect && $this->theme != "mooApp") {
                if ($page > 1)
                    $this->render('/Elements/lists/events_list');
                else
                    $this->render('Event.Events/profile_user_event');
        }
    }
}

?>
