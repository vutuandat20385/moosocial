<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::import('Cron.Task', 'CronTaskAbstract');
App::uses('StorageAmazon', 'Storage.Lib');
App::uses('CakeEventManager', 'Event');

class StorageTaskAwsCronTransfer implements CakeEventListener
{
    protected $_max;

    protected $_count;

    protected $_break;

    protected $_offset;
    protected $transferModel = false;

    public $taskModel;
    public $awsMapModel;
    protected $_eventManager = null;
    protected $_task;
    protected $_cron;
    protected $photoSizes = false;
    protected $nothingToDo = false;
    /**
     * @var boolean
     */
    protected $_wasIdle = false;

    // Main

    /**
     * @return Zend_Db_Table_Row_Abstract
     */
    public function getTask()
    {
        return $this->_task;
    }


    // Informational

    /**
     * @return null|integer
     */
    public function getTotal()
    {
        return null;
    }

    /**
     * @return boolean
     */
    public function wasIdle()
    {
        return $this->_wasIdle;
    }

    /**
     * @param boolean $flag
     * @return Core_Plugin_Task_Abstract
     */
    protected function _setWasIdle($flag = true)
    {
        $this->_wasIdle = (bool) $flag;
        return $this;
    }

    public function log($msg, $type = 'task', $scope = null) {
        if (!is_string($msg)) {
            $msg = print_r($msg, true);
        }

        return CakeLog::write($type, $msg, $scope);
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
    public function __construct($task, $cron = null)
    {
        $this->_task = $task;
        $this->_cron = $cron;
        $this->init();
    }

    public function init()
    {
        $this->taskModel = StorageAmazon::getInstance()->getTaskModel();
        $this->awsMapModel = StorageAmazon::getInstance()->getAwsModel();
    }

    public function execute()
    {
        $db = $this->taskModel->getDataSource();
        $db->begin();
        try {
            $this->_processOne();
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }


    }

    public function _processOne()
    {
        if (Configure::read('Storage.storage_current_type') != 'amazon') {
            return false;
        }

        $event = new CakeEvent("StorageTaskAwsCronTransfer.execute", $this);
        $this->getEventManager()->dispatch($event);
        $this->nothingToDo = !StorageAmazon::getInstance()->markTransferredObject();
        $this->end();
    }
    public function transferObject($oid, $type, $name, $thumb, $extra = false){
        StorageAmazon::getInstance()->transferObject($oid, $type, $name, $thumb, $extra);
    }
    public function getMaxTransferredItemId($type){
        $result = $this->getTransferModel()->find("first", array(
            'fields' => array('MAX(StorageAwsObjectTransfer.oid) as maxID' ),
            'group' => 'StorageAwsObjectTransfer.type',
            'conditions' => array('StorageAwsObjectTransfer.type'=> $type),
            )
        );
        if($result){
            return $result[0]['maxID'];
        }
        return 0;
    }
    private function getTransferModel()
    {
        if (!$this->transferModel) {
            $this->transferModel = MooCore::getInstance()->getModel("Storage.StorageAwsObjectTransfer");
        }

        return $this->transferModel;
    }
    public function photoSizes(){
        if(!$this->photoSizes){
            $this->photoSizes = explode('|', Configure::read('core.photo_image_sizes'));
        }
        return $this->photoSizes;
    }
    public function end(){
        if($this->nothingToDo){
            $taskModel = MooCore::getInstance()->getModel("Cron.Task");
            $taskModel->clear();
            $record = $taskModel->find("first", array(
                'conditions' => array(
                    'plugin' => 'Storage',
                    'class' => 'Storage_Task_Aws_Cron_Transfer',
                )
            ));
            if ($record) {
                $record['Task']['enable'] = 0;
                $taskModel->save($record);
            }
        }
    }
}