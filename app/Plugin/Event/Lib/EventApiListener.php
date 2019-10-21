<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class EventApiListener implements CakeEventListener {

    public function implementedEvents() {
        return array(
            'ApiHelper.renderAFeed.event_create' => 'exportEventCreate',
            'ApiHelper.renderAFeed.event_attend' => 'exportEventAttend',
            'ApiHelper.renderAFeed.event_item_detail_share' => 'exportEventItemDetailShare',
        );
    }

    function getEvent($id, $v) {
        $event = MooCore::getInstance()->getItemByType('Event_Event', $id); //echo '<pre>';print_r($event);die;
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        $imageArray = array();
        foreach ($photoSizes as $size) {
            $imageArray[$size] = $v->Event->getImage($event, array('prefix' => $size));
        }
        return array($event['Event']['id'], //id
            FULL_BASE_URL . str_replace('?','',mb_convert_encoding($event['Event']['moo_href'], 'UTF-8', 'UTF-8')), // url
            $event['Event']['title'],
            $imageArray,
            $v->Time->event_format($event['Event']['from']) . $event['Event']['from_time'] . " - " . $v->Time->event_format($event['Event']['to']) . $event['Event']['to_time'],
            h($event['Event']['location']),
            h($event['Event']['address']),
            $event['User']
        );
    }

    function exportEventCreate($e) {

        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];

        list($eId, $eUrl, $eTitle, $eImages, $eTimes, $eLocation, $eAddress) = $this->getEvent($data['Activity']['item_id'], $e->subject());
        $e->result['result'] = array(
            'type' => 'create',
            'title' => $data['User']['name'] . ' ' . __('created a new event'),
            'titleHtml' => $actorHtml . ' ' . __('created a new event'),
            'objects' => array(
                'type' => 'Event_Event',
                'id' => $eId,
                'url' => $eUrl,
                'title' => $eTitle,
                'images' => $eImages,
                'time' => $eTimes,
                'location' => $eLocation,
                'address' => $eAddress,
            ),
            'target' => '',
        );
    }

    function exportEventAttend($e) {

        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];
        $v = $e->subject();

        $ids = explode(',', $data['Activity']['items']);
        $eventModel = MooCore::getInstance()->getModel('Event_Event');
        $eventModel->cacheQueries = true;
        $events = $eventModel->find('all', array('conditions' => array('Event.id' => $ids),
        ));
        
        $attending1 = '%s';
        $attending2 = '%s and %s';
        $attending3 = '%s and %s';
        $attending = '';
        $attendingHtml = '';
        switch (count($events)):
            case 1:
                $attending = sprintf($attending1, $events[0]['Event']['title']);
                $attendingHtml = sprintf($attending1, $v->Html->link($events[0]['Event']['title'], FULL_BASE_URL . str_replace('?','',mb_convert_encoding($events[0]['Event']['moo_href'], 'UTF-8', 'UTF-8')) ));
                break;
            case 2:
                $attending = sprintf($attending2, $events[0]['Event']['title'], $events[1]['Event']['title']);
                $attendingHtml = sprintf($attending2, $v->Html->link($events[0]['Event']['title'], FULL_BASE_URL . str_replace('?','',mb_convert_encoding($events[0]['Event']['moo_href'], 'UTF-8', 'UTF-8'))), $v->Html->link(h($events[1]['Event']['title']), FULL_BASE_URL . str_replace('?','',mb_convert_encoding($events[1]['Event']['moo_href'], 'UTF-8', 'UTF-8'))));
                break;
            case 3:
            default :
                $attending = sprintf($attending2, $events[0]['Event']['title'], abs(count($events) - 1) . ' ' . __('others'));
                $attendingHtml = sprintf($attending2, $v->Html->link($events[0]['Event']['title'], FULL_BASE_URL . str_replace('?','',mb_convert_encoding($events[0]['Event']['moo_href'], 'UTF-8', 'UTF-8'))), abs(count($events) - 1) . ' ' . __('others'));
                break;
        endswitch;

        $title = $data['User']['name'] . ' ' . __('is attending') . ' ' . $attending;
        $titleHtml = $actorHtml . ' ' . __('is attending') . ' ' . $attendingHtml;
        $photoSizes = explode('|', Configure::read('core.photo_image_sizes'));

        foreach ($events as $event):
            $imageArray = array();
            foreach ($photoSizes as $size) {
                $imageArray[$size] = $v->Event->getImage($event, array('prefix' => $size));
            }
            $eventArray[] = array(
                'id' => $event['Event']['id'],
                'url' => FULL_BASE_URL . str_replace('?','',mb_convert_encoding($event['Event']['moo_href'], 'UTF-8', 'UTF-8')) ,
                'name' => $event['Event']['moo_title'],
                'type' => h($event['Event']['moo_plugin']),
                'userCount' => h($event['Event']['event_rsvp_count']),
                'images' => $imageArray,
                'time' =>  $v->Time->event_format($event['Event']['from']) . $event['Event']['from_time'] . " - " . $v->Time->event_format($event['Event']['to']) . $event['Event']['to_time'],
                'location' => h($event['Event']['location']),
            );
        endforeach;



        $e->result['result'] = array(
            'type' => 'attend',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Event_Event',
                'eventCount' => count($eventArray),
                'eventArray' => $eventArray,
            ),
            'target' => '',
        );
    }

    function exportEventItemDetailShare($e) {

        $data = $e->data['data'];
        $actorHtml = $e->data['actorHtml'];

        list($eId, $eUrl, $eTitle, $eImages, $eTimes, $eLocation, $eAddress, $eUser ) = $this->getEvent($data['Activity']['parent_id'], $e->subject());
        if (isset($data['Activity']['parent_id']) && $data['Activity']['parent_id']):

            $title = $data['User']['name'] . ' ' . __("shared %s's event", $eUser['name']);
            $titleHtml = $actorHtml . ' ' . __("shared %s's event", $e->subject()->Html->link($eUser['name'], FULL_BASE_URL . $eUser['moo_href']));
            $target = array(
                'url' => FULL_BASE_URL . $eUser['moo_href'],
                'id' => $eUser['id'],
                'name' => $eUser['name'],
                'type' => 'User',
            );
        endif;

        list($title_tmp, $target) = $e->subject()->getActivityTarget($data, $actorHtml);
        if (!empty($title_tmp)) {
            $title .= $title_tmp['name'];
            $titleHtml .= $title_tmp['titleHtml'];
        }

        $e->result['result'] = array(
            'type' => 'share',
            'title' => $title,
            'titleHtml' => $titleHtml,
            'objects' => array(
                'type' => 'Event_Event',
                'id' => $eId,
                'url' => $eUrl,
                'title' => $eTitle,
                'images' => $eImages,
                'time' => $eTimes,
                'location' => $eLocation,
                'address' => $eAddress,
            ),
            'target' => $target,
        );
    }

}
