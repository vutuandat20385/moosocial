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

class StoragePhotoListener implements CakeEventListener
{
    protected $_eventManager = null;
    public function getEventManager()
    {
        if (empty($this->_eventManager)) {
            $this->_eventManager = new CakeEventManager();
            $this->_eventManager->attach($this);
        }
        return $this->_eventManager;
    }
    public function implementedEvents()
    {
        return array(
            'StorageHelper.photos.getUrl.local' => 'storage_geturl_local',
            'StorageHelper.photos.getUrl.amazon' => 'storage_geturl_amazon',
            'StorageAmazon.photos.getFilePath' => 'storage_amazon_get_file_path',
            'StorageTaskAwsCronTransfer.execute' => 'storage_task_transfer',
            'StorageAmazon.photos.putObject.success' => 'storage_amazon_putObject_success',        );
    }
    public function storage_geturl_local($e)
    {
        $v = $e->subject();
        $request = Router::getRequest();
        $oid = $e->data['oid'];
        $thumb = $e->data['thumb'];
        $prefix = $e->data['prefix'];
        $extra = $e->data['extra'];
        if ($e->data['thumb']) {
            if ($extra['year_folder']) {  // hacking for MOOSOCIAL-2771
                $year = date('Y', strtotime($extra['created']));
                $month = date('m', strtotime($extra['created']));
                $day = date('d', strtotime($extra['created']));
                $url = FULL_BASE_LOCAL_URL . $request->webroot . "uploads/photos/thumbnail/$year/$month/$day/" . $oid . '/' . $prefix . $thumb . '?' . time();
            } else {
                $url = FULL_BASE_LOCAL_URL . $request->webroot . 'uploads/photos/thumbnail/' . $oid . '/' . $prefix . $thumb . '?' . time();
            }
        } else {
            $url = FULL_BASE_LOCAL_URL . $v->assetUrl('Photo.noimage/album.png', array('prefix' => rtrim($prefix, "_"), 'pathPrefix' => Configure::read('App.imageBaseUrl')));
        }
        $e->result['url'] = $url;
    }

    public function storage_geturl_amazon($e)
    {
        $v = $e->subject();
        $e->result['url'] = $v->getAwsURL($e->data['oid'], "photos", $e->data['prefix'], $e->data['thumb']);
    }



    public function storage_amazon_get_file_path($e)
    {
        $path = false;
        $objectId = $e->data['oid'];
        $prefix = $e->data['name'];
        $thumb= $e->data['thumb'];
        $v = $e->subject();
        $record = $v->getPhotoModel()->findById($objectId);
        if ($record) {
            $photoData = $record['Photo'];
            if (!empty($photoData['thumbnail'])) {
                if ($photoData['year_folder']) { // hacking for MOOSOCIAL-2771
                    $year = date('Y', strtotime($photoData['created']));
                    $month = date('m', strtotime($photoData['created']));
                    $day = date('d', strtotime($photoData['created']));
                    $path = WWW_ROOT . "uploads" . DS . "photos" . DS . "thumbnail" . DS . $year . DS . $month . DS . $day . DS . $photoData['id'] . DS . $prefix . $photoData['thumbnail'];
                } else {
                    $path = WWW_ROOT . "uploads" . DS . "photos" . DS . "thumbnail" . DS . $photoData['id'] . DS . $prefix . $photoData['thumbnail'];
                }
            }
        }
        $e->result['path'] =   $path ;

    }
    public function storage_task_transfer($e)
    {
        $v = $e->subject();
        $photoModel = MooCore::getInstance()->getModel('Photo.Photo');
        $photos = $photoModel->find('all', array(
                'conditions' => array("Photo.id > " => $v->getMaxTransferredItemId("photos")),
                'limit' => 10,
                'fields'=>array('Photo.id','Photo.thumbnail'),
                'order' => array('Photo.id'),
            )
        );

        if($photos){
            $photoSizes = $v->photoSizes();
            foreach($photos as $photo){
                if (!empty($photo["Photo"]["thumbnail"])) {
                    foreach ($photoSizes as $size){
                        $v->transferObject($photo["Photo"]['id'],"photos",$size.'_',$photo["Photo"]["thumbnail"]);
                    }
                    $v->transferObject($photo["Photo"]['id'],"photos",'',$photo["Photo"]["thumbnail"]);
                }
            }
        }
    }
    public function storage_amazon_putObject_success($e){
        if (Configure::read('Storage.storage_current_type') == 'amazon' ) {
            CakeLog::write('storage', 'storage_amazon_putObject_success');
            $objectId = $e->data['oid'];
            $photoModel = MooCore::getInstance()->getModel('Photo.Photo');
            $photo = $photoModel->find("first",array(
                'conditions' => array("Photo.id"=>$objectId),
            ));
            if($photo){
                $event = new CakeEvent("StorageAmazon.photos.putObject.success.".$photo["Photo"]["type"], $this, array("photo"=>$photo,"key"=>$e->data['key'],"name"=>$e->data['name'],"url"=>$e->data['url'],"path"=>$e->data['path']));
                $this->getEventManager()->dispatch($event);
            }
        }

    }
}
