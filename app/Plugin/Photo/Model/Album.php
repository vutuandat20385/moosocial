<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('PhotoAppModel', 'Photo.Model');

class Album extends PhotoAppModel
{
    public $mooFields = array('title', 'href', 'plugin', 'type', 'url', 'thumb', 'privacy');

    public $belongsTo = array('User' => array(
        'counterCache' => true,
        'counterScope' => array('Photo.type = "album"')
    ),
        'Category' => array('counterCache' => 'item_count',
            'counterScope' => array('Album.privacy' => PRIVACY_EVERYONE,
                'Category.type' => 'Photo',
                'Album.photo_count > 0'))
    );
    public $hasMany = array('Comment' => array(
        'className' => 'Comment',
        'foreignKey' => 'target_id',
        'conditions' => array('Comment.type' => 'Photo_Album'),
        'dependent' => true
    ),
        'Like' => array(
            'className' => 'Like',
            'foreignKey' => 'target_id',
            'conditions' => array('Like.type' => 'Photo_Album'),
            'dependent' => true
        ),
        'Tag' => array(
            'className' => 'Tag',
            'foreignKey' => 'target_id',
            'conditions' => array('Tag.type' => 'Photo_Album'),
            'dependent' => true
        ),
    );
    public $order = "Album.id desc";
    public $validate = array(
        'title' => array(
            'rule' => 'notBlank',
            'message' => 'Title is required'
        ),
        'category_id' => array(
            'rule' => 'notBlank',
            'message' => 'Category is required'
        ),
        'tags' => array(
        	'validateTag' => array(
        		'rule' => array('validateTag'),
        		'message' => 'No special characters ( /,?,#,%,...) allowed in Tags',
        	)
        )
    );
    public $actsAs = array(
        'Hashtag'=>array(
            'field_created_get_hashtag'=>'description',
            'field_updated_get_hashtag'=>'description',
        ),
    );

    /*
     * Get albums based on type
     * @param string $type - possible value: all (default), my, home, friends, user, search
     * @param mixed $param - could be uid (friends, home, my, user) or a query string (search)
     * @param int $page - page number
     * @return array $albums
     */

    public function getAlbums($type = null, $param = null, $page = 1, $limit = RESULTS_LIMIT, $addition_param = '', $role_id = null, $count = false)
    {
        $cond = array();
        $limit = Configure::read('Photo.album_item_per_pages');
        switch ($type) {
            case 'category':
                if (!empty($param)) {
                    if ($role_id == ROLE_ADMIN)
                        $cond = array('Album.category_id' => $param,
                            'Category.type' => 'Photo',
                            'Album.photo_count > ?' => 0,
                            'Album.type' => ''
                        );
                    else
                        $cond = array('Album.category_id' => $param,
                            'Category.type' => 'Photo',
                            'Album.privacy' => PRIVACY_EVERYONE,
                            'Album.photo_count > ?' => 0,
                            'Album.type' => ''
                        );
                }
                break;

            case 'friends':
                if ($param) {
                    App::import('Model', 'Friend');
                    $friend = new Friend();
                    $friends = $friend->getFriends($param);
                    if ($role_id == ROLE_ADMIN)
                        $cond = array('Album.user_id' => $friends,
                            'Album.photo_count > ?' => 0,
                            'Album.type' => ''
                        );
                    else
                        $cond = array('Album.user_id' => $friends,
                            'Album.privacy <> ' . PRIVACY_ME,
                            'Album.photo_count > ?' => 0,
                            'Album.type' => ''
                        );
                }
                break;

            case 'home':
            case 'my':
                if ($param)
                    $cond = array('Album.user_id' => $param);

                break;

            case 'user':
                if ($param) {
                    if ($role_id == ROLE_ADMIN)
                        $cond = array(
                            'Album.photo_count > ?' => 0,
                            'Album.user_id' => $param,
                        );
                    elseif (!empty($addition_param) && $role_id != ROLE_ADMIN) {
                        if (!empty($addition_param['are_friend']))
                            $cond = array(
                                'Album.privacy <> ?' => PRIVACY_ME,
                                'Album.photo_count > ?' => 0,
                                'Album.user_id' => $param
                            );
                    } else
                        $cond = array(
                            'Album.privacy' => PRIVACY_EVERYONE,
                            'Album.photo_count > ?' => 0,
                            'Album.user_id' => $param,
                        );

                }
                break;

            case 'search':
                if ($role_id == ROLE_ADMIN)
                    $cond = array('MATCH(Album.title, Album.description) AGAINST(? IN BOOLEAN MODE)' => urldecode($param),
                        'Album.photo_count > ?' => 0,
                        'Album.type' => ''
                    );
                else
                    $cond = array('MATCH(Album.title, Album.description) AGAINST(? IN BOOLEAN MODE)' => urldecode($param),
                        'Album.privacy' => PRIVACY_EVERYONE,
                        'Album.photo_count > ?' => 0,
                        'Album.type' => ''
                    );
                break;

            default:
                if ($role_id == ROLE_ADMIN)
                    $cond = array(
                        'Album.photo_count > ?' => 0,
                        'Album.type' => ''
                    );
                else
                    $cond = array(
                        'OR' => array(
                            array(
                                'Album.privacy' => PRIVACY_EVERYONE,
                                'Album.photo_count > ?' => 0,
                                'Album.type' => ''
                            ),
                            array(
                                'Album.user_id' => $param,
                                'Album.photo_count > ?' => 0,
                                'Album.type' => ''
                            ),
                            array(
                                'Find_In_Set(Album.user_id,"' . $addition_param . '")',
                                'Album.photo_count > ?' => 0,
                                'Album.type' => '',
                                'Album.privacy' => PRIVACY_FRIENDS
                            )
                        ),
                    );
        }

        //get albums of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        if (!$count) {
            $albums = $this->find('all', array('conditions' => $cond, 'limit' => $limit, 'page' => $page));
        } else {
            $albums = $this->find('count', array('conditions' => $cond));
        }

