<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class UserBlocksController extends AppController {

    public function ajax_add($id = null) {
    	$this->set('title_for_layout',__('Block user'));
        $id = intval($id);
        $this->_checkPermission();
        $uid = $this->Auth->user('id');
        $warning_msg = '';
        if ($uid == $id) {
            $warning_msg = __('You cannot block to yourself');
        }

        // check if users are already friends
        if ($this->UserBlock->areUserBlocks($uid, $id)) {
            $warning_msg = __('You already block this user');
        }

        // nothing? display the form
        $this->loadModel('User');
        $user = $this->User->findById($id);
        $this->set('user', $user);
        $this->set('warning_msg', $warning_msg);
    }

    public function ajax_do_add($isEcho=true) {
        $this->autoRender = false;
        $this->_checkPermission();
        $requestdata = $this->request->data;
        $id = $requestdata['user_id'];
        $uid = $this->Auth->user('id');
        if ($uid == $id) {
            if($isEcho) {
                echo __('You cannot block to yourself');
                return;
            }
            else {
                return $error = array(
                    'code' => 400,
                    'message' => __('You cannot block to yourself'),
                );
            }
        }

        $this->loadModel('UserBlock');

        if ($this->UserBlock->areUserBlocks($uid, $id)) {
            if($isEcho) {
                echo __('You already block this user');
                return;
            }
            else {
                return $error = array(
                    'code' => 400,
                    'message' => __('You already block this user'),
                );
            }
        }

        $this->UserBlock->create();
        $this->UserBlock->save(array('user_id' => $uid, 'object_id' => $id));

        // remove friend
        $this->loadModel('Friend');
        $this->Friend->deleteAll(array('Friend.user_id' => $uid, 'Friend.friend_id' => $id), true, true);
        $this->Friend->deleteAll(array('Friend.user_id' => $id, 'Friend.friend_id' => $uid), true, true);

        $this->loadModel('Activity');           
        $activities = $this->Activity->find('all', array('conditions' => array(
                'OR' => array(
                    array(
                        'Activity.action' => 'friend_add',
                        'Activity.user_id' => $uid,
                    ),
                    array(
                        'Activity.action' => 'friend_add',
                        'Activity.user_id' => $id,
                    )
                ),
        )));
        foreach ($activities as $item) {
            $friendsid = explode(',', $item['Activity']['items']);

            if ($item['Activity']['user_id'] == $uid) {
                if (($key = array_search($id, $friendsid)) !== false) {
                    unset($friendsid[$key]);
                }
            } else {
                if (($key = array_search($uid, $friendsid)) !== false) {
                    unset($friendsid[$key]);
                }
            }

            if (empty($friendsid)) { // delete
                $this->Activity->delete($item['Activity']['id']);
            } else { // update
                $this->Activity->id = $item['Activity']['id'];
                $this->Activity->set(array(
                    'items' => implode(',', $friendsid),
                    'modified' => false
                ));
                $this->Activity->save();
            }
        }
        if($isEcho) {
            $this->Session->setFlash(__('The user has been blocked'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
        }
        // Unfollow
        $this->loadModel('UserFollow');
        $this->UserFollow->deleteAll(array('UserFollow.user_id' => $uid, 'UserFollow.user_follow_id' => $id), true, true);
        $this->UserFollow->deleteAll(array('UserFollow.user_id' => $id, 'UserFollow.user_follow_id' => $uid), true, true);
        
        $event = new CakeEvent('UserBlocksController.afterBlock', $this, array('user_id' => $uid, 'object_id' => $id));
        $this->getEventManager()->dispatch($event);
        $this->clearCaches();               
    }

    public function ajax_remove($id = null) {
        $id = intval($id);
        $this->_checkPermission();
        $this->_checkPermission(array('confirm' => true));
        $uid = $this->Auth->user('id');

        if (!$this->UserBlock->areUserBlocks($uid, $id)) {
                $this->autoRender = false;
                echo __('This user is not blocked by you');
                return;
        }

        // nothing? display the form
        $this->loadModel('User');
        $user = $this->User->findById($id);
        $this->set('user', $user);   
    }

    public function ajax_do_remove($isEcho = true) {
        $this->autoRender = false;
        $this->_checkPermission();
        $requestdata = $this->request->data;
        $id = $requestdata['user_id'];
        $uid = $this->Auth->user('id');
        if ($uid == $id) {
            if($isEcho) {
                echo __('You cannot unblock to yourself');
                return;
            }
            else {
                return $error = array(
                    'code' => 400,
                    'message' => __('You cannot unblock to yourself'),
                );
            }
        }

        $this->loadModel('UserBlock');

        if (!$this->UserBlock->areUserBlocks($uid, $id)) {
            if($isEcho) {
                echo __('You already unblock this user');
                return;
            }
        }

        $this->UserBlock->deleteAll(array('UserBlock.user_id' => $uid, 'UserBlock.object_id' => $id), true, true);
        $this->clearCaches();
    }


    protected function clearCaches(){
        $this->autoRender = false;
        Cache::clearGroup('photo');
        Cache::clearGroup('blog');
        Cache::clearGroup('video');
        Cache::clearGroup('topic');
        Cache::clearGroup('group');
        Cache::clearGroup('event');
    }
}
