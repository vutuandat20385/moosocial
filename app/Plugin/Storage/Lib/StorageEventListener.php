<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');


class StorageEventListener implements CakeEventListener
{
    public function implementedEvents()
    {
        return array(
            'StorageHelper.events.getUrl.local' => 'storage_geturl_local',
            'StorageHelper.events.getUrl.amazon' => 'storage_geturl_amazon',
            'StorageAmazon.events.getFilePath' => 'storage_amazon_get_file_path',
            'StorageTaskAwsCronTransfer.execute' => 'storage_task_transfer',
        );
    }

    public function storage_geturl_local($e)
    {
        $v = $e->subject();
        $request = Router::getRequest();
        $oid = $e->data['oid'];
        $thumb = $e->data['thumb'];
        $prefix = $e->data['prefix'];
        if ($e->data['thumb']) {
            $url = FULL_BASE_LOCAL_URL . $request->webroot . 'uploads/events/photo/' . $oid . '/' . $prefix . $thumb;
        } else {
            //$url = FULL_BASE_LOCAL_URL . $v->assetUrl('Event.noimage/event.png', array('prefix' => rtrim($prefix, "_"), 'pathPrefix' => Configure::read('App.imageBaseUrl')));
            $url = $v->getImage("event/img/noimage/event.png");
        }
        $e->result['url'] = $url;
    }

    public function storage_geturl_amazon($e)
    {
        $v = $e->subject();
        $e->result['url'] = $v->getAwsURL($e->data['oid'], "events", $e->data['prefix'], $e->data['thumb']);
    }


    public function storage_amazon_get_file_path($e)
    {
        $objectId = $e->data['oid'];
        $name = $e->data['name'];
        $thumb = $e->data['thumb'];
        $path = false;
        if (!empty($thumb)) {
            $path = WWW_ROOT . "uploads" . DS . "events" . DS . "photo" . DS . $objectId . DS . $name . $thumb;
        }
        $e->result['path'] = $path;
    }

    public function storage_task_transfer($e)
    {
        $v = $e->subject();
        $eventModel = MooCore::getInstance()->getModel('Event.Event');
        $events = $eventModel->find('all', array(
                'conditions' => array("Event.id > " => $v->getMaxTransferredItemId("events")),
                'limit' => 10,
                'fields' => array('Event.id', 'Event.photo'),
                'order' => array('Event.id'),
            )
        );

        if ($events) {
            $photoSizes = $v->photoSizes();
            foreach ($events as $event) {
                if (!empty($event["Event"]["photo"])) {
                    foreach ($photoSizes as $size) {
                        $v->transferObject($event["Event"]['id'], "events", $size . '_', $event["Event"]["photo"]);
                    }
                    $v->transferObject($event["Event"]['id'], "events", '', $event["Event"]["photo"]);
                }
            }
        }
    }

}
