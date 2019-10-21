<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class LikesController extends AppController {

	public function ajax_add($type = null, $id = null, $thumb_up = null,$isReponse = true)
	{
		$id = intval($id);
		$this->autoRender = false;
		$this->_checkPermission( array( 'confirm' => true ) );
        $this->loadModel("User");

		$uid = $this->Auth->user('id');

        if ($type == 'activity') {
            $this->loadModel('Activity');
            $activity = $this->Activity->findById($id);
        }

		list($plugin, $model) = mooPluginSplit($type);
        
		if ($plugin)
			$this->loadModel( $plugin.'.'.$model );
		else
			$this->loadModel( $model );

		$item = $this->$model->findById( $id );	
		$this->_checkExistence( $item );

        // clear cache item
        switch ( $type )
        {
            case APP_PHOTO:
                Cache::delete('photo.photo_view_'.$id, 'photo');
                break;
            default:
                break;
        }
        
        // check to see if user already liked this item
        $like = $this->Like->getUserLike( $id, $uid, $type );
        $this->$model->id = $id;
        if ( !empty( $like ) ) // user already thumb up/down this item
        {
            if ( $like['Like']['thumb_up'] != $thumb_up )
            {
                $this->Like->id = $like['Like']['id'];
                $this->Like->save( array( 'thumb_up' => $thumb_up ) );

                if ( $thumb_up ) // user thumbed down before
                {
                    $this->$model->updateCounter($id, 'like_count', array('Like.type' => $type, 'Like.target_id' => $id, 'Like.thumb_up' => 1), 'Like');
                    $this->$model->updateCounter($id, 'dislike_count', array('Like.type' => $type, 'Like.target_id' => $id, 'Like.thumb_up' => 0), 'Like');

                    if(!empty($activity))
                        $this->updatePhotoLike($activity,$thumb_up);
                    
                }
                else
                {
                    $this->$model->updateCounter($id, 'like_count', array('Like.type' => $type, 'Like.target_id' => $id, 'Like.thumb_up' => 1), 'Like');
                    $this->$model->updateCounter($id, 'dislike_count', array('Like.type' => $type, 'Like.target_id' => $id, 'Like.thumb_up' => 0), 'Like');
                    if(!empty($activity))
                        $this->updatePhotoLike($activity,$thumb_up);

                }

            }
            else // remove the entry
            {
                $this->Like->delete( $like['Like']['id'] );
                if (!empty($activity)) {
                    $this->updatePhotoLike($activity,$thumb_up, true);
                }
                if ( $thumb_up )
                {
                    $this->$model->updateCounter($id, 'like_count', array('Like.type' => $type, 'Like.target_id' => $id, 'Like.thumb_up' => 1), 'Like');

                }
                else
                {
                    $this->$model->updateCounter($id, 'dislike_count', array('Like.type' => $type, 'Like.target_id' => $id, 'Like.thumb_up' => 0), 'Like');
                }
                  
            }
        }
        else 
        {
    		$data = array('type' => $type, 'target_id' => $id, 'user_id' => $uid, 'thumb_up' => $thumb_up);
    		$this->Like->save($data);
    		
    		if ( $thumb_up )
            {
                $this->$model->updateCounter($id, 'like_count', array('Like.type' => $type, 'Like.target_id' => $id, 'Like.thumb_up' => 1), 'Like');

                //user like activity photo with 1 photo
                if(!empty($activity))
                    $this->updatePhotoLike($activity,$thumb_up);

                // do not send notification when user like comment
                if ( !in_array( $type, array('core_activity_comment', 'comment') ) )
                {       
                    // send notification to author
                    if ( $uid != $item['User']['id'] )
                    {                                
                        switch ( $type )
                        {
                            case 'Photo_Photo':
                                $action = 'photo_like';
                                $params = '';
                                break;
                                
                            case 'activity':
                                $action = 'activity_like';
                                $params = '';
                                break;

                            case 'core_activity_comment':
                                $action = 'item_like';
                                $params = '';
                                break;

                            default:
                                $action = 'item_like';
                                $params = isset($item[$model]['title']) ? $item[$model]['title'] : '';
                                
                                if (empty($params)){
                                    $params = isset($item[$model]['moo_title']) ? $item[$model]['moo_title'] : '';
                                }
                        }
                                        
                        if ( !empty( $item[$model]['group_id'] ) ) // group topic / video
                        {
                            $url = '/groups/view/' . $item[$model]['group_id'] . '/' . $type . '_id:' . $id;
                        }
                        elseif ( $type == 'activity' ) // activity
                        {
                            $url = '/users/view/' . $item['User']['id'] . '/activity_id:' . $id;
                        }
                        else
                        {
                            $url = isset($item[key($item)]['moo_url']) ? $item[key($item)]['moo_url'] : '';
							
                            if ( $type == 'Photo_Photo' ){
                                $url .= '#content';
                            }
                        }

                        if ($this->User->checkSettingNotification($item['User']['id'],'like_item')) {
                            $notificationStopModel = MooCore::getInstance()->getModel('NotificationStop');
                            if (!$notificationStopModel->isNotificationStop($id, $type, $item['User']['id'])) {
                                $this->loadModel('Notification');
                                $this->Notification->record(array('recipients' => $item['User']['id'],
                                    'sender_id' => $uid,
                                    'action' => $action,
                                    'url' => $url,
                                    'params' => $params
                                ));
                            }
                        }
                    }
                }
            }
            else
            {
                $this->$model->updateCounter($id, 'dislike_count', array('Like.type' => $type, 'Like.target_id' => $id, 'Like.thumb_up' => 0), 'Like');

                //user like activity photo with 1 photo
                if(!empty($activity))
                    $this->updatePhotoLike($activity,$thumb_up);
            }
        }

       // $item = $this->$model->findById( $id );
        $re = array('like_count' => $this->Like->getBlockLikeCount($id,$type), 'dislike_count' => $this->Like->getBlockLikeCount($id,$type,0 ));
        
        $like_current = $this->Like->getUserLike( $id, $uid, $type );
        $like_item = $this->Like->read();
        if ($type == 'Photo_Photo')
        {
        	$this->loadModel('Activity');
        	$activity = $this->Activity->find('first',array(
        		'conditions' => array(
        			'OR'=> array(
        				array('item_type' => 'Photo_Album','action'=>'wall_post','items'=>$id),
        				array('item_type' => 'Photo_Photo', 'action' => 'photos_add','items'=>$id),
        			)
        		)
        	));
        	if ($activity)
        	{
        		$this->Like->deleteAll(array('Like.type' => 'activity','Like.user_id'=>$uid,'Like.target_id'=>$activity['Activity']['id']), false);
        		
        		if ($like_current)
        		{
        			$likeModel = MooCore::getInstance()->getModel('Like');
        			$likeModel->Behaviors->detach('Notification');
        			$likeModel->save(array(
        				'type' => 'activity',
        				'user_id' => $uid,
        				'target_id' => $activity['Activity']['id'],
        				'thumb_up' => $like_current['Like']['thumb_up']
        			));
        		}
        		
        		$this->Activity->updateCounter($activity['Activity']['id'], 'like_count', array('Like.type' => 'activity', 'Like.target_id' => $activity['Activity']['id'], 'Like.thumb_up' => 1), 'Like');
        		$this->Activity->updateCounter($activity['Activity']['id'], 'dislike_count', array('Like.type' => 'activity', 'Like.target_id' => $activity['Activity']['id'], 'Like.thumb_up' => 0), 'Like');
        	}
        }
        $cakeEvent = new CakeEvent('Controller.Like.afterLike', $this, array('aLike' => $like_item));
        $this->getEventManager()->dispatch($cakeEvent);
            if($isReponse) echo json_encode($re);
	}

	public function ajax_show($type = null, $id = null,$dislike = false,$isRedirect = true)
	{
		$id = intval($id);
		$page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
		$count = 0;
		if($dislike){
            $users = $this->Like->getDisLikes( $id, $type, RESULTS_LIMIT, $page );
            $count = $this->Like->getCountDisLikes( $id, $type);
            $this->set('dislike',1);
        }
		else
		{			
            $users = $this->Like->getLikes( $id, $type, RESULTS_LIMIT, $page );
            $count = $this->Like->getCountLikes( $id, $type);
		}
		$this->set( 'users', $users );
		$this->set('page', $page);
		$this->set('count',$count);
		if ($dislike)
		{
			$this->set('more_url', '/likes/ajax_show/' . $type . '/' . $id . '/1/page:' . ( $page + 1 ) );
		}
		else
		{
			$this->set('more_url', '/likes/ajax_show/' . $type . '/' . $id . '/page:' . ( $page + 1 ) );
		}
                $this->set('title_for_layout', __('Likes'));
                if($isRedirect) $this->render('/Elements/ajax/user_overlay_like');
	}

    public function updatePhotoLike($activity = null, $thumb = 1, $deleteLike = false) {
        $uid = $this->Auth->user('id');
        if  (!empty($activity)) {
        	$item_type = $activity['Activity']['item_type'];
        	
            if (
            	($item_type == 'Photo_Album' && $activity['Activity']['action'] == 'wall_post')
            	|| ($item_type == 'Photo_Photo' && $activity['Activity']['action'] == 'photos_add')
            ) 
            {
                $photo_id = explode(',',$activity['Activity']['items']);
                if (count($photo_id) == 1) {
                    $this->loadModel('Photo.Photo');
                    $data_like = array('type' => 'Photo_Photo', 'target_id' => $photo_id[0] , 'user_id' => $uid, 'thumb_up' => $thumb);

                    $like_id = false;
                    $like = $this->Like->findByTargetIdAndType($photo_id[0],'Photo_Photo');
                    if(!empty($like))
                        $like_id = $like['Like']['id'];

                    if ($deleteLike && $like_id) {
                        $this->Like->delete($like_id);
                    } else {
                        $this->Like->create();
                        if($like_id)
                            $this->Like->id = $like_id;
                        $this->Like->save($data_like);
                    }

                    $this->Photo->updateCounter($photo_id[0], 'like_count', array('Like.type' => 'Photo_Photo', 'Like.target_id' => $photo_id[0], 'Like.thumb_up' => 1), 'Like');
                    $this->Photo->updateCounter($photo_id[0], 'dislike_count', array('Like.type' => 'Photo_Photo', 'Like.target_id' => $photo_id[0], 'Like.thumb_up' => 0), 'Like');
                }
            }
        }
    }
}

