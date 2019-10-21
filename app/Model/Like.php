<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class Like extends AppModel {
		
	public $belongsTo = array( 'User' );
	
	public $order = array('Like.id desc');
        
        public $actsAs = array('Notification');
	
	public function getLikes( $id, $type, $limit = null, $page = 1 )
	{
		$likes = $this->find( 'all', array( 'conditions' => array_merge($this->addBlockCondition(), array( 'Like.type' => $type, 
																   'Like.target_id' => $id, 
																   'Like.thumb_up' => 1 )),
											'limit' => $limit,
											'page' => $page
							));
		return $likes;
	}
	
	public function getCountLikes($id, $type)
	{
		return $this->find('count',array(
			'conditions'=>array_merge($this->addBlockCondition(), array( 'Like.type' => $type, 
																   'Like.target_id' => $id, 
																   'Like.thumb_up' => 1 )
		)));
	}
	
	public function getCountDisLikes($id,$type)
	{
		return $this->find('count',array(
			'conditions'=>array_merge($this->addBlockCondition(), array( 'Like.type' => $type, 
																   'Like.target_id' => $id, 
																   'Like.thumb_up' => 0 )
		)));
	}

    public function getDisLikes( $id, $type, $limit = null, $page = 1 )
    {
        $dislikes = $this->find( 'all', array( 'conditions' => array_merge($this->addBlockCondition(),array( 'Like.type' => $type,
            'Like.target_id' => $id,
            'Like.thumb_up' => 0 )),
            'limit' => $limit,
            'page' => $page
        ) ) ;
        return $dislikes;
    }

	public function getUserLike( $id, $uid, $type )
	{
		$like = $this->find( 'first', array( 'conditions' => array_merge($this->addBlockCondition(),array( 'Like.type' => $type, 
																	'Like.target_id' => $id, 
																	'Like.user_id' => $uid 
							) )) );
		return $like;
	}
	
	public function getAllUserLikes( $uid )
	{
		$res = array();
		$items = $this->find( 'all', array( 'conditions' => array( 'user_id' => $uid ), 
											'order' => 'Like.id desc', 
											'limit' => RESULTS_LIMIT ) 
							);
                
		$blogids = array();
		$topicids = array();
		$albumids = array();
		$videoids = array();
		$unions = array();
		$likes = array();

		foreach ($items as $item)
		{
			switch ($item['Like']['type'])
			{
				case 'Blog_Blog':
					$blogids[] = $item['Like']['target_id'];
					break;
					
				case 'Topic_Topic':
					$topicids[] = $item['Like']['target_id'];
					break;
					
				case 'Photo_Album':
					$albumids[] = $item['Like']['target_id'];
					break;
					
				case 'Video_Video':
					$videoids[] = $item['Like']['target_id'];
					break;
			}
		}

		if ( !empty($blogids) )
		{
			App::import('Blog.Model', 'Blog');
			$blog = new Blog();
			
			$likes['blog'] = $blog->find( 'all', array( 'conditions' => array( 'Blog.id' => $blogids ) ) );
		}
		
		if ( !empty($topicids) )
		{
			App::import('Topic.Model', 'Topic');
			$topic = new Topic();
			
			$likes['topic'] = $topic->find( 'all', array( 'conditions' => array( 'Topic.id' => $topicids ) ) );
		}
		
		if ( !empty($albumids) )
		{
			App::import('Photo.Model', 'Album');
			$album = new Album();
			
			$likes['album'] = $album->find( 'all', array( 'conditions' => array( 'Album.id' => $albumids ) ) );
		}
		
		if ( !empty($videoids) )
		{
			App::import('Video.Model', 'Video');
			$video = new Video();
			
			$likes['video'] = $video->find( 'all', array( 'conditions' => array( 'Video.id' => $videoids ) ) );
		}
		
		return $likes;
	}

	public function getActivityLikes( $activities, $uid )
	{
		$res = array();
		$activity_ids = array();
		$activity_comment_ids = array();
		$comment_ids = array();
			
		foreach ( $activities as $activity )
		{
			$activity_ids[] = $activity['Activity']['id'];
			
			foreach ( $activity['ActivityComment'] as $comment )
				$activity_comment_ids[] = $comment['id'];
			
			if ( !empty( $activity['ItemComment'] ) )
				foreach ( $activity['ItemComment'] as $comment )
					$comment_ids[] = $comment['Comment']['id'];
				
			if ( !empty( $activity['PhotoComment'] ) ) {
				foreach ($activity['PhotoComment'] as $comment)
					$photo_comment_ids[] = $comment['Comment']['id'];
			}
		}
		
		if ( !empty( $activity_ids ) )
			$res['activity_likes'] = $this->find( 'list', array( 'conditions' => array( 'user_id' => $uid, 
																				 		'type' => 'activity', 
																				 		'target_id' => $activity_ids
																			   		  ),
														  		 'fields' => array( 'Like.target_id', 'Like.thumb_up' )
												) );
												
		if ( !empty( $activity_comment_ids ) )									
			$res['comment_likes'] = $this->find( 'list', array( 'conditions' => array( 'user_id' => $uid, 
																				 	   'type' => 'core_activity_comment', 
																				 	   'target_id' => $activity_comment_ids
																			   		 ),
														  		'fields' => array( 'Like.target_id', 'Like.thumb_up' )
												) );
											
		if ( !empty( $comment_ids ) )	
			$res['item_comment_likes'] = $this->find( 'list', array( 'conditions' => array('user_id' => $uid, 
																					 	   'type' => 'comment', 
																					 	   'target_id' => $comment_ids
																				   		 ),
															  		 'fields' => array( 'Like.target_id', 'Like.thumb_up' )
													) );
		if ( !empty( $photo_comment_ids ) ) {
			$res['photo_comment_likes'] = $this->find('list', array('conditions' => array('user_id' => $uid,
					'type' => 'comment',
					'target_id' => $photo_comment_ids
			),
					'fields' => array('Like.target_id', 'Like.thumb_up')
			));
		}
		
		return $res;
	}
	
	public function getCommentLikes( $comments, $uid )
	{
		$comment_ids = array();
			
		foreach ( $comments as $comment )
			$comment_ids[] = $comment['Comment']['id'];;
									
		$comment_likes = $this->find( 'list', array( 'conditions' => array( 'user_id' => $uid, 
																			'type' => 'comment', 
																			'target_id' => $comment_ids
																		  ),
													 'fields' => array( 'Like.target_id', 'Like.thumb_up' )
									) );
									
		return $comment_likes;
	}
        
        public function getBlockLikeCount( $id, $type , $is_like = 1)
	{
		$comment_count = $this->find( 'count', array( 'conditions' => array_merge($this->addBlockCondition(),array( 'Like.type' => $type, 
                                                                                                                            'Like.target_id' => $id,
                                                                                                                            'Like.thumb_up' => $is_like 
									) ) ));
		return $comment_count;
	}

    public function getUserLikeByType( $uid, $type )
    {
        $like = $this->find( 'list', array(
            'conditions' => array_merge($this->addBlockCondition(),array( 'Like.type' => $type,
                'Like.user_id' => $uid
            ) ),
            'fields' => array('Like.target_id', 'Like.thumb_up')
        ));
        return $like;
    }
}
