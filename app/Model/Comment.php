<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class Comment extends AppModel 
{
	public $actsAs = array(
        'MooUpload.Upload' => array(
            'thumbnail' => array(
               'path' => '{ROOT}webroot{DS}uploads{DS}comments{DS}{field}{DS}',
                'thumbnailSizes' => array(
                    'size' => array('200')
                )
            )
        ),
        'Hashtag'=>array(
            'field_created_get_hashtag'=>'message',
            'field_updated_get_hashtag'=>'message',
        ),
        'Storage.Storage' => array(
            'type'=>array('comments'=>'thumbnail'),
        ),
    );

	public $hasMany = array( 'Like' => 	array( 'className' => 'Like',	
											   'foreignKey' => 'target_id',
											   'conditions' => array('Like.type' => 'comment'),						
											   'dependent'=> true
											 ),
							);	
						 
	public $belongsTo = array('User');
	
	public $mooFields = array('thumb');
	
	public $validate = array( 'message' => array( 'rule' => 'notBlank') );
	
	public $order = 'Comment.id desc';
	
	/*
	 * Get comments based on $id and $type
	 * @param int $id - item id
	 * @param string $tyoe - item type
	 * @param int $page
	 * @return array $comments
	 */
	
	public function getComments($id, $type, $page = 1, $cond = array()) {
            
            $offset = 0;
            $comments = array();
            $comment_count = $this->getCommentsCount($id, $type);

        $style_sort = Configure::read('core.comment_sort_style');

        if (in_array($type, array('comment','core_activity_comment')))
        {
            $style_sort = Configure::read('core.reply_sort_style');
        }

        if ($style_sort == COMMENT_RECENT) {
                

                if ($comment_count >= $page * RESULTS_LIMIT) {

                }
                if ($page > 1) {
                    $offset = ($page - 1) * RESULTS_LIMIT;
                }
				if ($type == APP_CONVERSATION)
				{
	                $comments = $this->find('all', array('conditions' => array('Comment.type' => $type,
	                        'Comment.target_id' => $id
	                    ),
	                    'limit' => RESULTS_LIMIT,
	                    'offset' => $offset
	                ));
				}
				else
				{
					$comments = $this->find('all', array('conditions' => array_merge($this->addBlockCondition(),array('Comment.type' => $type,
	                        'Comment.target_id' => $id,
                            $cond
	                    )),
	                    'limit' => RESULTS_LIMIT,
	                    'offset' => $offset
	                ));
				}
            } else if ($style_sort == COMMENT_CHRONOLOGICAL) {
                
                if ($comment_count >= RESULTS_LIMIT)
                    $offset = $comment_count - (RESULTS_LIMIT * intval($page));
                if ($offset >= 0 || ( $offset < 0 && RESULTS_LIMIT >= abs($offset) )) {
                    if ($offset < 0 && RESULTS_LIMIT >= abs($offset)) {
                        $limit = RESULTS_LIMIT - abs($offset);
                        $offset = 0;
                    } else
                        $limit = RESULTS_LIMIT;
                    if ($limit != 0) {
                    	if ($type == APP_CONVERSATION)
						{
	                        $comments = $this->find('all', array('conditions' => array('Comment.type' => $type,
	                                'Comment.target_id' => $id
	                            ),
	                            'limit' => $limit,
	                            'offset' => $offset,
	                            'order' => array('Comment.id ASC')
	                                ));
						}
						else
						{
							$comments = $this->find('all', array('conditions' => array_merge($this->addBlockCondition(),array('Comment.type' => $type,
	                                'Comment.target_id' => $id,
                                    $cond
	                            )),
	                            'limit' => $limit,
	                            'offset' => $offset,
	                            'order' => array('Comment.id ASC')
	                                ));
						}
                    }
                }
            }

            return $comments;
        }

    /*
	 * Get comments count based on $id and $type
	 * @param int $id - item id
	 * @param string $tyoe - item type
	 * @return int $comment_count
	 */
	
	public function getCommentsCount( $id, $type )
	{
		if ($type == APP_CONVERSATION)
		{
			$comment_count = $this->find( 'count', array( 'conditions' =>array( 'Comment.type' => $type, 
																	  		 'Comment.target_id' => $id
									) ) );
		}
		else
		{
			$comment_count = $this->find( 'count', array( 'conditions' => array_merge($this->addBlockCondition(),array( 'Comment.type' => $type, 
																	  		 'Comment.target_id' => $id
									) ) ));
		}
               
		return $comment_count;
	}

    public function getCommentHashtags($qid, $limit = RESULTS_LIMIT,$page = 1){
        $cond = array(
            'Comment.id' => $qid,
        );
        $cond = $this->addBlockCondition($cond);
        $comments = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
        return $comments;
    }

	public function beforeValidate($options = array()) {
		if (!empty($this->data[$this->alias]['thumbnail'])) {
	        unset($this->validate['message']);
	    }
	
	    return true;
	}
	
	public function getThumb($row){
        return 'thumbnail';
    }

    public function beforeDelete($cascade = true)
    {
        $data = $this->findById($this->id);
        if ($data)
        {
            $activityModel = MooCore::getInstance()->getModel("Activity");
            if ($data['Comment']['type'] == 'Photo_Photo')
            {
                $activity = $activityModel->find('first',array(
                    'conditions'=>array(
                        'Activity.item_type'=>'Photo_Album',
                        'Activity.items'=>$data['Comment']['target_id']
                    ),
                ));
            }
            else
            {
                $activity = $activityModel->getItemActivity($data['Comment']['type'], $data['Comment']['target_id']);
            }

            if (!empty($activity)) {
                $last_comment = $this->find('first',array(
                    'conditions'=>array(
                        'Comment.type'=>$data['Comment']['type'],
                        'Comment.target_id'=>$data['Comment']['target_id'],
                        'Comment.id <>'=>$data['Comment']['id'],
                    ),
                    'order' => array('Comment.id DESC'))
                );
                $date = $activity['Activity']['created'];
                if ($last_comment)
                {
                    $date = $last_comment['Comment']['created'];
                }
                $activityModel->id = $activity['Activity']['id'];
                $activityModel->save(array('modified' => $date));
            }
        }
    }
}
 