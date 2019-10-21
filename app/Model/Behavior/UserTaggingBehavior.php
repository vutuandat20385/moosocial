<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('ModelBehavior', 'Model');

class UserTaggingBehavior extends ModelBehavior
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

    public function setup(Model $Model, $settings = array())
    {
        $this->settings[$Model->alias] = (array)$settings;
    }

    public function getRuntimeModel()
    {
        if (!$this->_runtimeModel)
            $this->_runtimeModel = ClassRegistry::init('UserTagging');
        return $this->_runtimeModel;
    }

    public function afterSave(Model $Model, $created, $options = array())
    {
        extract($this->settings[$Model->alias]);
        $field_created = $field_updated = "userTagging";

        if (isset($this->settings[$Model->alias]['field_created']))
            $field_created = $this->settings[$Model->alias]['field_created'];
        if (isset($this->settings[$Model->alias]['field_updated']))
            $field_updated = $this->settings[$Model->alias]['field_updated'];
        $restricted_fields = false;
        if(isset($this->settings[$Model->alias]['restricted_fields']))
            $restricted_fields =  $this->settings[$Model->alias]['restricted_fields'];

        $RuntimeModel = $this->getRuntimeModel();

        if ($created) {
            if($restricted_fields){
                if(!isset($Model->data[$Model->alias][$restricted_fields['name']]))
                    return true;

                if($Model->data[$Model->alias][$restricted_fields['name']] != $restricted_fields['value'])
                    return true;
            }
            if(!isset($Model->data[$Model->alias][$field_created])) return true;
            $userIds = $this->filterUsers($Model->data[$Model->alias][$field_created]);
            if(empty($userIds))
                return true;

            $RuntimeModel->clear();
            $RuntimeModel->save(array('item_id' => $Model->getID(),
                'item_table' => $Model->table,
                'users_taggings' => $userIds,
                'created' => date("Y-m-d H:i:s"),
            ));
        } else {


            $db = $RuntimeModel->getDataSource();

            if(!isset($Model->data[$Model->alias][$field_updated])){
                return true;
            }
            $userIds =$this->filterUsers($Model->data[$Model->alias][$field_updated]);
            $userIds = $db->value($userIds, 'string');

            if(!empty($userIds)){
                $RuntimeModel->updateAll(
                    array('users_taggings' => $userIds),
                    array('item_id' => $Model->getID(),
                        'item_table' => $Model->table,
                    )
                );
                $item = $RuntimeModel->find('count',array(
                    'fields' => 'id',
                    'conditions' => array(
                        'item_id' => $Model->getID(),
                        'item_table' => $Model->table,
                    )
                ));

                if(empty($item)){
                    $RuntimeModel->clear();
                    $RuntimeModel->save(array('item_id' => $Model->getID(),
                        'item_table' => $Model->table,
                        'users_taggings' => $userIds,
                        'created' => date("Y-m-d H:i:s"),

                    ));
                }
            }else{
                $RuntimeModel->deleteAll(
                    array('item_id' => $Model->getID(),
                        'item_table' => $Model->table,
                    ),
                    true,
                    true);
            }


        }
    }

    public function afterDelete(Model $Model)
    {
        if (!$Model->id) {
            return true;
        }

        extract($this->settings[$Model->alias]);
        $RuntimeModel = $this->getRuntimeModel();
        $RuntimeModel->deleteAll(
            array('item_id' => $Model->getID(),
                'item_table' => $Model->table,
            ),
            true,
            true);

    }

    private function  filterUsers($ids=null)
    {
        if (empty($ids)) return false;
        $ids = explode(",",$ids);
        if (empty($ids)) return false;
        $in_SQL = '';
        foreach($ids as $id){
            $in_SQL.=(is_int((int)$id)? ((int)$id).",":"");
        }
        $in_SQL = trim($in_SQL,',');
        if(empty($in_SQL)) return false;
        $RuntimeModel = $this->getRuntimeModel();
        $db = ConnectionManager::getDataSource('default');
        $prefix = (!empty($db->config['prefix'])? $db->config['prefix']:'');

        $sql = "SELECT id FROM ".$prefix."users WHERE id IN($in_SQL)";
        $users = $db->fetchAll($sql);

        $results = Hash::extract($users, '{n}.'.$prefix.'users.id');
        return implode(',',$results);
    }
    public function beforeFind(Model $Model, $query) {
        $db = $Model->getDataSource();
        if (!is_string($query['fields']) || $query['fields'] !== "COUNT(*) AS {$db->name('count')}") {
            $RuntimeModel = $this->getRuntimeModel();
            $type = $Model->findQueryType;
            if (!empty($RuntimeModel->tablePrefix)) {
                $tablePrefix = $RuntimeModel->tablePrefix;
            } else {
                $tablePrefix = $db->config['prefix'];
            }
            $joinTable = new StdClass();
            $joinTable->tablePrefix = $tablePrefix;
            $joinTable->table = $RuntimeModel->table;
            $joinTable->schemaName = $RuntimeModel->getDataSource()->getSchemaName();

            $query['joins'][] = array(
                'type' => 'LEFT',
                'alias' => $RuntimeModel->alias,
                'table' => $joinTable,
                'conditions' => array(
                    $Model->escapeField() => $db->identifier($RuntimeModel->escapeField('item_id')),
                    $RuntimeModel->escapeField('item_table') => $Model->table,
                ),
            );
            $tables = array();
            if (!empty($query['fields']) && is_array($query['fields'])) {
                $query['fields'][] = $RuntimeModel->escapeField('users_taggings');
            }else{
                $query['fields'] = array('*');
            }
        }





        return $query;
    }
    public function afterFind(Model $Model, $results, $primary = false) {
        foreach ($results as $result){
            if(isset($result['UserTagging']['users_taggings']) && !empty($result['UserTagging']['users_taggings'])){
                
                if (MooPeople::getInstance()->getStatus() == 'onRender'){
                    $userModel = MooCore::getInstance()->getModel('User');
                    $ids = explode(',',$result['UserTagging']['users_taggings']);
                    $users = $userModel->find('all', array('conditions' => array("User.id" => $ids)));
                    MooPeople::getInstance()->add(Hash::combine($users, '{n}.User.id', '{n}'));
                }
                else{
                    MooPeople::getInstance()->register(explode(',',$result['UserTagging']['users_taggings']));
                }
                
            }
        }

        return $results;
    }

}