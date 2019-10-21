<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class FollowsController extends AppController
{
    public function ajax_update_follow($isRedirect=true)
    {
        $this->_checkPermission(array('confirm' => true));
        $uid = MooCore::getInstance()->getViewer(true);
        $user_id = $this->request->data['user_id'];

        $this->loadModel("UserFollow");

        $follow = $this->UserFollow->checkFollow($uid,$user_id);

        if ($follow)
        {
            $this->UserFollow->deleteAll(array('UserFollow.user_id' => $uid, 'UserFollow.user_follow_id' => $user_id));
            //activitylog event
            $cakeEvent = new CakeEvent('Controller.Follow.afterUnfollow', $this, array('follow' => $follow));
            $this->getEventManager()->dispatch($cakeEvent);
        }
        else
        {
            $this->UserFollow->save(array(
                'user_id' => $uid,
                'user_follow_id' => $user_id
            ));
            // activitylog event
            $cakeEvent = new CakeEvent('Controller.Follow.afterFollow', $this, array('follow' => $this->UserFollow->read()));
            $this->getEventManager()->dispatch($cakeEvent);
        }
        if($isRedirect) die();
    }

    public function user_follows($isRedirect = true)
    {
        $this->_checkPermission(array('confirm' => true));
        $uid = MooCore::getInstance()->getViewer(true);

        if($isRedirect) {
            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
        }
        else {
            $page = $this->request->query('page') ? $this->request->query('page') : 1;
        }
        $this->loadModel("UserFollow");
        $users = $this->UserFollow->find('all',array(
            'conditions'=>array('UserFollow.user_id'=>$uid),
            'order' => 'User.name asc',
            'limit' => RESULTS_LIMIT,
            'page' => $page
        ));

        $count_user = $this->UserFollow->find('count',array('conditions'=>array('UserFollow.user_id'=>$uid)));
        $is_view_more = (($page - 1) * RESULTS_LIMIT  + count($users)) < $count_user;
        $this->set('page',$page);
        if ($is_view_more)
            $this->set('url_more', '/follows/user_follows/page:' . ( $page + 1 ) ) ;
        $this->set('users',$users);
        $this->set('title_for_layout', __('Following'));
    }
    
    public function user_followers($user_id = 0,$isRedirect = true)
    {

    	if($isRedirect) {
    		$page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
    	}
    	else {
    		$page = $this->request->query('page') ? $this->request->query('page') : 1;
    	}
    	$this->loadModel("UserFollow");
    	$this->UserFollow->unbindModel(array('belongsTo'=>'User'));
    	$this->UserFollow->bindModel(
    		array('belongsTo' => array(
    			'User' => array(
    				'className' => 'User',
    				'foreignKey' => 'user_id'
    			)
    		)
    	));
    	$users = $this->UserFollow->find('all',array(
    			'conditions'=>array('UserFollow.user_follow_id'=>$user_id),
    			'order' => 'User.name asc',
    			'limit' => RESULTS_LIMIT,
    			'page' => $page
    	));
    	
    	$count_user = $this->UserFollow->find('count',array('conditions'=>array('UserFollow.user_follow_id'=>$user_id)));
    	$is_view_more = (($page - 1) * RESULTS_LIMIT  + count($users)) < $count_user;
    	$this->set('page',$page);
    	if ($is_view_more)
    		$this->set('url_more', '/follows/user_followers/'.$user_id.'/page:' . ( $page + 1 ) ) ;
    	$this->set('users',$users);
    	$this->set('title_for_layout', __('Followers'));
    }

    public function ajax_remove($id = null)
    {
        $id = intval($id);
        $this->_checkPermission( array( 'confirm' => true ) );
        $uid = $this->Auth->user('id');

        // check if users are not follow
        $this->loadModel("UserFollow");
        if ( !$this->UserFollow->checkFollow( $uid, $id ) )
        {
            $this->autoRender = false;
            echo __('You are not a follow of this user');
            return;
        }

        // nothing? display the form
        $this->loadModel( 'User' );
        $user = $this->User->findById($id);
        $this->set('user', $user);
    }

    public function ajax_removeRequest()
    {
        $this->_checkPermission( array( 'confirm' => true ) );
        $uid = $this->Auth->user('id');
        $user_id = $this->request->data['user_id'];

        $this->loadModel("UserFollow");

        $this->UserFollow->deleteAll(array('UserFollow.user_id' => $uid, 'UserFollow.user_follow_id' => $user_id));
        echo json_encode(array('status'=>1));
        die();
    }
}

