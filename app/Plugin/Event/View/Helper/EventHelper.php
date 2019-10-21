<?php
App::uses('AppHelper', 'View/Helper');

class EventHelper extends AppHelper
{
    public $helpers = array('Storage.Storage');

    public function isMember($event, $uid)
    {
        $model = $model = MooCore::getInstance()->getModel('Event_Event_Rsvp');
        $my_rsvp = $model->getMyRsvp($uid, $event['Event']['id']);

        return $my_rsvp;
    }

    public function getEnable()
    {
        return Configure::read('Event.event_enabled');
    }

    public function checkPostStatus($event, $uid)
    {
        if (!$uid)
            return false;

        $cuser = MooCore::getInstance()->getViewer();
        if ($cuser['Role']['is_admin'])
        	return true;
        
        $my_rsvp = $this->isMember($event, $uid);

        if ($my_rsvp || $event['Event']['type'] != PRIVACY_PRIVATE)
            return true;

        return false;
    }

    public function checkSeeActivity($event, $uid)
    {
        $is_member = $this->isMember($event, $uid);
        if ($event['Event']['type'] == PRIVACY_PRIVATE) {
            $cuser = MooCore::getInstance()->getViewer();

            if (!$cuser['Role']['is_admin'] && !$is_member)
                return false;
        }
        return true;
    }

    public function isPublicFeedIcon($event)
    {
        if ($event['Event']['type'] == PRIVACY_PUBLIC)
            return true;

        return false;
    }

    public function getAdminList($event)
    {
        return array($event['Event']['user_id']);
    }

    public function getItemSitemMap($name, $limit, $offset)
    {
        if (!MooCore::getInstance()->checkPermission(null, 'event_view'))
            return null;

        $eventModel = MooCore::getInstance()->getModel("Event.Event");
        $events = $eventModel->find('all', array(
            'conditions' => array('Event.type' => PRIVACY_PUBLIC),
            'limit' => $limit,
            'offset' => $offset
        ));

        $urls = array();
        foreach ($events as $event) {
            $urls[] = FULL_BASE_URL . $event['Event']['moo_href'];
        }

        return $urls;
    }

    public function getImage($item, $options)
    {
        $prefix = (isset($options['prefix'])) ? $options['prefix'] . '_' : '';

        return $this->Storage->getUrl($item['Event']['id'], $prefix, $item['Event']['photo'], "events");
    }

}
