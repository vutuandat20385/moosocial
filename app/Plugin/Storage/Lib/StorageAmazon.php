<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('Vendor', 'aws', array('file' => 'aws/aws-autoloader.php'));
use Aws\S3\S3Client;

App::uses('MooCore', 'Lib');
App::uses('StorageAwsTask', 'Storage.Model');
App::uses('CakeEventListener', 'Event');

class StorageAmazon implements CakeEventListener
{
    private $transferedObjects = array();
    private $awsModel = false;
    private $taskModel = false;
    private $photoModel = false;
    private $s3 = false;
    public $s3Config = array();
    public $photoSizes = false;
    protected $_eventManager = null;

    public function implementedEvents()
    {
        return array();
    }

    public function getEventManager()
    {
        if (empty($this->_eventManager)) {
            $this->_eventManager = new CakeEventManager();
            $this->_eventManager->attach($this);
        }
        return $this->_eventManager;
    }

    public function __construct()
    {
        $this->s3Config['bucket'] = Configure::read("Storage.storage_amazon_bucket_name");
        $this->s3Config['key'] = Configure::read("Storage.storage_amazon_access_key");
        $this->s3Config['secret'] = Configure::read("Storage.storage_amazon_secret_key");
        $this->s3Config['region'] = Configure::read("Storage.storage_amazon_s3_region");
        $params = array(
            'version' => 'latest',
            'region' => $this->s3Config['region'],
            'credentials' => array(
                'key' => $this->s3Config['key'],
                'secret' => $this->s3Config['secret'],
            ),
        );
        if (Configure::read("Storage.storage_amazon_server_file_vi_https") != "1") {
            $params["scheme"] = "http";
        }
        $this->s3 = new S3Client($params);
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new StorageAmazon();
        }

