<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');
//App::uses('StorageAmazon', 'Storage.Lib');

class StorageVideoListener implements CakeEventListener
{
    public function implementedEvents()
    {
        return array(
            'StorageHelper.videos.getUrl.local' => 'storage_geturl_local',
            'StorageHelper.videos.getUrl.amazon' => 'storage_geturl_amazon',
            'StorageAmazon.videos.getFilePath' => 'storage_amazon_get_file_path',
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
            $url = FULL_BASE_LOCAL_URL . $request->webroot . 'uploads/videos/thumb/' . $oid . '/' . $prefix . $thumb;
        } else {
            $url = FULL_BASE_LOCAL_URL . $v->assetUrl('Video.noimage/video.png', array('prefix' => rtrim($prefix, "_"), 'pathPrefix' => Configure::read('App.imageBaseUrl')));
        }
        $e->result['url'] = $url;
    }

    public function storage_geturl_amazon($e)
    {
        $v = $e->subject();
        $e->result['url'] = $v->getAwsURL($e->data['oid'], "videos", $e->data['prefix'], $e->data['thumb']);
    }

    public function storage_amazon_get_file_path($e)
    {
        $objectId = $e->data['oid'];
        $name = $e->data['name'];
        $thumb= $e->data['thumb'];
        $path = false;
        if (!empty($thumb)) {
            $path = WWW_ROOT . "uploads" . DS . "videos" . DS . "thumb" . DS . $objectId . DS . $name . $thumb;
        }
        $e->result['path'] =   $path ;
    }
    public function storage_task_transfer($e)
    {
        $v = $e->subject();
        $videoModel = MooCore::getInstance()->getModel('Video.Video');
        $videos = $videoModel->find('all', array(
                'conditions' => array("Video.id > " => $v->getMaxTransferredItemId("videos")),
                'limit' => 10,
                'fields'=>array('Video.id','Video.thumb'),
                'order' => array('Video.id'),
            )
        );

        if($videos){
            $photoSizes = $v->photoSizes();
            foreach($videos as $video){
                if (!empty($video["Video"]["thumb"])) {
                    foreach ($photoSizes as $size){
                        $v->transferObject($video["Video"]['id'],"videos",$size.'_',$video["Video"]["thumb"]);
                    }
                    $v->transferObject($video["Video"]['id'],"videos",'',$video["Video"]["thumb"]);
                }
            }
        }
    }
}
