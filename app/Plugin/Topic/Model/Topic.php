<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('TopicAppModel', 'Topic.Model');

class Topic extends TopicAppModel {
    public $mooFields = array('title','href','plugin','type','url', 'thumb');
    
    public $actsAs = array(
        'MooUpload.Upload' => array(
            'thumbnail' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}topics{DS}{field}{DS}',
                'thumbnailSizes' => array(
                    
                )
            )
        ),
        'Hashtag' ,
        'Storage.Storage' => array(
            'type'=>array('topics'=>'thumbnail'),
        ),
    );
    
    public $recursive = 2;

    public $belongsTo = array('User' => array('counterCache' => true,
            'counterScope' => array('Topic.group_id' => 0)
        ),
        'Category' => array(
            'counterCache' => 'item_count',
            'counterScope' => array('Category.type' => 'Topic')
        ),
        'LastPoster' => array(
            'className' => 'User',
            'foreignKey' => 'lastposter_id'
        ),
        'Group' => array('className'=>'Group.Group','counterCache' => true)
    );
    public $hasMany = array('Comment' => array(
            'className' => 'Comment',
            'foreignKey' => 'target_id',
            'conditions' => array('Comment.type' => 'Topic_Topic'),
            'dependent' => true
        ),
        'Like' => array(
            'className' => 'Like',
            'foreignKey' => 'target_id',
            'conditions' => array('Like.type' => 'Topic_Topic'),
            'dependent' => true
        ),
        'Tag' => array(
            'className' => 'Tag',
            'foreignKey' => 'target_id',
            'conditions' => array('Tag.type' => 'Topic_Topic'),
            'dependent' => true
        )
    );
    public $order = "Topic.last_post desc";
    public $validate = array(
        'title' => array(
            'rule' => 'notBlank',
            'message' => 'Title is required'
        ),
        'category_id' => array(
            'rule' => 'notBlank',
            'message' => 'Category is required'
        ),
        'body' => array(
            'rule' => 'notBlank',
            'message' => 'Body is required'
        ),
        'tags' => array(
        	'validateTag' => array(
        		'rule' => array('validateTag'),
        		'message' => 'No special characters ( /,?,#,%,...) allowed in Tags',
        	)
        )
    );

    /*
     * Get topics based on type
     * @param string $type - possible value: my, home, category, user, search, group
     * @param mixed $param - could be catid (category), uid (home, my, user) or a query string (search)
     * @param int $page - page number
     * @return array $topics
     */

    public function getTopics($type = null, $param = null, $page = 1, $limit = RESULTS_LIMIT) {
        $cond = array('Topic.group_id' => 0);
        $order = null;
        $limit = Configure::read('Topic.topic_item_per_pages');
               
        if ($type == 'group')
            $this->unbindModel(array('belongsTo' => array('Category')));
        else
            $this->unbindModel(array('belongsTo' => array('Group')));

        switch ($type) {
            case 'category':
                if (!empty($param)) {
                    $cond = array('Topic.category_id' => $param, 'Category.type' => 'Topic');
                    $order = 'Topic.pinned desc, Topic.last_post desc';
                }

                break;

            case 'friends':
                if ($param) {
                    App::import('Model', 'Friend');
                    $friend = new Friend();
                    $friends = $friend->getFriends($param);
                    $cond = array('Topic.user_id' => $friends, 'Topic.group_id' => 0);
                }
                break;

            case 'home':
            case 'my':
                if (!empty($param))
                    $cond = array('Topic.user_id' => $param, 'Topic.group_id' => 0);

                break;

            case 'user':
                if ($param)
                    $cond = array('Topic.user_id' => $param, 'Topic.group_id' => 0);

                break;

            case 'search':
                if ($param)
                    $cond = array('Topic.group_id' => 0, 'MATCH(Topic.title, Topic.body) AGAINST(? IN BOOLEAN MODE)' => urldecode($param));

                break;

            case 'group':
                if (!empty($param)) {
                    $cond = array('Topic.group_id' => $param);
                    $order = 'Topic.pinned desc, Topic.last_post desc';
                }

                break;
            default:
                $order = 'Topic.pinned desc, Topic.last_post desc';
        }

        //only get topics of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $topics = $this->find('all', array('conditions' => $cond, 'order' => $order, 'limit' => $limit, 'page' => $page));
        $uid = CakeSession::read('uid');
        App::import('Model', 'NotificationStop');
        $notificationStop = new NotificationStop();
        foreach ($topics as $key => $topic){
            $notification_stop = $notificationStop->find('count', array('conditions' => array('item_type' => APP_TOPIC,
                    'item_id' => $topic['Topic']['id'],
                    'user_id' => $uid)
                    ));
            $topics[$key]['Topic']['notification_stop'] = $notification_stop;
        }
        
        return $topics;
    }

    public function getPopularTopics($limit = 5, $days = null) {
        $this->unbindModel(array('belongsTo' => array('Group')));

        $cond = array('Topic.group_id' => 0);

        if (!empty($days))
            $cond['DATE_SUB(CURDATE(),INTERVAL ? DAY) <= Topic.created'] = intval($days);

        //only get topics of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $topics = Cache::read('topic.popular', 'topic');
        if (!$topics){
            $topics = $this->find('all', array('conditions' => $cond,
                'order' => 'Topic.like_count desc',
                'limit' => intval($limit)
            ));
            Cache::write('topic.popular', 'topic');
        }
        

        return $topics;
    }

    public function deleteTopic($topic) {
        
        // delete activity
        $activityModel = MooCore::getInstance()->getModel('Activity');
        $parentActivity = $activityModel->find('list', array('fields' => array('Activity.id') , 'conditions' => 
            array('Activity.item_type' => 'Topic_Topic', 'Activity.item_id' => $topic['Topic']['id'])));
        
        $activityModel->deleteAll(array('Activity.item_type' => 'Topic_Topic', 'Activity.item_id' => $topic['Topic']['id']), true, true);
        
        // delete child activity
        $activityModel->deleteAll(array('Activity.item_type' => 'Topic_Topic', 'Activity.parent_id' => $parentActivity));
        
        $this->delete($topic['Topic']['id']);
        
        // delete attachments
        App::import('Model', 'Attachment');
        $attachment = new Attachment();
        $attachments = $attachment->getAttachments(PLUGIN_TOPIC_ID, $topic['Topic']['id']);

        foreach ($attachments as $a){
            $attachment->deleteAttachment($a);
        }
    }

    public function afterSave($created, $options = array()) {
        Cache::clearGroup('topic', 'topic');
    }

    public function afterDelete() {
        Cache::clearGroup('topic', 'topic');
        
        // delete attached images in topic
        $photoModel = MooCore::getInstance()->getModel('Photo.Photo');
        $photos = $photoModel->find('all', array('conditions' => array('Photo.type' => 'Topic',
            'Photo.target_id' => $this->id)));
        foreach ($photos as $p){
            $photoModel->delete($p['Photo']['id']);
        }
    }
    
    public function getHref($row)
    {
    	$request = Router::getRequest();
    	if (isset($row['title']) && isset($row['id']))
    		return $request->base.'/topics/view/'.$row['id'].'/'.seoUrl($row['title']);
    	else 
    		return '';
    }
    
    public function getThumb($row){

        return 'thumbnail';
    }

    public function getTitle(&$row)
    {
        if (isset($row['title']))
        {
            $row['title'] = htmlspecialchars($row['title']);
            return $row['title'];
        }
        return '';
    }

    public function getTopicSuggestion($q, $limit = RESULTS_LIMIT,$page = 1){
        $this->unbindModel(	array('belongsTo' => array('Group') ) );
        $cond = array('Topic.title LIKE' =>  $q . "%");

        //only get topics of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $topics = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page) );
        return $topics;
    }

    public function getTopicHashtags($qid, $limit = RESULTS_LIMIT,$page = 1){
        $cond = array(
            'Topic.id' => $qid,
        );

        //only get topics of active user
        $cond['User.active'] = 1;
        $cond = $this->addBlockCondition($cond);
        $topics = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
        return $topics;
    }

    public function updateCounter($id, $field = 'comment_count',$conditions = '',$model = 'Comment') {
        Cache::clearGroup('topic', 'topic');
                
        if(empty($conditions)){
            $conditions = array('Comment.type' => 'Topic_Topic', 'Comment.target_id' => $id);
        }
        
        parent::updateCounter($id, $field, $conditions, $model);
    }
}
