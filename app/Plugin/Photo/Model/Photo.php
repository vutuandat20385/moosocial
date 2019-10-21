<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('PhotoAppModel','Photo.Model');
class Photo extends PhotoAppModel {
    public $mooFields = array('title','href','plugin','type','url', 'thumb', 'privacy');

    public $actsAs = array(
        'MooUpload.Upload' => array(
            'thumbnail' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}photos{DS}{field}{DS}{year}{DS}{month}{DS}{day}{DS}',
            )
        ),
        'Hashtag'=>array(
            'field_created_get_hashtag'=>'caption',
            'field_updated_get_hashtag'=>'caption',
        ),
        'Storage.Storage' => array(
            'type'=>array('photos'=>'thumbnail'),
        ),
    );

    public $belongsTo = array('Album' => array(
        'foreignKey' => 'target_id',
        'conditions' => 'Photo.type = "Photo_Album"',
        'counterCache' => true,
        'counterScope' => 'Photo.type = "Photo_Album"',
        'className' => 'Photo.Album',
    ),
        'Group' => array(
            'foreignKey' => 'target_id',
            'conditions' => 'Photo.type = "Group_Group"',
            'counterCache' => true,
            'counterScope' => 'Photo.type = "Group_Group"',
            'className' => 'Group.Group',
        ),
        'User' => array(
            'counterCache' => true,
            'counterScope' => array('Photo.type = "Photo_Album"')
        )
    );
    public $hasMany = array('Comment' => array(
        'className' => 'Comment',
        'foreignKey' => 'target_id',
        'conditions' => array('Comment.type' => 'Photo_Photo'),
        'dependent' => true
    ),
        'Like' => array(
            'className' => 'Like',
            'foreignKey' => 'target_id',
            'conditions' => array('Like.type' => 'Photo_Photo'),
            'dependent' => true
        ),
        'Tag' => array(
            'className' => 'Tag',
            'foreignKey' => 'target_id',
            'conditions' => array('Tag.type' => 'Photo_Photo'),
            'dependent' => true
        )
    );
    public $validate = array('type' => array('rule' => 'notBlank'),
        'target_id' => array('rule' => 'notBlank'),
        'user_id' => array('rule' => 'notBlank'),
        'path' => array('rule' => 'notBlank'),
        //  'thumbnail' => array('rule' => 'notBlank')
    );
    public $order = 'Photo.id desc';

    /*
     * Get photos based on type
     * @param string $type - possible value: album, group
     * @param int $target_id - could be album_id or group_id
     * @param int $page - page number
     * @return array $photos
     */

    public function getPhotos($type = null, $target_id = null, $page = 1, $limit = 5, $params = array()) {

        list($plugin, $name) = mooPluginSplit($type);
        if ($type != 'Photo_Album')
        {
            $this->bindModel(
                array('belongsTo' =>
                    array($name=>
                        array
                        (
                            'className' => ($plugin ? $plugin.'.' : '').$name,
                            'foreignKey' => 'target_id',
                            'conditions' => 'Photo.type = "'.$type.'"',
                            'counterCache' => true,
                            'counterScope' => 'Photo.type = "'.$type.'"'
                        )
                    )
                )
            );
        }

        $cond = array('Photo.type' => $type, 'target_id' => $target_id);
        if (isset($params['newsfeed']))
        {
            if (!isset($params['is_friend']) || !$params['is_friend'])
            {
                $cond['Photo.privacy'] = PRIVACY_EVERYONE;
            }
        }

        //get photos of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        if (!empty($page)){
            $photos = $this->find('all', array('conditions' => $cond, 'limit' => $limit, 'page' => $page));
        }else{
            $photos = $this->find('all', array('conditions' => $cond, 'limit' => $limit));
        }
        return $photos;
    }

    public function getFeedPhotos($album_type, $album_type_id, $page = 1, $limit = 5, $params = array()) {

        $cond = array('Photo.album_type' => $album_type, 'Photo.album_type_id' => $album_type_id);

        //get photos of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        if (!empty($page)){
            $photos = $this->find('all', array('conditions' => $cond, 'limit' => $limit, 'page' => $page));
        }else{
            $photos = $this->find('all', array('conditions' => $cond, 'limit' => $limit));
        }
        return $photos;
    }

    public function getFeedPhotosCount($album_type = null, $album_type_id = null){
        $cond = array('Photo.album_type' => $album_type, 'Photo.album_type_id' => $album_type_id);

        //get photos of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $count = $this->find('count', array('conditions' => $cond));

        return $count;
    }

    public function getAllFeedPhotos($album_type = null, $album_type_id = null){
        $cond = array('Photo.album_type' => $album_type, 'Photo.album_type_id' => $album_type_id);

        //get photos of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $photos = $this->find('all', array('conditions' => $cond));
        return $photos;
    }

    public function getPhotosCount($type = null, $target_id = null, $params = array()){
        $cond = array('Photo.type' => $type, 'target_id' => $target_id);

        if (isset($params['newsfeed']))
        {
            if (!isset($params['is_friend']) || !$params['is_friend'])
            {
                $cond['Photo.privacy'] = PRIVACY_EVERYONE;
            }
        }

        //get photos of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $count = $this->find('count', array('conditions' => $cond));

        return $count;
    }

    public function getAllPhotos($type = null, $target_id = null, $params = array()){
        $cond = array('Photo.type' => $type, 'target_id' => $target_id);

        if (isset($params['newsfeed']))
        {
            if (!isset($params['is_friend']) || !$params['is_friend'])
            {
                $cond['Photo.privacy'] = PRIVACY_EVERYONE;
            }
        }

        //get photos of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $photos = $this->find('all', array('conditions' => $cond));
        return $photos;
    }

    public function getPhotoHashtags($qid, $limit = RESULTS_LIMIT,$page = 1)
    {
        $cond = array(
            'Photo.id' => $qid,

        );

        //get photos of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $photos = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
        return $photos;
    }
    
    public function afterSave($created, $options = array())
    {
    	if ($created)
    	{
    		$photoData= $this->data['Photo'];    		
    		$prefix = '1500_';
    		if (!empty($photoData['thumbnail'])) {
    			if ($photoData['year_folder']) { // hacking for MOOSOCIAL-2771
    				$year = date('Y', strtotime($photoData['created']));
    				$month = date('m', strtotime($photoData['created']));
    				$day = date('d', strtotime($photoData['created']));
    				$path = WWW_ROOT . "uploads" . DS . "photos" . DS . "thumbnail" . DS . $year . DS . $month . DS . $day . DS . $photoData['id'] . DS . $prefix . $photoData['thumbnail'];
    			} else {
    				$path = WWW_ROOT . "uploads" . DS . "photos" . DS . "thumbnail" . DS . $photoData['id'] . DS . $prefix . $photoData['thumbnail'];
    			}
    			
    			if (is_file($path))
    			{
    				$info = @getImageSize($path);
    				if (!empty($info[0]) && !empty($info[1]))
    				{
    					$this->query("UPDATE $this->tablePrefix$this->table SET size='".$info[0].','.$info[1]."' WHERE id=" . intval($photoData['id']));
    				}
    			}
    		}
    		
    		
    	}
    	parent::afterSave($created,$options);
    }

    public function beforeDelete($cascade = true)
    {
        $photo = $this->findById($this->id);
        $this->clearCache($photo);

        if ($photo['Photo']['path'] && file_exists(WWW_ROOT . $photo['Photo']['path']))
            unlink(WWW_ROOT . $photo['Photo']['path']);

        if ($photo['Photo']['thumbnail'] && file_exists(WWW_ROOT . $photo['Photo']['thumbnail']))
            unlink(WWW_ROOT . $photo['Photo']['thumbnail']);

        if (!empty($photo['Photo']['original']) && file_exists(WWW_ROOT . $photo['Photo']['original']))
            unlink(WWW_ROOT . $photo['Photo']['original']);

        //delete activity feed on items
        $activityModel = MooCore::getInstance()->getModel('Activity');
        $activities = $activityModel->find('all',array(
            'conditions'=>array(
                'OR' => array(
                    array(
                        'CONCAT(",",Activity.items,",") LIKE'=>'%,'.$this->id.',%',
                        'Activity.item_type'=>'Photo_Album'
                    ),
                    array(
                        'Activity.item_id' => $this->id,
                        'Activity.item_type'=>'Photo_Photo'
                    )
                )
            )
        ));
        if (count($activities))
        {
            foreach ($activities as $activity)
            {
                $items = array_filter(explode(',',$activity['Activity']['items']));
                $items = array_diff($items,array($this->id));

                if (!count($items))
                {
                    $activityModel->delete($activity['Activity']['id']);
                }
                else
                {
                    $activityModel->updateAll(array('items'=>'"'.implode(',',$items).'"'),array('Activity.id'=>$activity['Activity']['id']));
                }
            }
        }
        $this->clearCache($photo);

        parent::beforeDelete($cascade);
    }

    public function clearCache($row)
    {
        $type = $row['Photo']['type'];
        $target_id= $row['Photo']['target_id'];
        $key = '_'.$type.'.'.$target_id;

        Cache::clearGroup($key, 'photo');
    }

    public function afterDelete() {
        Cache::clearGroup('photo', 'photo');
        Cache::delete('category.'.'Photo_Album');
    }

    public function getHref($row)
    {
        $request = Router::getRequest();
        if (isset($row['id']))
            return $request->base.'/photos/view/'.$row['id'];
        else
            return '';
    }

    public function getPrivacy($row){
        if (isset($row['privacy'])){
            return $row['privacy'];
        }
        return false;
    }

    public function getThumb($row){

        return 'thumbnail';
    }

    public function getPhotoCoverOfFeedAlbum($album_id,$privacy = 1)
    {
        $photo = $this->find('first', array('conditions' => array(
            'Photo.target_id' => $album_id,
            'Photo.type' => 'Photo_Album',
            'Photo.privacy' => $privacy,
        ),
            'order' => 'Photo.id desc',
        ));
        return $photo;
    }

    public function updateCounter($id, $field = 'comment_count',$conditions = '',$model = 'Comment') {
        if(empty($conditions)){
            $conditions = array('Comment.type' => 'Photo_Photo', 'Comment.target_id' => $id);
        }
        parent::updateCounter($id, $field, $conditions, $model);

    }

    public function decreaseCounter($id, $field = 'comment_count') {
        parent::decreaseCounter($id,$field);

    }
}
