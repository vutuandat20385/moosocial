<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');


class StorageTopicListener implements CakeEventListener
{
    public function implementedEvents()
    {
        return array(
            'StorageHelper.topics.getUrl.local' => 'storage_geturl_local',
            'StorageHelper.topics.getUrl.amazon' => 'storage_geturl_amazon',
            'StorageAmazon.topics.getFilePath' => 'storage_amazon_get_file_path',
            'StorageTaskAwsCronTransfer.execute' => 'storage_task_transfer',
            'StorageAmazon.photos.putObject.success.Topic' => 'storage_amazon_photo_put_success_callback',
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
            $url = FULL_BASE_LOCAL_URL . $request->webroot . 'uploads/topics/thumbnail/' . $oid . '/' . $prefix . $thumb;
        } else {
            //$url = FULL_BASE_LOCAL_URL . $v->assetUrl('Topic.noimage/topic.png', array('prefix' => rtrim($prefix, "_"), 'pathPrefix' => Configure::read('App.imageBaseUrl')));
            $url = $v->getImage("topic/img/noimage/topic.png");
        }
        $e->result['url'] = $url;
    }

    public function storage_geturl_amazon($e)
    {
        $v = $e->subject();
        $e->result['url'] = $v->getAwsURL($e->data['oid'], "topics", $e->data['prefix'], $e->data['thumb']);
    }

    public function storage_amazon_get_file_path($e)
    {
        $objectId = $e->data['oid'];
        $name = $e->data['name'];
        $thumb= $e->data['thumb'];
        $path = false;
        if (!empty($thumb)) {
            $path = WWW_ROOT . "uploads" . DS . "topics" . DS . "thumbnail" . DS . $objectId . DS . $name . $thumb;
        }

        $e->result['path'] =   $path ;
    }
    public function storage_task_transfer($e)
    {
        $v = $e->subject();
        $topicModel = MooCore::getInstance()->getModel('Topic.Topic');
        $topics = $topicModel->find('all', array(
                'conditions' => array("Topic.id > " => $v->getMaxTransferredItemId("topics")),
                'limit' => 10,
                'fields'=>array('Topic.id','Topic.thumbnail'),
                'order' => array('Topic.id'),
            )
        );

        if($topics){
            $photoSizes = $v->photoSizes();
            foreach($topics as $topic){
                if (!empty($topic["Topic"]["thumbnail"])) {
                    foreach ($photoSizes as $size){
                        $v->transferObject($topic["Topic"]['id'],"topics",$size.'_',$topic["Topic"]["thumbnail"]);
                    }
                    $v->transferObject($topic["Topic"]['id'],"topics",'',$topic["Topic"]["thumbnail"]);
                }
            }
        }
    }
    public function storage_amazon_photo_put_success_callback($e){
        $photo = $e->data['photo'];
        $path= $e->data['path'];
        $url= $e->data['url'];
        if (Configure::read('Storage.storage_cloudfront_enable') == "1"){
            $url = rtrim(Configure::read('Storage.storage_cloudfront_cdn_mapping'),"/")."/".$e->data['key'];
        }
        $topicModel = MooCore::getInstance()->getModel('Topic.Topic');
        $topicModel->clear();
        $topic = $topicModel->find("first",array(
            'conditions' => array("Topic.id"=>$photo['Photo']['target_id']),
        ));
        if($topic){
            $findMe = str_replace(WWW_ROOT,"",$path);
            $isReplaced = false;
            $regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
            if(preg_match_all("/$regexp/siU", $topic['Topic']['body'], $matches)) {
                foreach ($matches[2] as $match){
                    if(strpos($match, $findMe) !== false){
                        $isReplaced = true;
                        $topic['Topic']['body'] = str_replace($match,$url,$topic['Topic']['body']);
                    }
                }
            }
            $regexp = "<img\s[^>]*src=(\"??)([^\" >]*?)\\1[^>]*>";
            if(preg_match_all("/$regexp/siU", $topic['Topic']['body'], $matches)) {
                foreach ($matches[2] as $match){
                    if(strpos($match, $findMe) !== false){
                        $isReplaced = true;
                        $topic['Topic']['body'] = str_replace($match,$url,$topic['Topic']['body']);
                    }
                }
            }
            if($isReplaced){
                $topicModel->clear();
                $topicModel->save($topic);
            }
        }

    }
}
