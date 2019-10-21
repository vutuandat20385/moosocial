<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class ActivityComment extends AppModel 
{
	public $actsAs = array(
            'Notification',
        'MooUpload.Upload' => array(
            'thumbnail' => array(
               'path' => '{ROOT}webroot{DS}uploads{DS}activitycomments{DS}{field}{DS}',
                'thumbnailSizes' => array(
                    'size' => array('200')
                )
            )
        ),
        'Hashtag' => array(
            'table' => 'activities_comments',
            'field_created_get_hashtag'=>'comment',
            'field_updated_get_hashtag'=>'comment',
        ),
        'Storage.Storage' => array(
            'type'=>array('activitycomments'=>'thumbnail'),
        ),
    );
    
    public $mooFields = array('thumb');
	
	public $hasMany = array( 'Like' => 	array( 'className' => 'Like',	
											   'foreignKey' => 'target_id',
											   'conditions' => array('Like.type' => 'core_activity_comment'),						
											   'dependent'=> true
											 ),
							); 
		
	public $validate = array( 'user_id' => array( 'rule' => 'notBlank'),
							  'activity_id' => array( 'rule' => 'notBlank'),
							  'comment' => array( 'rule' => 'notBlank'),							
						 );
						 
	public $belongsTo = array( 'Activity'  => array('counterCache' => true), 
							   'User' 
	);
	
	public function getThumb($row){
        return 'thumbnail';
    }
	
	public $order = 'ActivityComment.id asc';
	
	public function beforeValidate($options = array()) {
		if (!empty($this->data[$this->alias]['thumbnail'])) {
	        unset($this->validate['comment']);
	    }
	
	    return true;
	}

    public function getActivityCommentHashtags($qid, $limit = RESULTS_LIMIT,$page = 1){
        $cond = array(
            'ActivityComment.id' => $qid,
        );

        $activity_comments = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
        return $activity_comments;
    }

	public function beforeDelete($cascade = true)
	{
		$data = $this->findById($this->id);
		if ($data) {
			$activityModel = MooCore::getInstance()->getModel("Activity");
			$activity = $activityModel->find('first', array(
					'conditions' => array(
						'Activity.id' => $data['ActivityComment']['activity_id']
					))
			);
			if ($activity) {
				$last_comment = $this->find('first', array(
						'conditions' => array(
							'ActivityComment.activity_id' => $activity['Activity']['id'],
							'ActivityComment.id <>' => $data['ActivityComment']['id'],
						),
						'order' => array('ActivityComment.id DESC'))
				);
				$date = $activity['Activity']['created'];
				if ($last_comment) {
					$date = $last_comment['ActivityComment']['created'];
				}

				$activityModel->id = $activity['Activity']['id'];
				$activityModel->save(array('modified' => $date));
			}
		}
	}
}
