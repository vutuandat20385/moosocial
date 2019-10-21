<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');


class StorageGroupListener implements CakeEventListener
{
    public function implementedEvents()
    {
        return array(
            'StorageHelper.groups.getUrl.local' => 'storage_geturl_local',
            'StorageHelper.groups.getUrl.amazon' => 'storage_geturl_amazon',
            'StorageAmazon.groups.getFilePath' => 'storage_amazon_get_file_path',
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
            $url = FULL_BASE_LOCAL_URL . $request->webroot . 'uploads/groups/photo/' . $oid . '/' . $prefix . $thumb;
        } else {
            //$url = FULL_BASE_LOCAL_URL . $v->assetUrl('Group.noimage/group.png', array('prefix' => rtrim($prefix, "_"), 'pathPrefix' => Configure::read('App.imageBaseUrl')));
            $url = $v->getImage("group/img/noimage/group.png");
        }
        $e->result['url'] = $url;
    }

    public function storage_geturl_amazon($e)
    {
        $v = $e->subject();
        $e->result['url'] = $v->getAwsURL($e->data['oid'], "groups", $e->data['prefix'], $e->data['thumb']);
    }


    public function storage_amazon_get_file_path($e)
    {
        $objectId = $e->data['oid'];
        $name = $e->data['name'];
        $thumb = $e->data['thumb'];
        $path = false;
        if (!empty($thumb)) {
            $path = WWW_ROOT . "uploads" . DS . "groups" . DS . "photo" . DS . $objectId . DS . $name . $thumb;
        }
        $e->result['path'] = $path;
    }

    public function storage_task_transfer($e)
    {
        $v = $e->subject();
        $groupModel = MooCore::getInstance()->getModel('Group.Group');
        $groups = $groupModel->find('all', array(
                'conditions' => array("Group.id > " => $v->getMaxTransferredItemId("groups")),
                'limit' => 10,
                'fields' => array('Group.id', 'Group.photo'),
                'order' => array('Group.id'),

            )
        );

        if ($groups) {
            $photoSizes = $v->photoSizes();
            foreach ($groups as $group) {
                if (!empty($group["Group"]["photo"])) {
                    foreach ($photoSizes as $size) {
                        $v->transferObject($group["Group"]['id'], "groups", $size . '_', $group["Group"]["photo"]);
                    }
                    $v->transferObject($group["Group"]['id'], "groups", '', $group["Group"]["photo"]);
                }
            }
        }
    }
}