        return $albums;
    }

    public function getTitle(&$row)
    {
        if (isset($row['type']))
        {
            switch ($row['type'])
            {
                case 'newsfeed':
                    return __("Newsfeed Photos");

                case 'profile':
                    return __("Profile Pictures");

                case 'cover':
                    return __("Cover Pictures");
            }
        }
        if (isset($row['title']))
        {
        	$row['title'] = htmlspecialchars($row['title']);
        }
        return isset($row['title']) ? $row['title'] : '';
    }

    public function getPopularAlbums($limit = 5, $days = null)
    {
        $cond = array('Album.privacy' => PRIVACY_EVERYONE, 'Album.photo_count > ?' => 0, 'Album.type' => '');

        if (!empty($days))
            $cond['DATE_SUB(CURDATE(),INTERVAL ? DAY) <= Album.created'] = intval($days);

        //get albums of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $albums = $this->find('all', array('conditions' => $cond,
            'order' => 'Album.like_count desc',
            'limit' => intval($limit)
        ));

        return $albums;
    }

    public function getUserAlbumByType($uid, $type)
    {
        $album = $this->find('first', array('conditions' => array('Album.user_id' => $uid, 'Album.type' => $type,'User.active' => 1)));

        return $album;
    }

    public function deleteAlbum($album)
    {
        $photoModel = MooCore::getInstance()->getModel('Photo.Photo');
        $photos = $photoModel->getPhotos('Photo_Album', $album['Album']['id'], null, null);
        $activityModel = MooCore::getInstance()->getModel('Activity');

        foreach ($photos as $p){
            $photoModel->delete($p['Photo']['id']);
            $activityModel->deleteAll(array('Activity.action' => 'comment_add_photo', 'Activity.item_type' => 'Photo_Photo', 'Activity.item_id' => $p['Photo']['id'] ));
        }
        
         // delete activity
        $parentActivity = $activityModel->find('list', array('fields' => array('Activity.id') , 'conditions' => 
            array('Activity.item_type' => 'Photo_Photo', 'Activity.item_id' => $album['Album']['id'])));

        $activityModel->deleteAll(array( 'Activity.item_type' => 'Photo_Photo', 'Activity.item_id' => $album['Album']['id'] ), true, true);

        // delete child activity
        $activityModel->deleteAll(array('Activity.item_type' => 'Photo_Photo', 'Activity.parent_id' => $parentActivity));

        $this->delete($album['Album']['id']);
    }

    public function afterSave($created, $options = array())
    {
        Cache::clearGroup('photo', 'photo');
        Cache::delete('category.' . 'Photo_Album');
    }

    public function afterDelete()
    {
        Cache::clearGroup('photo', 'photo');
        Cache::delete('category.' . 'Photo_Album');
    }

    public function getHref($row)
    {
        $request = Router::getRequest();
        if (isset($row['id']) && isset($row['title']))
        {
            if (!isset($row['moo_title']))
            {
                $row['moo_title'] = $this->getTitle($row);
            }
            return $request->base . '/albums/view/' . $row['id'] . '/' . seoUrl($row['moo_title']);
        }
        else
            return '';
    }

    public function getThumb($row)
    {

        return 'cover';
    }
    
    public function getPrivacy($row){
        if (isset($row['privacy'])){
            return $row['privacy'];
        }
        return false;
    }

    public function getPhotoCoverOfFeedAlbum($album_id, $privacy = 1)
    {
        $photo = $this->find('first', array('conditions' => array(
            'target_id' => $album_id,
            'type' => 'Photo_Album',
            'privacy' => $privacy,
        ),
            'order' => 'Album.id desc',
        ));
        return $photo;
    }

    public function getAlbumSuggestion($q, $limit = RESULTS_LIMIT, $page = 1)
    {
    	$cond = array('Album.title LIKE'=>$q . "%", 'Album.privacy' => PRIVACY_EVERYONE);

        //get albums of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $albums = $this->find('all', array('conditions' => $cond, 'limit' => $limit, 'page' => $page));
        return $albums;
    }

    public function getAlbumHashtags($qid, $limit = RESULTS_LIMIT,$page = 1){
        $cond = array(
            'Album.id' => $qid,
          
        );

        //get albums of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $albums = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
        return $albums;
    }

    public function updateCounter($id, $field = 'comment_count',$conditions = '',$model = 'Comment') {
        if(empty($conditions)){
            $conditions = array('Comment.type' => 'Photo_Album', 'Comment.target_id' => $id);
        }
        parent::updateCounter($id, $field, $conditions, $model);
        
    }

    public function decreaseCounter($id, $field = 'comment_count') {
        parent::decreaseCounter($id,$field);
        
    }
}