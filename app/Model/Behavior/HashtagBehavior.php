<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('ModelBehavior', 'Model');

class HashtagBehavior extends ModelBehavior
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

    public function getHashModel()
    {
        if (!$this->_runtimeModel)
            $this->_runtimeModel = ClassRegistry::init('Hashtag');
        return $this->_runtimeModel;
    }

    public function afterSave(Model $Model, $created, $options = array())
    {
        extract($this->settings[$Model->alias]);
        $field_created_get_hashtag = $field_updated_get_hashtag = "body";
        $restricted_fields = false;
        if (isset($this->settings[$Model->alias]['field_created_get_hashtag']))
            $field_created_get_hashtag = $this->settings[$Model->alias]['field_created_get_hashtag'];
        if (isset($this->settings[$Model->alias]['field_updated_get_hashtag']))
            $field_updated_get_hashtag = $this->settings[$Model->alias]['field_updated_get_hashtag'];
        if(isset($this->settings[$Model->alias]['restricted_fields']))
            $restricted_fields =  $this->settings[$Model->alias]['restricted_fields'];

        $RuntimeModel = $this->getHashModel();

        if ($created) {

            if($restricted_fields){
                if(!isset($Model->data[$Model->alias][$restricted_fields['name']]))
                    return true;

                if($Model->data[$Model->alias][$restricted_fields['name']] != $restricted_fields['value'])
                    return true;
            }
            if(!isset($Model->data[$Model->alias][$field_created_get_hashtag])) return true;
            $hashtags = $this->gethashtags($Model->data[$Model->alias][$field_created_get_hashtag]);
            if(empty($hashtags))
                return true;

            $RuntimeModel->clear();
            $RuntimeModel->save(array('item_id' => $Model->getID(),
                'item_table' => $Model->table,
            	'hashtags' => trim(mb_strtolower($hashtags)),
                'created' => date("Y-m-d H:i:s"),

            ));
        } else {


            $db = $RuntimeModel->getDataSource();

            if(!isset($Model->data[$Model->alias][$field_updated_get_hashtag])){
                return true;
            }
            $hashtags =$this->gethashtags($Model->data[$Model->alias][$field_updated_get_hashtag]);
            $value = $db->value($hashtags, 'string');

            if(!empty($hashtags)){
                $RuntimeModel->updateAll(
                	array('hashtags' => trim(mb_strtolower($value)) ),
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

                if(empty($item  )){
                    $RuntimeModel->clear();
                    $RuntimeModel->save(array('item_id' => $Model->getID(),
                        'item_table' => $Model->table,
                    	'hashtags' => trim(mb_strtolower($hashtags)),
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
        $RuntimeModel = $this->getHashModel();
        $RuntimeModel->deleteAll(
            array('item_id' => $Model->getID(),
                'item_table' => $Model->table,
            ),
            true,
            true);

    }

    private function  gethashtags($text)
    {

        //Match the hashtags
        $text = strip_tags($text);

        preg_match_all('/(#\w+)/u', $text, $matchedHashtags);

        $hashtag = '';
        // For each hashtag, strip all characters but alpha numeric
        if (!empty($matchedHashtags[0])) {
            foreach ($matchedHashtags[0] as $match) {
                $hashtag .= $match . ',';
            }
        }
        $hashtag = str_replace('#', '', $hashtag);
        //to remove last comma in a string

        return rtrim($hashtag, ',');
    }
}