        return $instance;
    }

    public function getAwsModel()
    {
        if (!$this->awsModel) {
            $this->awsModel = MooCore::getInstance()->getModel("Storage.StorageAwsObjectMap");
        }

        return $this->awsModel;
    }

    public function getTaskModel()
    {
        if (!$this->taskModel) {
            $this->taskModel = MooCore::getInstance()->getModel("Storage.StorageAwsTask");
        }
        return $this->taskModel;
    }

    public function getPhotoModel()
    {
        if (!$this->photoModel) {
            $this->photoModel = MooCore::getInstance()->getModel("Photo");
        }
        return $this->photoModel;
    }

    /**
     * Registering object to the Amazon's S3 PUT object queue
     * @param int $oid
     * @param string $type
     * @param string $name
     * @param string $thumb
     * @param array $extra
     * @return bool false means that it has been registered
     */
    public function addMissingObject($oid, $type, $name, $thumb, $extra = false)
    {
        $result = $this->getTaskModel()->find("first", array(
            'conditions' => array(
                'StorageAwsTask.oid' => $oid,
                'StorageAwsTask.name' => $name,
                'StorageAwsTask.type' => $type,
                'StorageAwsTask.action' => "PUT",
            )));

        if (!$result) {
            $path = '';
            $event = new CakeEvent("StorageAmazon." . $type . ".getFilePath", $this, array('oid' => $oid, 'type' => $type, 'name' => $name, 'thumb' => $thumb, 'extra' => $extra));
            $this->getEventManager()->dispatch($event);
            if (isset($event->result['path'])) {
                $path = $event->result['path'];
            }

            if (file_exists($path)) {
                $this->getTaskModel()->clear();

                $key = isset($extra['key'])?$extra['key']:str_replace(WWW_ROOT . "uploads" . DS, "", $path);
                $record = array(
                    'action' => "PUT",
                    'oid' => $oid,
                    'type' => $type,
                    'name' => $name,
                    'path' => $path,
                    'key' => $key,

                );
                $this->getTaskModel()->save($record);
            } else {
                if (!empty($thumb)) {
                    CakeLog::write('storage', 'Missing path when calling event ' . "StorageAmazon." . $type . ".getFilePath");
                }
                    // We will get issue in case system has more than 0,5 mil records without images , it will loop to select image with the same value no image . That not good.
                    // So we will hacking the cache for getAwsURL in StorageAwsObjectMap.php
                $event = new CakeEvent("StorageAmazon.addMissingObject.fileNotExist", $this, array('oid' => $oid, 'type' => $type, 'name' => $name, 'thumb' => $thumb, 'extra' => $extra));
                $this->getEventManager()->dispatch($event);

            }
            return true;
        }
        return false;
    }

    /**
     * Registering object to the Amazon's S3 DELETE object queue
     * @param int $oid
     * @param string $type
     * @param string $name
     * @param string $key
     * @param string $bucket
     * @return bool false means that it has been registered
     */
    public function addDeletedObject($oid, $type, $name, $key, $bucket)
    {
        $result = $this->getTaskModel()->find("first", array(
            'conditions' => array(
                'StorageAwsTask.oid' => $oid,
                'StorageAwsTask.name' => $name,
                'StorageAwsTask.type' => $type,
                'StorageAwsTask.action' => "DELETE",
            )));
        if (!$result) {
            $this->getTaskModel()->clear();
            $record = array(
                'action' => "DELETE",
                'oid' => $oid,
                'type' => $type,
                'name' => $name,
                'key' => $key,
                'bucket' => $bucket
            );
            $this->getTaskModel()->save($record);
            return true;
        }
        return false;
    }

    /**
     * The the URL of object is on Amazon's S3
     * @param $oid
     * @param $type
     * @param $name
     * @param $thumb
     * @param bool $extra
     * @return mixed  false means that the url does not exist , string means the url is exist
     */
    public function getAwsURL($oid, $type, $name, $thumb, $extra = false)
    {
        $url = $this->getAwsModel()->getAwsURL($oid, $type, $name);
        if ($url && !empty($url)) {
            return $url;
        } else {
            $this->addMissingObject($oid, $type, $name, $thumb, $extra);
            return false;
        }
    }

    /**
     * Putting an object to Amazon's S3
     * @param string $path the path of file need to be put to Amazon's S3
     * @param int $oid
     * @param string $name
     * @param string $type
     * @return mixed array|bool false means that the file does not exists
     */
    public function putObject($path, $oid, $name, $type,$key)
    {
        if (file_exists($path)) {
            $cachingName = $oid . "_" . $type . "_" . $name . "_putting";
            $isCached = Cache::read($cachingName, 'storage_short');
            if (!$isCached) {
                Cache::write($cachingName, 1, 'storage_short');
                //$key = str_replace(WWW_ROOT . "uploads" . DS, "", $path);
                try {
                    $result = $this->s3->putObject(array(
                        'ALC' => 'public-read',
                        'Bucket' => $this->s3Config['bucket'],
                        'SourceFile' => $path,
                        'Key' => $key
                    ));
                }catch (Exception $e) {
                    return false;
                }

                Cache::delete($cachingName, 'storage_short');
                if (filter_var($result['ObjectURL'], FILTER_VALIDATE_URL) == TRUE) {
                    //$result['ObjectURL'] = $this->s3->getObjectUrl($this->s3Config['bucket'], $key);

                    if (Configure::read('Storage.storage_amazon_use_cname') == '1') {
                        $domain = str_replace("http://", "", Configure::read('Storage.storage_amazon_url_cname'));
                        $domain = str_replace("https://", "", $domain);
                        $domain = rtrim($domain, "/");
                        $domain = "//" . $domain . "/" . $key;
                        //CakeLog::write('storage', $domain);
                        //if (filter_var($domain, FILTER_VALIDATE_URL) == TRUE) {
                        $result['ObjectURL'] = $domain;
                        //}
                    }

                    $event = new CakeEvent("StorageAmazon." . $type . ".putObject.success", $this, array('oid' => $oid, 'type' => $type,'key' => $key, 'name' => $name,'path'=>$path,'url'=>$result['ObjectURL']));
                    $this->getEventManager()->dispatch($event);

                    $file = new File($path);

                    $size = $file->size();
                    if (Configure::read('Storage.storage_amazon_delete_image_after_adding') == "1"){
                        $whitelist_type = array('image/jpeg', 'image/png','image/gif');
                        $path_delete = WWW_ROOT . "uploads";
                        if (in_array($file->mime(), $whitelist_type) && strpos($path,$path_delete) !== FALSE) {
                            $file->delete();
                        }

                    }
                    $file->close();
                    return array('url' => $result['ObjectURL'], 'bucket' => $this->s3Config['bucket'], 'key' => $key,'size'=>$size);
                }
            }
        }
        return false;
    }

    /**
     * Deleting an object is on Amazon's S3
     * @param string $bucket
     * @param string $keyname
     */
    public function deleteObject($bucket, $keyname)
    {
        $result = $this->s3->deleteObject(array(
            'Bucket' => $bucket,
            'Key' => $keyname
        ));

    }

    /**
     * @param $oid
     * @param $type
     * @param $name
     * @param $thumb
     * @param bool $extra
     */
    public function transferObject($oid, $type, $name, $thumb, $extra = false)
    {
        $url = $this->getAwsModel()->getAwsURL($oid, $type, $name);
        if (!$url) {
            if ($this->addMissingObject($oid, $type, $name, $thumb, $extra)) {
                $this->addTransferredObjectToQueue($oid, $type);
            }
        }
    }

    public function addTransferredObjectToQueue($oid, $type)
    {

        foreach ($this->transferedObjects as $record) {
            if ($record[0] == $oid && $record[1] == $type ) {
                return false;
            }
        }
        array_push($this->transferedObjects, array($oid, $type));
        return true;
    }

    public function markTransferredObject()
    {
        if(!empty($this->transferedObjects)){
            $transferModel =  MooCore::getInstance()->getModel("Storage.StorageAwsObjectTransfer");
            foreach ($this->transferedObjects as $object){
                $transferModel->clear();
                $transferModel->save(array(
                    'oid'=>$object[0],
                    'type'=>$object[1],
                ));
            }
            return true;
        }
        return false;
    }
}
