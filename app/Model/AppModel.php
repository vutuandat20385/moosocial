<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Model', 'Model');

class AppModel extends Model {

    public $findMethods = array(
        'all' => true, 'first' => true, 'count' => true,
        'neighbors' => true, 'list' => true, 'threaded' => true,
        'translated' => true
    );
    public $mooFields = array();
    public $recursive = 0;

    /*
     * Initialize the model with an array of empty fields
     * @return array $res
     */

    public function initFields() {
        $res[$this->name] = array_fill_keys(array_keys($this->schema()), '');
        return $res;
    }

    public function updateCounter($id, $field = 'comment_count', $conditions = '',$model = 'Comment') {
        $modelObj = ClassRegistry::init($model);
        $count = (int)$modelObj->find('count', array('conditions' => $conditions));
        $this->query("UPDATE $this->tablePrefix$this->table SET $field=$count WHERE id=" . intval($id));

    }

    public function increaseCounter($id, $field = 'comment_count') {
        $this->query("UPDATE $this->tablePrefix$this->table SET $field=$field+1 WHERE id=" . intval($id));
    }

    public function decreaseCounter($id, $field = 'comment_count') {
        $this->query("UPDATE $this->tablePrefix$this->table SET $field=$field-1 WHERE id=" . intval($id));
    }

    function _findTranslated($state, $query, $results = array()) {
        if ($state == 'before') {
            return array_merge($query, array(
                    //'fields' => array('id', 'name'),
                    //'recursive' => -1
            ));
        } elseif ($state == 'after') {
            if (empty($results)) {
                return $results;
            }
          //  pr($results);
            // get the model's belongs to relation model names
            $belongsTo = Set::extract($this->belongsTo, '/@*');
       
            if (!empty($belongsTo) && isset($belongsTo[0]) && is_array($belongsTo[0]))
                $belongsTo = $belongsTo[0];

            if (!empty($belongsTo))
                foreach ($results as &$result) {
                    foreach ($belongsTo as $modelName) {
                        if (isset($result[$modelName]) &&
                                isset($result[$modelName]['id']) &&
                                !empty($result[$modelName]['id'])) {

                            $data = $this->$modelName->find('first', array(
                                'conditions' => array(
                                    $modelName . '.id' => $result[$modelName]['id']
                                ),
                                'recursive' => -1
                            ));

                            if (!empty($data))
                                $result[$modelName] = $data[$modelName];
                        }
                    }
                }

            return $results;
        }
    }
    
	public function getMooFields() {
		return $this->mooFields;
	}
	
	public function getTitle(&$row)
	{
		if (isset($row['title']))
			return $row['title'];
	}
	
	public function getPlugin($row)
	{		
		return $this->plugin;	
	}	
	
	public function getHref($row)
	{
		return true;
	}
	
	public function getType($row)
	{
		if ($this->plugin)
			return ucfirst($this->plugin).'_'.get_class($this);
		else
			return get_class($this);
	}
	
	public function getUrl($row)
    {    	
		$url = '';
		$href = $this->getHref($row);
		if ($href)
		{
			$request = Router::getRequest();
			$url = str_replace($request->base,'',$href);
		}
		return $url;
    }
	
	public function beforeDelete($cascade = true) 
	{
		$item = $this->findById($this->id);		
		$name = get_class($this);
		if (isset($item[$name]['moo_type']))
		{
			$activityModel = MooCore::getInstance()->getModel('Activity',false);
			$photoModel = MooCore::getInstance()->getModel('Photo_Photo',false);
			$commentModel = MooCore::getInstance()->getModel('Comment',false);
			$likeModel = MooCore::getInstance()->getModel('Like',false);
			
			$activityModel->deleteAll(array('Activity.item_type'=>$item[$name]['moo_type'],'Activity.params'=>'item','Activity.item_id'=>$this->id),true,true);
			$activityModel->deleteAll(array('Activity.type'=>$item[$name]['moo_type'],'Activity.target_id'=>$this->id),true,true);
			
			$photos = $photoModel->find('all', array(
		        'conditions' => array('Photo.type' => $item[$name]['moo_type'],'Photo.target_id'=>$this->id)
		    ));
		    
		    if ($photos)
		    {
		    	foreach ($photos as $photo)
		    	{
		    		$photoModel->delete($photo['Photo']['id']);
		    	}
		    }
			
			$commentModel->deleteAll(array('type'=>$item[$name]['moo_type'],'target_id'=>$this->id),true,true);
			$likeModel->deleteAll(array('type'=>$item[$name]['moo_type'],'target_id'=>$this->id),true,true);
		}
		return true;
	}
	
	
	public function validateTag($values)
	{
		$value = $values[key($values)];
		$value = str_replace(' ', '', $value);
		$value = str_replace(',', '', $value);
		if (!$value)
			return true;
			
		if(preg_match('/[#$%^&*()+=\-\[\]\';,.\/{}|":<>?~\\\\]/', $value)) {
		    return false;
		}
		
		return true;
	}
        
        public function addBlockCondition($cond = array(), $modal_name = null) {           
            $userBlockModal = MooCore::getInstance()->getModel('UserBlock');               
            $blockedUsers = $userBlockModal->getBlockedUsers();
            if(!empty($blockedUsers)){
                $str_blocked_users = implode(',', $blockedUsers);
                $field_name = 'User.id';
                if(empty($modal_name)){
                    $modal_name = $this->name;
                }
                if($modal_name != 'User'){
                    $field_name = $modal_name . '.user_id';
                } 
                $cond[] = "$field_name NOT IN ($str_blocked_users)";
            }
           
            return $cond;
        }
    function getLastQuery() {
        $dbo = $this->getDatasource();
        $logs = $dbo->getLog();
        $lastLog = end($logs['log']);
        return $lastLog['query'];
    }
}
