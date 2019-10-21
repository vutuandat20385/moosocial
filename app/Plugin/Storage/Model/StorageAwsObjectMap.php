<?php
App::uses('StorageAppModel', 'Storage.Model');
//App::uses('StorageAmazon', 'Storage.Lib');
App::uses('StorageAwsTask', 'Storage.Model');

class StorageAwsObjectMap extends StorageAppModel
{
    public $awsModel = false;

    /** The cache name of aws
     * @param int $oid
     * @param string $type
     * @param string $name
     * @return string
     */
    public function getCacheName($oid, $type, $name){
        if($oid ==0){
            $cachingName = "aws_map_" . $type . "_" . md5($name);
        }else{
            $cachingName = "aws_map_" . $oid . "_" . $type . "_" . $name;
        }
        return $cachingName;
    }
    /**
     * Get url of object from AWS
     * @param int $oid
     * @param string $type
     * @param string $name
     * @return bool|mixed
     */
    public function getAwsURL($oid, $type, $name)
    {


        $cachingName = $this->getCacheName($oid, $type, $name);
        $result = Cache::read($cachingName, 'storage');
        if (!$result) {

            $record = $this->find('first', array(
                'conditions' => array(
                    'oid' => $oid,
                    'type' => $type,
                    'name' => $name,
                )
            ));

            if ($record) {
                $url = $record['StorageAwsObjectMap']['url'];
                if (Configure::read('Storage.storage_cloudfront_enable') == "1"){
                    if ($record['StorageAwsObjectMap']['key'] == 'file_not_exist'){
                        return false;
                    }
                    $url = rtrim(Configure::read('Storage.storage_cloudfront_cdn_mapping'),"/")."/".$record['StorageAwsObjectMap']['key'];
                }
                Cache::write($cachingName, $url, 'storage');
                return $record['StorageAwsObjectMap']['url'];
            } else {
                return false;
            }

        }

        return $result;
    }
    public function getAwsBaseURl(){
        $result = Cache::read('getAwsBaseURl', 'storage');
        if (!$result) {
            $record = $this->find('first');
            $url = str_replace($record['StorageAwsObjectMap']['key'],'',$record['StorageAwsObjectMap']['url']);
            $url = rtrim($url,"/");
            Cache::write('getAwsBaseURl', $url, 'storage');
            return $url;
        }
        return $result;
    }
    public function destroy($oid, $type)
    {
        $records = $this->find('all', array(
            'conditions' => array(
                'oid' => $oid,
                'type' => $type,
            )
        ));
        if ($records) {
            foreach ($records as $record) {
                if($oid ==0){
                    $cachingName = "aws_map_" . $type . "_" . md5($record['StorageAwsObjectMap']['name']);
                }else{
                    $cachingName = "aws_map_" . $oid . "_" . $type . "_" . $record['StorageAwsObjectMap']['name'];
                }
                Cache::delete($cachingName, 'storage');
                //StorageAmazon::getInstance()->addDeletedObject($oid, $type, $record['StorageAwsObjectMap']['name'], $record['StorageAwsObjectMap']['key'], $record['StorageAwsObjectMap']['bucket']);

                $result = $this->getTaskModel()->find("first", array(
                    'conditions' => array(
                        'StorageAwsTask.oid' => $oid,
                        'StorageAwsTask.name' => $record['StorageAwsObjectMap']['name'],
                        'StorageAwsTask.type' => $type,
                        'StorageAwsTask.action' => "DELETE",
                    )));
                if (!$result) {
                    $this->getTaskModel()->clear();
                    $record = array(
                        'action' => "DELETE",
                        'oid' => $oid,
                        'type' => $type,
                        'name' => $record['StorageAwsObjectMap']['name'],
                        'key' => $record['StorageAwsObjectMap']['key'],
                        'bucket' => $record['StorageAwsObjectMap']['bucket'],
                    );
                    $this->getTaskModel()->save($record);
                }

            }
            $this->deleteAll(array('oid' => $oid, 'type' => $type), false);
        }
    }
    private function getTaskModel()
    {
        if (!$this->awsModel) {
            $this->awsModel = MooCore::getInstance()->getModel("Storage.StorageAwsTask");
        }

        return $this->awsModel;
    }
}