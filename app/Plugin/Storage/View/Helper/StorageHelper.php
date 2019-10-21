<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

App::uses('Helper', 'View');
App::uses('StorageAmazon', 'Storage.Lib');

class StorageHelper extends Helper implements CakeEventListener{
    public $helpers = array('Moo');
    protected $_eventManager = null;
    protected $awsMapModel = false;
    protected $awsTaskModel = false;

    /**
     * @return mixed
     */
    public function getAwsTaskModel()
    {
        if(!$this->awsTaskModel){
            $this->awsTaskModel = MooCore::getInstance()->getModel("Storage.StorageAwsTask");
        }
        return $this->awsTaskModel;
    }
    /**
     * @return mixed
     */
    public function getAwsMapModel()
    {
        if(!$this->awsMapModel){
            $this->awsMapModel = MooCore::getInstance()->getModel("Storage.StorageAwsObjectMap");
        }
        return $this->awsMapModel;
    }
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
    public function getName($type){
        switch ($type){
            case "local":
                return __("Local Storage");
            case "amazon":
                return __("Amazon S3");
            default:

        }
        return __("Local Storage");
    }
    public function isLocalStorage(){
    	return Configure::read('Storage.storage_current_type') == 'local' && !Configure::read('Storage.storage_localcdn_enable');
    }
    public function getStatus($type){
        return ($type == Configure::read('Storage.storage_current_type'))?__("Enabled"):__("Disable");
    }
    public function getS3Region($code){
        switch ($code){
            case "us-east-1":
                return __("US East (N. Virginia)");
            case "us-east-2":
                return __("US East (Ohio)");
            case "us-east-1":
                return __("US West (N. California)");
            case "us-west-2":
                return __("US West (Oregon)");
            case "ap-south-1":
                return __("Asia Pacific (Mumbai)");
            case "ap-northeast-2":
                return __("Asia Pacific (Seoul)");
            case "ap-southeast-1":
                return __("Asia Pacific (Singapore)");
            case "ap-southeast-2":
                return __("Asia Pacific (Sydney)");
            case "ap-northeast-1":
                return __("Asia Pacific (Tokyo)");
            case "eu-central-1":
                return __("EU (Frankfurt)");
            case "eu-west-1":
                return __("EU (Ireland)");
            case "sa-east-1":
                return __("South America (São Paulo)");
            default:
                return "";

        }

    }

    /**
     * @param int $oid
     * @param string $prefix
     * @param string $thumb
     * @param string $type
     * @param mixed $extra
     * @param bool $forceLocal
     * @return bool
     */
    public function getUrl($oid, $prefix, $thumb, $type, $extra=false,$forceLocal=false){
        $url =false;
        $storage_type = Configure::read('Storage.storage_current_type');
        if($storage_type != "local" && !$forceLocal){
            $event =new CakeEvent("StorageHelper.$type.getUrl.".$storage_type, $this,array('type'=>$type,'oid'=>$oid,'prefix'=>$prefix,'thumb'=>$thumb,'extra'=>$extra));
            $this->getEventManager()->dispatch($event);
            $url =  $event->result['url'];
            if(!$url){
                CakeLog::write('storage', "Can not get url from calling event $oid, $prefix, $thumb, $type on "."StorageHelper.$type.getUrl.".$storage_type);
            }
        }
        $this->getEventManager()->dispatch(new CakeEvent("StorageHelper.afterGetUrl.notLocal", $this,array('type'=>$type,'oid'=>$oid,'prefix'=>$prefix,'thumb'=>$thumb,'extra'=>$extra)));
        if(!$url || is_null($url)){
            if (Configure::read('Storage.storage_localcdn_enable') == "1" && Configure::read('Storage.storage_current_type') == "local" && strpos($_SERVER['REQUEST_URI'], '/admin') === false){
                $tmp = $this->request->webroot;
                $this->request->webroot = "/";
                $event = new CakeEvent("StorageHelper.$type.getUrl.local", $this,array('type'=>$type,'oid'=>$oid,'prefix'=>$prefix,'thumb'=>$thumb,'extra'=>$extra));
                $this->getEventManager()->dispatch($event);
                $this->request->webroot = $tmp;
            }else{
                $event = new CakeEvent("StorageHelper.$type.getUrl.local", $this,array('type'=>$type,'oid'=>$oid,'prefix'=>$prefix,'thumb'=>$thumb,'extra'=>$extra));
                $this->getEventManager()->dispatch($event);
            }
            $url =  $event->result['url'];
        }
        $this->getEventManager()->dispatch(new CakeEvent("StorageHelper.beforeReturn.getUrl", $this,array('url'=>$url,'type'=>$type,'oid'=>$oid,'prefix'=>$prefix,'thumb'=>$thumb,'extra'=>$extra)));
        return $url;
    }
    public function getAwsURL($oid,$type,$name,$thumb,$extra=false){
        return StorageAmazon::getInstance()->getAwsURL($oid,$type,$name,$thumb,$extra);
    }
    public function getImage($path){
        return $this->getUrl(0,$path,$path,"img");
    }
    public function defaultCoverUrl(){
        return $this->Moo->defaultCoverUrl();
    }
    public function getNoAvatar($gender,$isSmall=false){
        
        switch ($gender) {
            case"Male" :
                $avatar = Configure::read('core.male_avatar');
            break;
            case"Female" :
                $avatar = Configure::read('core.female_avatar');
            break;
            case"Unknown" :
                $avatar = Configure::read('core.unknown_avatar');
            break;
        }
        if (DS != '/') {
            $avatar = str_replace(DS, '/', $avatar);
        }
        if (!empty($avatar)){ // cover uploaded in admincp
            if($isSmall) {
                    $file = $avatar;
                    $epl = explode('.', $file);
                    $extension = $epl[count($epl) - 1];
                    $avatar = $epl[0] . '-sm.' . $extension;
            }
            return $this->getUrl(0,$avatar,$avatar,"img");
        }
        else {
            if ($isSmall) {
                return $this->getImage("user/img/noimage/".$gender . '-user-sm.png');
            } else {
                return $this->getImage("user/img/noimage/".$gender . '-user.png');
            }
        }
    }
    public function getS3StatusTransfer($returnByCode = false){
        $taskModel = MooCore::getInstance()->getModel("Cron.Task");
        $taskModel->clear();
        $record = $taskModel->find("first", array(
            'conditions' => array(
                'plugin' => 'Storage',
                'class' => 'Storage_Task_Aws_Cron_Transfer',
            )
        ));
        if($record){
           if($record['Task']['enable'] == 1){
                if($returnByCode){
                    return 1;
                }else{
                    return __("In progress");
                }


            }
        }
        if($returnByCode){
            return 0;
        }else{
            return __("Stop");
        }
    }
    public function getS3TotalSize(){
        $total = $this->getAwsMapModel()->find("all",array(
            'fields' => array("SUM(StorageAwsObjectMap.size) AS total"),
        ));
        $size = 0;

        if($total){
            $size = $total[0][0]['total'];
        }
        return $this->formatSizeUnits($size);
    }
    public function getS3Syncing($type){
        $type = ($type == 'img')?array("img","photos"):$type;
        $conditions = array("StorageAwsTask.type"=>$type);
        return $this->getAwsTaskModel()->find("count",array(
            'conditions' => $conditions
        ));

    }
    public function getS3TotalItem(){
        $total = $this->getAwsMapModel()->find("count");
        return $total;
    }
    function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' kB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' '.__('bytes');
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' '.__('byte');
        }
        else
        {
            $bytes = '0 '.__('byte');
        }

        return $bytes;
    }
}
