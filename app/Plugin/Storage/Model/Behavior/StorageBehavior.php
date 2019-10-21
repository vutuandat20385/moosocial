<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
App::uses('ModelBehavior', 'Model');

class StorageBehavior extends ModelBehavior
{
    /**
     * Used for runtime configuration of model
     *
     * @var array
     */
    public $runtime = array();

    /**
     * Stores the joinTable object for generating joins.
     *
     * @var object
     */
    protected $_joinTable;

    /**
     * Stores the runtime model for generating joins.
     *
     * @var Model
     */
    protected $_runtimeModel;
    protected $_awsObjectMap;

    public function setup(Model $Model, $settings = array())
    {
        $this->settings[$Model->alias] = (array)$settings;
    }

    public function getAwsObjectMapModel()
    {
        if (!$this->_awsObjectMap)
            $this->_awsObjectMap = ClassRegistry::init('Storage.StorageAwsObjectMap');
        return $this->_awsObjectMap;
    }

    public function afterSave(Model $Model, $created, $options = array())
    {
        if ($created) {
        } else {
            if (isset($this->settings[$Model->alias]['type'])) {
                if (Configure::read('Storage.storage_current_type') == 'amazon') {
                    if(isset($this->settings[$Model->alias]['type'])){
                        foreach ($this->settings[$Model->alias]['type'] as $type => $field){
                            if(array_key_exists($field,$Model->data[$Model->alias])){
                                $this->getAwsObjectMapModel()->destroy($Model->getID(), $type);
                            }
                        }

                    }
                    /*
                    if(is_array($this->settings[$Model->alias]['type'])){
                        foreach ($this->settings[$Model->alias]['type'] as $type){
                            $this->getAwsObjectMapModel()->destroy($Model->getID(), $type);
                        }
                    }else{
                        $this->getAwsObjectMapModel()->destroy($Model->getID(), $this->settings[$Model->alias]['type']);
                    }
                    */
                }
            }

        }
    }

    public function afterDelete(Model $Model)
    {
        if (!$Model->id) {
            return true;
        }
        if (isset($this->settings[$Model->alias]['type'])) {
            if (Configure::read('Storage.storage_current_type') == 'amazon') {
                if(is_array($this->settings[$Model->alias]['type'])){
                    foreach ($this->settings[$Model->alias]['type'] as $type){
                        $this->getAwsObjectMapModel()->destroy($Model->getID(), $type);
                    }
                }else{
                    $this->getAwsObjectMapModel()->destroy($Model->getID(), $this->settings[$Model->alias]['type']);
                }

            }
        }


    }
}
