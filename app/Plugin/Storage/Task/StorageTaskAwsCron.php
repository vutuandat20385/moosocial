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

class StorageTaskAwsCron extends CronTaskAbstract
{
    protected $_max;

    protected $_count;

    protected $_break;

    protected $_offset;

    public $taskModel;
    public $awsMapModel;

    public function __construct($task, $cron = null)
    {
        parent::__construct($task, $cron);
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

        $this->_max = 10;
        $this->_count = 0;
        $this->_break = false;
        $this->_offset = 0;

        while ($this->_count <= $this->_max && $this->_offset <= $this->_max && !$this->_break) {
            // We should run each mail in a try-catch-transaction, not all at once
            $db->begin();
            try {
                $this->_processOne();
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
            }
            $this->_offset++;
        }

    }

    protected function _processOne()
    {
        if (Configure::read('Storage.storage_current_type') != 'amazon') {
            return false;
        }
        $taskRow = $this->taskModel->find('first', array(
            'order' => array('id' => 'ASC')
        ));


        if (!$taskRow) {
            $this->_break = true;
            return;
        } else {
            $params = $taskRow["StorageAwsTask"];
            switch ($params["action"]) {
                case "DELETE":
                    if($params['key'] != "file_not_exist"){
                        StorageAmazon::getInstance()->deleteObject($params['bucket'], $params['key']);
                    }
                    $this->taskModel->delete($params['id']); // Hacking lol
                    break;
                case "PUT":
                default:
                    $result = StorageAmazon::getInstance()->putObject($params['path'], $params['oid'], $params['name'], $params['type'],$params['key']);
                    if ($result) {
                        $this->awsMapModel->clear();
                        $rAwsMap = $this->awsMapModel->find('first', array(
                                'conditions' => array(
                                    'StorageAwsObjectMap.oid' => $params['oid'],
                                    'StorageAwsObjectMap.name' => $params['name'],
                                    'StorageAwsObjectMap.type' => $params['type'],
                                )
                            )
                        );
                        if ($rAwsMap) {
                            $rAwsMap['StorageAwsObjectMap']['url'] = $result['url'];
                            $rAwsMap['StorageAwsObjectMap']['bucket'] = $result['bucket'];
                            $rAwsMap['StorageAwsObjectMap']['key'] = $result['key'];
                            $this->awsMapModel->save($rAwsMap);
                        } else {
                            $this->awsMapModel->save(array(
                                'oid' => $params['oid'],
                                'name' => $params['name'],
                                'type' => $params['type'],
                                'url' => $result['url'],
                                'bucket' => $result['bucket'],
                                'key' => $result['key'],
                                'size' => $result['size'],
                            ));
                        }
                    // Hacking for no-image
                        if($params['oid'] == 0 && $params['type'] == "img" &&
                            (strpos($result['key'], 'noimage') !== false ||
                            strpos($result['key'], 'no-image') !== false)
                        ){

                            $records = $this->awsMapModel->find('all', array(
                                'conditions' => array('key' => "file_not_exist", 'size' => 0,'url LIKE'=>'%'.substr($result['key'],8).'%')
                            ));
                            if ($records) {
                                foreach ($records as $record) {
                                    $this->awsMapModel->destroy($record['oid'],$record['type']);
                                }
                            }
                            $this->awsMapModel->deleteAll(array('key' => "file_not_exist", 'size' => 0,'url LIKE'=>'%'.substr($result['key'],8).'%'), false);
                        }
                    }
                $this->taskModel->delete($params['id']); // Delete when uploading fail
            }


        }
    }
}