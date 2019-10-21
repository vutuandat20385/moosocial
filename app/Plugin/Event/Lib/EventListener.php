<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class EventListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'Plugin.Controller.Event.index' => 'processEventIndex',
            'Plugin.Controller.Event.create' => 'processEventCreate',
            'Plugin.Controller.Event.afterSaveEvent' => 'processEventAfterSave',
            'Plugin.Controller.Event.view' => 'processEventView',
            'Plugin.Controller.Event.changeRsvpFromAttending' => 'processEventChangeRsvp',
            'Plugin.Controller.Event.firstTimeRsvp' => 'processEventFirstTimeRsvp',
            'Plugin.Controller.Event.sentInvite' => 'processEventSentInvite',
            'UserController.deleteUserContent' => 'deleteUserContent',
            'Controller.Search.search' => 'search',
            'Model.Activity.afterSetParamsConditionsOr' => 'afterSetParamsConditionsOr',
            'Controller.Search.suggestion' => 'suggestion',
            'Controller.Search.hashtags' => 'hashtags',
            'Controller.Search.hashtags_filter' => 'hashtags_filter',
            'Controller.Widgets.tagCoreWidget' => 'hashtagEnable',
            'Controller.User.deactivate' => 'deactivate',
            'MooView.beforeRender' => 'beforeRender',
            'Controller.User.afterDeactive' => 'afterDeactiveUser',
            'Controller.User.afterEdit' => 'afterEditUser',
            'UserController.doSaveUser' => 'doSaveUser',
        );
    }

    public function doSaveUser($event)
    {
        $controller = $event->subject();
        $data = isset($event->data['data']) ? $event->data['data'] : '';
        //if user already have some group's invites
        if ($controller->Session->read('event_invite_checksum') )
        {
            $eventUserInviteModel = MooCore::getInstance()->getModel('Event.EventUserInvite');
            $invite = $eventUserInviteModel->find('first', array(
                'conditions' => array(
                    'invite_checksum' => $controller->Session->read('event_invite_checksum')
                ),
            ));
            if (!empty($invite)) {
                $eventRsvpModel = MooCore::getInstance()->getModel('Event.EventRsvp');
                $eventRsvpModel->clear();
                $eventRsvpModel->save(array(
                    'event_id' => $invite['EventUserInvite']['event_id'], 'user_id' => $controller->User->id, 'rsvp' => $controller->Session->read('event_invite_status') ? $controller->Session->read('event_invite_status') : 0
                ));
                $eventUserInviteModel->delete($invite['EventUserInvite']['id']);
                $controller->Session->delete('event_invite_checksum');
                $controller->Session->delete('event_invite_status');
            }
            //Auto add this user to group if this user was invited via email and sign up after join group
            return;
        }
    }

    public function afterEditUser($event)
    {
        $cuser = $event->data['item'];

        $categoryModel = Moocore::getInstance()->getModel('Category');
        $eventModel = Moocore::getInstance()->getModel('Event.Event');
        $eventCategory = $categoryModel->find('all', array('conditions' => array('Category.type' => 'Event')));

        foreach ($eventCategory as $item) {
            $category_id = $item['Category']['id'];
            $events_count = $eventModel->find('count', array('conditions' => array(
                'Event.category_id' => $category_id,
                'User.active' => true
            )));
            $categoryModel->updateAll(array('Category.item_count' => $events_count), array('Category.id' => $category_id));
        }
    }

    public function afterDeactiveUser($event)
    {
        $cuser = $event->data['item'];

        $categoryModel = Moocore::getInstance()->getModel('Category');
        $eventModel = Moocore::getInstance()->getModel('Event.Event');
        $eventCategory = $categoryModel->find('all', array('conditions' => array('Category.type' => 'Event')));

        foreach ($eventCategory as $item) {
            $category_id = $item['Category']['id'];
            $events_count = $eventModel->find('count', array('conditions' => array(
                'Event.category_id' => $category_id,
                'User.active' => true
            )));
            $categoryModel->updateAll(array('Category.item_count' => $events_count), array('Category.id' => $category_id));
        }
    }


    public function beforeRender($event)
    {
        $view = $event->subject();
        if ($view instanceof MooView) {
            $view->addPhraseJs(array(
                'drag_or_click_here_to_upload_photo' => __("Drag or click here to upload photo"),
                'january' => __('January'),
                'february' => __('February'),
                'march' => __('March'),
                'april' => __('April'),
                'may' => __('May'),
                'june' => __('June'),
                'july' => __('July'),
                'august' => __('August'),
                'september' => __('September'),
                'october' => __('October'),
                'november' => __('November'),
                'december' => __('December'),
                'jan' => __('Jan'),
                'feb' => __('Feb'),
                'mar' => __('Mar'),
                'apr' => __('Apr'),
                'may' => __('May'),
                'jun' => __('Jun'),
                'jul' => __('Jul'),
                'aug' => __('Aug'),
                'sep' => __('Sep'),
                'oct' => __('Oct'),
                'nov' => __('Nov'),
                'dec' => __('Dec'),
                'sunday' => __('Sunday'),
                'monday' => __('Monday'),
                'tuesday' => __('Tuesday'),
                'wednesday' => __('Wednesday'),
                'thursday' => __('Thursday'),
                'friday' => __('Friday'),
                'saturday' => __('Saturday'),
                'sun' => __('Sun'),
                'mon' => __('Mon'),
                'tue' => __('Tue'),
                'wed' => __('Wed'),
                'thu' => __('Thu'),
                'fri' => __('Fri'),
                'sat' => __('Sat'),
                'today' => __('Today'),
                'clear' => __('Clear'),
                'close' => __('Close'),
                'to_date_must_be_greater_than_from_date' => __('To date must be greater than From date'),
                'to_time_must_be_greater_than_from_time' => __('To time must be greater than From time'),
                'enter_a_friend_s_name' => __('Enter a friend\'s name'),
                'no_results' => __('No results'),
                'are_you_sure_you_want_to_remove_this_event' => __('Are you sure you want to remove this event?'),


            ));
        }
    }

    function afterSetParamsConditionsOr($event)
    {
        $eventModel = MooCore::getInstance()->getModel("Event.Event");

        $eventRSVPModel = MooCore::getInstance()->getModel("Event.EventRsvp");
        $events = $eventRSVPModel->getMyEventsList($event->data['param']);
        if (!count($events))
            $events = array(0);

        $data = array(array('Activity.type' => 'Event_Event', 'Activity.target_id' => $events));
        $event->result[] = $data;
    }

    public function processEventIndex($event)
    {
        $v = $event->subject();

    }

    public function processEventCreate($event)
    {
        $v = $event->subject();
        $this->Category = ClassRegistry::init('Category');
        $role_id = $v->_getUserRoleId();
        $categories = $this->Category->getCategoriesList('Event', $role_id);
        $v->set('categories', $categories);
    }

    public function processEventAfterSave($event)
    {
        // load feed model
        $this->Activity = ClassRegistry::init('Activity');

        // find activity which belong to event just created
        $activity = $this->Activity->find('first', array('conditions' => array(
            'Activity.item_type' => 'Event_Event',
            'Activity.item_id' => $event->data['id'],
            'Activity.type' => 'Event_Event'
        )));

        if (!empty($activity)) {
            $share = false;
            // only enable share feature for public event
            if ($event->data['type'] == PRIVACY_PUBLIC) {
                $share = true;
            }
            $this->Activity->clear();
            $this->Activity->updateAll(array('Activity.share' => $share), array('Activity.id' => $activity['Activity']['id']));
        }

    }

    public function processEventView($event)
    {
        $v = $event->subject();
    }

    public function processEventChangeRsvp($event)
    {
        $v = $event->subject();
        // remove associated activity
        $this->Activity = ClassRegistry::init('Activity');

        $activity = $this->Activity->getRecentActivity('event_attend', $event->data['uid']);

        if ($activity) {
            $items = array_filter(explode(',', $activity['Activity']['items']));
            $items = array_diff($items, array($event->data['event_id']));

            if (!count($items)) {
                $this->Activity->delete($activity['Activity']['id']);
            } else {
                $this->Activity->id = $activity['Activity']['id'];
                $this->Activity->save(
                    array('items' => implode(',', $items))
                );
            }
        }
    }

    public function processEventFirstTimeRsvp($cakeEvent)
    {
        $v = $cakeEvent->subject();
        $this->Activity = ClassRegistry::init('Activity');
        $activity = $this->Activity->getRecentActivity('event_attend', $cakeEvent->data['uid']);

        // insert into activity feed if it's a public event
        if (!empty($activity)) {
            // aggregate activities
            $event_ids = explode(',', $activity['Activity']['items']);
            if (!in_array($cakeEvent->data['event']['Event']['id'], $event_ids))
                $event_ids[] = $cakeEvent->data['event']['Event']['id'];

            $this->Activity->id = $activity['Activity']['id'];
            $this->Activity->save(array('items' => implode(',', $event_ids)
            ));
        } else {
            $this->Activity->save(array('type' => 'user',
                'action' => 'event_attend',
                'user_id' => $cakeEvent->data['uid'],
                'item_type' => 'Event_Event',
                'items' => $cakeEvent->data['event']['Event']['id'],
                'plugin' => 'Event',
                'type' => 'Event_Event'
            ));
        }
    }

    public function processEventSentInvite($event)
    {
        $this->Notification = ClassRegistry::init('Notification');
        $this->Notification->record(array('recipients' => $event->data['friends'],
            'sender_id' => $event->data['cuser']['id'],
            'action' => 'event_invite',
            'url' => '/events/view/' . $event->data['event_id'],
            'params' => $event->data['event']['Event']['title']
        ));
    }

    public function deleteUserContent($event)
    {
        $eventModel = MooCore::getInstance()->getModel("Event.Event");
        $EventRsvpModel = MooCore::getInstance()->getModel("Event.EventRsvp");

        $events = $eventModel->findAllByUserId($event->data['aUser']['User']['id']);
        foreach ($events as $item) {
            $eventModel->deleteEvent($item);
        }

        $EventRsvpModel->deleteAll(array('EventRsvp.user_id' => $event->data['aUser']['User']['id']), true, true);
    }

    public function search($event)
    {
        $e = $event->subject();
        App::import('Model', 'Event.Event');
        $this->Event = new Event();
        $results = $this->Event->getEvents('search', $e->keyword, 1);
        if (count($results) > 5)
            $results = array_slice($results, 0, 5);
        if (isset($e->plugin) && $e->plugin == 'Event') {
            $e->set('events', $results);
            $e->render("Event.Elements/lists/events_list");
        } else {
            $event->result['Event']['header'] = __("Events");
            $event->result['Event']['icon_class'] = "event";
            $event->result['Event']['view'] = "lists/events_list";
            if (!empty($results))
                $event->result['Event']['notEmpty'] = 1;
            $e->set('events', $results);
        }
    }

    public function suggestion($event)
    {
        $e = $event->subject();
        App::import('Model', 'Event.Event');
        $this->Event = new Event();

        $event->result['event']['header'] = __('Events');
        $event->result['event']['icon_class'] = 'event';

        if (isset($event->data['type']) && $event->data['type'] == 'event') {
            $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;
            $events = $this->Event->getEvents('search', $event->data['searchVal'], $page);
            $more_events = $this->Event->getEvents('search', $event->data['searchVal'], $page + 1);
            $more_result = 0;
            if (!empty($more_events))
                $more_result = 1;

            $e->set('events', $events);
            $e->set('result', 1);
            $e->set('more_result', $more_result);
            $more_url = isset($e->params['pass'][1]) ? '/search/suggestion/event/' . $e->params['pass'][1] . '/page:' . ($page + 1) : '';
            $e->set('more_url', $more_url);
            $e->set('element_list_path', "Event.lists/events_list");
        }
        if (isset($event->data['type']) && $event->data['type'] == 'all') {
            $event->result['event'] = null;
            $events = $this->Event->getEvents('search', $event->data['searchVal'], 1);
            if (count($events) > 2) {
                $events = array_slice($events, 0, 2);
            }
            if (!empty($events)) {
                $event->result['event'] = array(__('Event'));
                $helper = MooCore::getInstance()->getHelper("Event_Event");
                foreach ($events as $index => &$detail) {
                    $index++;
                    $event->result['event'][$index]['id'] = $detail['Event']['id'];
                    if (!empty($detail['Event']['photo']))
                        $event->result['event'][$index]['img'] = $helper->getImage($detail,array('prefix'=>'75_square'));
                    $event->result['event'][$index]['title'] = $detail['Event']['title'];
                    $event->result['event'][$index]['find_name'] = 'Find Event';
                    $event->result['event'][$index]['icon_class'] = 'event';
                    $event->result['event'][$index]['view_link'] = 'events/view/';

                    $event->result['event'][$index]['more_info'] = h($detail['Event']['location']) . ' ' . __('%s attending', $detail['Event']['event_rsvp_count']);
                }
            }
        }
    }

    public function hashtags($event)
    {
        $enable = Configure::read('Event.event_hashtag_enabled');
        $e = $event->subject();
        App::import('Model', 'Event.Event');
        $this->Event = new Event();
        App::import('Model', 'Tag');
        $this->Tag = new Tag();
        $events = array();
        $uid = CakeSession::read('uid');
        $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;

        if ($enable) {
            if (isset($event->data['type']) && $event->data['type'] == 'events') {
                $events = $this->Event->getEventHashtags($event->data['item_ids'], RESULTS_LIMIT, $page);
                $events = $this->_filterEvent($events);
            }
            $table_name = $this->Event->table;
            if (isset($event->data['type']) && $event->data['type'] == 'all' && !empty($event->data['item_groups'][$table_name])) {
                $events = $this->Event->getEventHashtags($event->data['item_groups'][$table_name], 5);
                $events = $this->_filterEvent($events);
            }
        }

        // get tagged item
        $tag = h(urldecode($event->data['search_keyword']));
        $tags = $this->Tag->find('all', array('conditions' => array(
            'Tag.type' => 'Event_Event',
            'Tag.tag' => $tag
        )));
        $event_ids = Hash::combine($tags, '{n}.Tag.id', '{n}.Tag.target_id');

        $friendModel = MooCore::getInstance()->getModel('Friend');

        $items = $this->Event->find('all', array('conditions' => array(
            'Event.id' => $event_ids
        ),
            'limit' => RESULTS_LIMIT,
            'page' => $page
        ));

        $viewer = MooCore::getInstance()->getViewer();

        foreach ($items as $key => $item) {
            $owner_id = $item[key($item)]['user_id'];
            $privacy = isset($item[key($item)]['privacy']) ? $item[key($item)]['privacy'] : 1;
            if (empty($viewer)) { // guest can view only public item
                if ($privacy != PRIVACY_EVERYONE) {
                    unset($items[$key]);
                }
            } else { // viewer
                $aFriendsList = array();
                $aFriendsList = $friendModel->getFriendsList($owner_id);
                if ($privacy == PRIVACY_ME) { // privacy = only_me => only owner and admin can view items
                    if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id) {
                        unset($items[$key]);
                    }
                } else if ($privacy == PRIVACY_FRIENDS) { // privacy = friends => only owner and friendlist of owner can view items
                    if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id && !in_array($viewer['User']['id'], array_keys($aFriendsList))) {
                        unset($items[$key]);
                    }
                } else {

                }
            }
        }
        $events = array_merge($events, $items);
        //only display 5 items on All Search Result page
        if (isset($event->data['type']) && $event->data['type'] == 'all') {
            $events = array_slice($events, 0, 5);
        }
        $events = array_map("unserialize", array_unique(array_map("serialize", $events)));
        if (!empty($events)) {
            $event->result['events']['header'] = __('Events');
            $event->result['events']['icon_class'] = 'event';
            $event->result['events']['view'] = "Event.lists/events_list";

            if (isset($event->data['type']) && $event->data['type'] == 'events') {
                $e->set('result', 1);
                $e->set('more_url', '/search/hashtags/' . $e->params['pass'][0] . '/events/page:' . ($page + 1));
                $e->set('element_list_path', "Event.lists/events_list");
            }
            $e->set('events', $events);

        }
    }

    public function hashtags_filter($event)
    {

        $e = $event->subject();
        App::import('Model', 'Event.Event');
        $this->Event = new Event();

        if (isset($event->data['type']) && $event->data['type'] == 'events') {
            $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;
            $events = $this->Event->getEventHashtags($event->data['item_ids'], RESULTS_LIMIT, $page);
            $e->set('events', $events);
            $e->set('result', 1);
            $e->set('more_url', '/search/hashtags/' . $e->params['pass'][0] . '/events/page:' . ($page + 1));
            $e->set('element_list_path', "Event.lists/events_list");
        }
        $table_name = $this->Event->table;
        if (isset($event->data['type']) && $event->data['type'] == 'all' && !empty($event->data['item_groups'][$table_name])) {
            $event->result['events'] = null;

            $events = $this->Event->getEventHashtags($event->data['item_groups'][$table_name], 5);

            if (!empty($events)) {
                $event->result['events']['header'] = __('Events');
                $event->result['events']['icon_class'] = 'event';
                $event->result['events']['view'] = "Event.lists/events_list";
                $e->set('events', $events);

            }
        }
    }

    private function _filterEvent($events)
    {
        if (!empty($events)) {
            $eventRsvpModel = MooCore::getInstance()->getModel('Event.EventRsvp');
            $viewer = MooCore::getInstance()->getViewer();
            foreach ($events as $key => &$event) {
                $owner_id = $event[key($event)]['user_id'];
                $privacy = isset($event[key($event)]['type']) ? $event[key($event)]['type'] : 1;

                if (empty($viewer)) { // guest can view only public item
                    if ($privacy != PRIVACY_EVERYONE) {
                        unset($events[$key]);
                    }
                } else { // viewer
                    $awaiting = $eventRsvpModel->getRsvp($event[key($event)]['id'], RSVP_AWAITING);
                    $attending = $eventRsvpModel->getRsvp($event[key($event)]['id'], RSVP_ATTENDING);
                    $not_attending = $eventRsvpModel->getRsvp($event[key($event)]['id'], RSVP_NOT_ATTENDING);
                    $maybe = $eventRsvpModel->getRsvp($event[key($event)]['id'], RSVP_MAYBE);

                    $awaiting = Hash::extract($awaiting, '{n}.User.id');
                    $attending = Hash::extract($attending, '{n}.User.id');
                    $not_attending = Hash::extract($not_attending, '{n}.User.id');
                    $maybe = Hash::extract($maybe, '{n}.User.id');

                    $idList = array_merge($awaiting, $attending, $not_attending, $maybe);
                    if ($privacy == PRIVACY_FRIENDS) { // privacy = private => only owner and admin can view items
                        if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id && !in_array($viewer['User']['id'], $idList)) {
                            unset($events[$key]);
                        }
                    } else {

                    }
                }
            }
        }
        return $events;
    }

    public function hashtagEnable($event)
    {
        $enable = Configure::read('Event.event_hashtag_enabled');
        $event->result['events']['enable'] = $enable;
    }

    public function deactivate($event)
    {
        $eventModel = MooCore::getInstance()->getModel('Event.Event');
        $eventCategory = $eventModel->find('all', array(
                'conditions' => array('Event.user_id' => $event->data['uid']),
                'group' => array('Event.category_id'),
                'fields' => array('category_id', '(SELECT count(*) FROM ' . $eventModel->tablePrefix . 'events WHERE category_id=Event.category_id AND user_id = ' . $event->data['uid'] . ') as count')
            )
        );
        $eventCategory = Hash::combine($eventCategory, '{n}.Event.category_id', '{n}.{n}.count');
        $event->result['Event'] = $eventCategory;
    }
}
