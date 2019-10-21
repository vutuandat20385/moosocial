<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class TopicsController extends TopicAppController {

    
    
    public $paginate = array(
        'order' => array(
            'Topic.id' => 'desc'
        ),
        'findType' => 'translated',
    );
    
    public function beforeFilter() {
        parent::beforeFilter();
        $this->loadModel('Topic.Topic');
        $this->loadModel('Group.GroupUser');
    }

    public function index($cat_id = null) {
        $this->loadModel('Tag');
        $this->loadModel('Category');

        $cat_id = intval($cat_id);
        
        

        $tags = $this->Tag->getTags('Topic_Topic', Configure::read('core.popular_interval'));
        $more_result = 0;
        if (!empty($cat_id)){
            $topics = $this->Topic->getTopics('category', $cat_id);
            $more_topics = $this->Topic->getTopics('category', $cat_id,2);
        }else{
            $topics = $this->Topic->getTopics();
            $more_topics = $this->Topic->getTopics(null,null,2);
        }
        if(!empty($more_topics)){
            $more_result = 1;
        }

        $uid = $this->Auth->user('id');
        if (!empty($uid)) {
            $this->loadModel('Like');
            $like = $this->Like->getUserLikeByType($uid, 'Topic_Topic');
            $this->set('like', $like);
        }
        
        $this->set('tags', $tags);
        $this->set('topics', $topics);
        $this->set('cat_id', $cat_id);
        $this->set('title_for_layout', '');
        $this->set('more_result', $more_result);
    }

    /*
     * Browse albums based on $type
     * @param string $type - possible value: cats, my, home, friends
     * @param mixed $param - could be catid (category), uid (user) or a query string (search)
     */

    public function browse($type = null, $param = null,$isRedirect = true) {
        
            if($isRedirect) {
                    $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            }
            else {
                $page = $this->request->query('page') ? $this->request->query('page') : 1;
            }
            $uid = $this->Auth->user('id');

            if (!empty($this->request->named['category_id'])) {
                $type = 'category';
                $param = $this->request->named['category_id'];
            }

            $url = (!empty($param)) ? $type . '/' . $param : $type;

            switch ($type) {
                case 'home':
                case 'my':
                case 'friends':
                    $this->_checkPermission();
                    $param = $uid;
                    break;

                case 'search':
                    $param = urldecode($param);

                    if (!Configure::read('core.guest_search') && empty($uid))
                        $this->_checkPermission();

                    break;

                case 'group':
                    // check permission if group is private
                    $this->loadModel('Group.Group');
                    $group = $this->Group->findById($param);

                    $this->loadModel('Group.GroupUser');
                    $is_member = $this->GroupUser->isMember($uid, $param);

                    if ($group['Group']['type'] == PRIVACY_PRIVATE) {
                        $cuser = $this->_getUser();        
                        
                        if (!$cuser['Role']['is_admin'] && !$is_member)
                        {
                            if($isRedirect) {
                                $this->autoRender = false;
                                echo 'Only group members can view topics';
                                return;
                            }
                            else {
                                $this->throwErrorCodeException('not_group_member');
                                return $error = array(
                                    'code' => 400,
                                    'message' => __('Only group members can view topics'),
                                );
                            }
                        }
                    }

                    $this->set('is_member', $is_member);
                    $this->set('ajax_view', true);
                    $this->set('groupname', $group['Group']['name']);

                    break;

                default:
                    if (!empty($param))
                        $this->Session->write('cat_id', $param);
            }

            $topics = $this->Topic->getTopics($type, $param, $page);
            $more_result = 0;
            $more_topics = $this->Topic->getTopics($type, $param, $page +1);
            if(!empty($more_topics))
                $more_result = 1;

            foreach ($topics as $key => $topic){
                $admins = $this->GroupUser->getUsersList($topic['Topic']['group_id'], GROUP_USER_ADMIN);
                $topics[$key]['Topic']['admins'] = $admins;
            }

            if (!empty($uid)) {
                $this->loadModel('Like');
                $like = $this->Like->getUserLikeByType($uid, 'Topic_Topic');
                $this->set('like', $like);
            }

            $this->set('topics', $topics);
            $this->set('title_for_layout', __( 'Topics'));
            $this->set('more_url', '/topics/browse/' . h($url) . '/page:' . ($page + 1));
            $this->set('page', $page);
            $this->set('group_id', $param);
            $this->set('more_result',$more_result);
            $data = array (
                'topics' => $topics,
                'more_url' => '/topics/browse/' . h($url) . '/page:' . ($page + 1),
                'page' => $page,
                'type' => $type,
            );
            if($type == 'category') $this->set('categoryId', $param);
            $this->set('data', $data);
        if($isRedirect && $this->theme != "mooApp") {
            if ($page == 1 && $type == 'home'){
                $this->render('/Elements/ajax/home_topic');
            }
            else if ($page == 1 && $type == 'group'){
                $this->render('/Elements/ajax/group_topic');
            }
            else{
                if ($this->request->is('ajax')){
                    $this->render('/Elements/lists/topics_list');
                }
                else{
                    $this->render('/Elements/lists/topics_list_m');
                }
            }
        }
    }

    /*
     * Show add/edit topic form
     * @param int $id - topic id to edit
     */

    public function create($id = null) {
        $id = intval($id);
        $this->_checkPermission(array('confirm' => true));
        $this->_checkPermission(array('aco' => 'topic_create'));

        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();

        $cats = $this->Category->getCategoriesList('Topic', $role_id);
        $attachments_list = array();
        
        if (!empty($id)) { // editing
            $topic = $this->Topic->findById($id);
            $this->_checkExistence($topic);
            $this->_checkPermission(array('admins' => array($topic['User']['id'])));

            // if it's a group topic, redirect to group view
            if($this->theme != "mooApp"){ // do not redirect to group view when use app
                if (!empty($topic['Topic']['group_id'])) {
                    $this->redirect('/groups/view/' . $topic['Topic']['group_id'] . '/topic_id:' . $id. '/edit:1');
                    exit;
                }
            }

            $this->loadModel('Tag');
            $tags = $this->Tag->getContentTags($id, 'Topic_Topic');

            $this->loadModel('Attachment');
            $attachments = $this->Attachment->find('all', array('conditions' => array('plugin_id' => PLUGIN_TOPIC_ID, 'target_id' => $id)));

            foreach ($attachments as $a)
                $attachments_list[] = $a['Attachment']['id'];

            $this->set('tags', $tags);
            $this->set('attachments', $attachments);
            $this->set('title_for_layout', __( 'Edit Topic'));
        } else {
            $topic = $this->Topic->initFields();

            if ($this->Session->check('cat_id')) {
                $topic['Topic']['category_id'] = $this->Session->read('cat_id');
                $this->Session->delete('cat_id');
            }

            $this->set('title_for_layout', __( 'Create New Topic'));
        }

        $this->set('topic', $topic);
        $this->set('cats', $cats);
        $this->set('attachments_list', implode(',', $attachments_list));
    }

    /*
     * Show add/edit group topic form
     * @param int $id - topic id to edit
     */

    public function group_create($id = null) {
        $id = intval($id);
        $this->_checkPermission(array('confirm' => true));
        $this->_checkPermission(array('aco' => 'topic_create'));

        $attachments_list = array();

        if (!empty($id)) { // editing
            $topic = $this->Topic->findById($id);
            $this->_checkExistence($topic);

            $this->loadModel('Attachment');
            $attachments = $this->Attachment->find('all', array('conditions' => array('plugin_id' => PLUGIN_TOPIC_ID, 'target_id' => $id)));

            foreach ($attachments as $a)
                $attachments_list[] = $a['Attachment']['id'];

            $this->set('attachments', $attachments);
        } else {
            $topic = $this->Topic->initFields();
            if ($this->isApp()) {
                if(isset($this->request->named['group_id'])) $topic['Topic']['group_id'] = $this->request->named['group_id'];
            }
            else {
                $topic['Topic']['group_id'] = $this->request->data['group_id'];
            }
        }

        $this->set('group_id', $topic['Topic']['group_id']);
        $this->set('topic', $topic);
        $this->set('attachments_list', implode(',', $attachments_list));
    }

    /*
     * Save add/edit form
     */

    public function save($isReturn = false) {
        $this->_checkPermission(array('confirm' => true));
        $this->autoRender = false;
        $uid = $this->Auth->user('id');
        $this->request->data['attachment'] = (!empty($this->request->data['attachments'])) ? 1 : 0;

        if (!empty($this->request->data['id'])) { // edit topic
            // check edit permission
            $topic = $this->Topic->findById($this->request->data['id']);
            $this->_checkTopic($topic, true);

            $this->Topic->id = $this->request->data['id'];
        } else {
            // if it's a group topic, check if user has permission to create topic in this group
            if (!empty($this->request->data['group_id'])) {
                $this->loadModel('Group.GroupUser');

                if (!$this->GroupUser->isMember($uid, $this->request->data['group_id'])) {
                    if(!$isReturn) {
                        return;
                    }
                    else {
                        $this->throwErrorCodeException('not_group_member');
                        return $error = array(
                            'code' => 400,
                            'message' => __("You are not member of this group."),
                        );
                    }
                }
                    
            }

            $this->request->data['user_id'] = $uid;
            $this->request->data['lastposter_id'] = $uid;
            $this->request->data['last_post'] = date('Y-m-d H:i:s');
        }

        $this->request->data['body'] = str_replace('../', '/', $this->request->data['body']);

        $this->Topic->set($this->request->data);
        $this->_validateData($this->Topic);

        // todo: check if user has permission to post in category

        if ($this->Topic->save()) {
            if (empty($this->request->data['id'])) { // add topic
                $type = APP_USER;
                $target_id = 0;
                $privacy = PRIVACY_EVERYONE;
                
                // Todo: refactor on group plugin
                if (!empty($this->request->data['group_id'])) {
                    $type = 'Group_Group';
                    $target_id = $this->request->data['group_id'];

                    $this->loadModel('Group.Group');
                    $group = $this->Group->findById($this->request->data['group_id']);

                    if ($group['Group']['type'] == PRIVACY_PRIVATE)
                        $privacy = PRIVACY_ME;
                    
                    Cache::delete('group_detail_' . $target_id, 'group');
                }
                $this->loadModel('Activity');
                $this->Activity->save(array('type' => $type,
                        'target_id' =>$target_id,
                        'action' => 'topic_create',
                        'user_id' => $uid,                       
                        'item_type' => 'Topic_Topic',
                        'privacy' => $privacy,
                		'item_id' => $this->Topic->id,
                        'query' => 1,
                    	'params' => 'item',
    					'plugin' => 'Topic'
                 ));
            }
            $event = new CakeEvent('Plugin.Controller.Topic.afterSaveTopic', $this, array(
                'uid' => $uid, 
                'id' => $this->Topic->id, 
               
             ));

            $this->getEventManager()->dispatch($event);
            
            // update Topic item_id for photo thumbnail
            if (!empty($this->request->data['topic_photo_ids'])) {
            	$photos = explode(',', $this->request->data['topic_photo_ids']);
            	if (count($photos))
            	{
		            $this->loadModel('Photo.Photo');
		            // Hacking for cdn
		            $result = $this->Photo->find("all",array(
		                'recursive'=>1,
		                'conditions' =>array(
		                    'Photo.type' => 'Topic',
		                    'Photo.user_id' => $uid,
		                	'Photo.id' => $photos
		                )));
		            if($result){
		                $view = new View($this);
		                $mooHelper = $view->loadHelper('Moo');
		                foreach ($result as $iPhoto){
		                    $iPhoto["Photo"]['moo_thumb'] = 'thumbnail';
		                    $mooHelper->getImageUrl($iPhoto, array('prefix' => '450'));
		                    $mooHelper->getImageUrl($iPhoto, array('prefix' => '1500'));
		                }
		                // End hacking
		                $this->Photo->updateAll(array('Photo.target_id' => $this->Topic->id), array(
		                		'Photo.type' => 'Topic',
		                		'Photo.user_id' => $uid,
		                		'Photo.id' => $photos
		                ));
		            }
		            
            	}
	        }
            
            $this->loadModel('Tag');
            $this->Tag->saveTags($this->request->data['tags'], $this->Topic->id, 'Topic_Topic');

            if (!empty($this->request->data['attachments'])) {
                $this->loadModel('Attachment');
                $this->Attachment->updateAll(array('Attachment.target_id' => $this->Topic->id), array('Attachment.id' => explode(',', $this->request->data['attachments'])));
            }
            if(!$isReturn) {
                $response['result'] = 1;
                $response['id'] = $this->Topic->id;
                echo json_encode($response);
            }
            else {
                return $this->Topic->id;
            }
        }
    }

    public function view($id = null) {
        $id = intval($id);
        
        $this->Topic->recursive = 2;
        $topic= $this->Topic->findById($id);
        if ($topic['Category']['id'])
        {
        	foreach ($topic['Category']['nameTranslation'] as $translate)
        	{
        		if ($translate['locale'] == Configure::read('Config.language'))
        		{
        			$topic['Category']['name'] = $translate['content'];
        			break;
        		}
        	}
        }
        $this->Topic->recursive = 0;
        
        $this->_checkExistence($topic);
        $this->_checkPermission(array('aco' => 'topic_view'));
        $this->_checkPermission( array('user_block' => $topic['Topic']['user_id']) );
        
        $uid = $this->Auth->user('id');
        // if it's a group topic, redirect to group view
        /*if (!empty($topic['Topic']['group_id'])) {
            $this->redirect('/groups/view/' . $topic['Topic']['group_id'] . '/topic_id:' . $id);
            exit;
        }*/

        $this->_getTopicDetail($topic);
        
            $this->loadModel('Tag');
            $tags = $this->Tag->getContentTags($id, 'Topic_Topic');

            $areFriends = false;
            if (!empty($uid)) { //  check if user is a friend
                $this->loadModel('Friend');
                $areFriends = $this->Friend->areFriends($uid, $topic['User']['id']);
            }
            MooCore::getInstance()->setSubject($topic);
            $likes = $this->Like->getLikes($id, 'Topic_Topic');
            $dislikes = $this->Like->getDisLikes($id, 'Topic_Topic');

            $this->loadModel('NotificationStop');
            $notification_stop = $this->NotificationStop->find('count', array('conditions' => array('item_type' => 'topic',
                            'item_id' => $id,
                            'user_id' => $uid)
                            ));
            $this->set('notification_stop', $notification_stop);

            $this->set('areFriends', $areFriends);
            $this->set('tags', $tags);
            $this->set('likes', $likes);
            $this->set('dislikes', $dislikes);

            $this->set('title_for_layout', $topic['Topic']['title']);
            $description = $this->getDescriptionForMeta($topic['Topic']['body']);
            if ($description) {
                $this->set('description_for_layout', $description);
                if (count($tags))
                {
                    $tags = implode(",", $tags).' ';
                }
                else
                {
                    $tags = '';
                }
                $this->set('mooPageKeyword', $this->getKeywordsForMeta($tags.$description));
            }

            // set og:image
            if ($topic['Topic']['thumbnail']) {
                $mooHelper = MooCore::getInstance()->getHelper('Core_Moo');
                $this->set('og_image', $mooHelper->getImageUrl($topic, array('prefix' => '850')));

            }

            if (!empty($topic['Topic']['group_id'])) {
                    $this->loadModel("Group.Group");
                    $group = $this->Group->findById($topic['Topic']['group_id']);
                    $this->set('group',$group);
            }
        
        if($this->theme == "mooApp"){ 
            $this->set('topicId',$id);
            $this->set('title_for_layout', $topic['Topic']['title']);
        }
        
    }

    public function ajax_view($id = null) {
        $id = intval($id);
        $topic = $this->Topic->findById($id);
        $this->_checkExistence($topic);
        $this->_checkPermission(array('aco' => 'topic_view'));
        $this->_checkPermission( array('user_block' => $topic['Topic']['user_id']) );   
        $this->loadModel('Like');
        $likes = $this->Like->getLikes($id, 'Topic_Topic');
        $this->set('likes', $likes);

        //close comment
        $closeCommentModel =  MooCore::getInstance()->getModel('CloseComment');
        $item_close_comment = $closeCommentModel->getCloseComment($topic['Topic']['id'], $topic['Topic']['moo_type']);
        $this->set('item_close_comment', $item_close_comment);

        $this->_getTopicDetail($topic);
    }
    
    public function profile_user_topic($uid = null,$isRedirect=true){
        $uid = intval($uid);
        $this->loadModel('Topic.Topic');
        if($isRedirect) {
                    $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            }
            else {
                $page = $this->request->query('page') ? $this->request->query('page') : 1;
            }

        $topics = $this->Topic->getTopics( 'user', $uid, $page );
        $more_result = 0;
        $more_topics = $this->Topic->getTopics( 'user', $uid, $page  + 1);
        if(!empty($more_topics))
            $more_result = 1;
        $this->set('topics', $topics);
        $this->set('more_url', '/topics/profile_user_topic/' . $uid . '/page:' . ( $page + 1 ));
        $this->set('user_id', $uid);
        $this->set('more_result', $more_result);
        if($isRedirect && $this->theme != "mooApp") {
            if ($page > 1)
                $this->render('/Elements/lists/topics_list');
            else
                $this->render('Topic.Topics/profile_user_topic');
        }
    }

    private function _getTopicDetail($topic) {
        $uid = $this->Auth->user('id');
        $data = array () ;
        // if topic belongs to a group, check permission
        if (!empty($topic['Topic']['group_id'])) {
            $this->loadModel('Group.GroupUser');
            
            $is_member = $this->GroupUser->isMember($uid, $topic['Topic']['group_id']);
            $this->set('is_member', $is_member);

            if ($topic['Group']['type'] == PRIVACY_PRIVATE) {
                $cuser = $this->_getUser();

                if (!$cuser['Role']['is_admin'] && !$is_member) {
                    $this->Session->setFlash(__( "This is a private group topic and can only be viewed by the group's members"), 'default', array('class' => 'error-message'));
                    $this->redirect('/pages/no-permission');

                    exit;
                }
            }

            $admins = $this->GroupUser->getUsersList($topic['Topic']['group_id'], GROUP_USER_ADMIN);
            $this->set('admins', $admins);
                        
            $data['admins'] = $admins ;
        }

        $this->loadModel('Like');
        $this->loadModel('Comment');

        $cond= array();
        if (!empty( $this->request->named['comment_id'] )) {
            $cond['Comment.id'] = $this->request->named['comment_id'];
            $data['cmt_id'] = $this->request->named['comment_id'];
            $data['subject'] = $topic;
        }

        $comments = $this->Comment->getComments($topic['Topic']['id'], 'Topic_Topic', 1, $cond);

        if(!empty( $this->request->named['reply_id']) && !empty($comments[0])){
            $reply = $this->Comment->find('all', array(
                'conditions' => array(
                    'Comment.id' => $this->request->named['reply_id'],
                )
            ));
            $replies_count = $this->Comment->getCommentsCount( $comments[0]['Comment']['id'], 'comment' );
            $comment_likes = $this->Like->getCommentLikes($reply, $this->Auth->user('id') );

            $comments[0]['Replies'] = $reply;
            $comments[0]['RepliesIsLoadMore'] = ($replies_count - 1) > 0 ? true : false;
            $comments[0]['RepliesCommentLikes'] = $comment_likes;
        }
        
        // get comment likes
        if (!empty($uid)) {
            $comment_likes = $this->Like->getCommentLikes($comments, $uid);
            $this->set('comment_likes', $comment_likes);
            $data['comment_likes'] = $comment_likes ;
            $like = $this->Like->getUserLike($topic['Topic']['id'], $uid, 'Topic_Topic');
            $this->set('like', $like);
        }

        $this->loadModel('Attachment');
        $attachments = $this->Attachment->getAttachments(PLUGIN_TOPIC_ID, $topic['Topic']['id']);

        $files = array();
        $pictures = array();

        foreach ($attachments as $a)
            if (in_array(strtolower($a['Attachment']['extension']), array('jpg', 'jpeg', 'png', 'gif')))
                $pictures[] = $a;
            else
                $files[] = $a;
            
        $page = 1 ;
        $this->set('files', $files);
        $this->set('pictures', $pictures);
     
        $this->set('topic', $topic);
        $data['comments'] = $comments ;
        $data['bIsCommentloadMore'] = $topic['Topic']['comment_count'] - $page*RESULTS_LIMIT ;
        $data['more_comments'] = '/comments/browse/Topic_Topic/' . $topic['Topic']['id'] . '/page:' . ($page + 1) ;
       
        $this->set('data', $data);
    }

    /*
     * Delete topic
     * @param int $id - topic id to delete
     */

    public function do_delete($id = null,$isRedirect = true) {
        $id = intval($id);
        $topic = $this->Topic->findById($id);
        $this->ajax_delete($id);
        if($isRedirect) {
            $this->Session->setFlash(__( 'Topic has been deleted'));
            if ($topic['Topic']['group_id'])
            {
            	if (!$this->isApp())
            	{
                    $this->redirect('/groups/view/'.$topic['Topic']['group_id'].'/tab:topics');
                    return;
            	}
            	$this->autoRender = true;
            	return;
            }
            $this->redirect('/topics');
        }
    }

    public function ajax_delete($id = null) {
        $id = intval($id);
        $this->autoRender = false;

        $topic = $this->Topic->findById($id);
        $this->_checkTopic($topic, true);

        $this->Topic->deleteTopic($topic);
        //$this->Topic->deleteTopic($topic);
        $cakeEvent = new CakeEvent('Plugin.Controller.Topic.afterDeleteTopic', $this, array('item' => $topic));
        $this->getEventManager()->dispatch($cakeEvent);
    }

    public function do_pin($id = null,$isRedirect = true) {
        $id = intval($id);
        $topic = $this->Topic->findById($id);
        $this->_checkTopic($topic);

        $this->Topic->id = $id;
        $this->Topic->save(array('pinned' => 1));
        
        // event
        $cakeEvent = new CakeEvent('Plugin.Controller.Topic.afterPin', $this, array('item' => $topic));
        $this->getEventManager()->dispatch($cakeEvent);
        if($isRedirect) {
            $this->Session->setFlash(__( 'Topic has been pinned'));

            if (!empty($topic['Topic']['group_id']))
                $this->redirect('/groups/view/' . $topic['Topic']['group_id'] . '/topic_id:' . $id);
            else
                $this->redirect('/topics/view/' . $id);
        }
    }

    public function do_unpin($id = null,$isRedirect = true) {
        $id = intval($id);
        $topic = $this->Topic->findById($id);
        $this->_checkTopic($topic);

        $this->Topic->id = $id;
        $this->Topic->save(array('pinned' => 0));
        
        // event
        $cakeEvent = new CakeEvent('Plugin.Controller.Topic.afterUnPin', $this, array('item' => $topic));
        $this->getEventManager()->dispatch($cakeEvent);
        if($isRedirect) {
            $this->Session->setFlash(__( 'Topic has been unpinned'));

            if (!empty($topic['Topic']['group_id']))
                $this->redirect('/groups/view/' . $topic['Topic']['group_id'] . '/topic_id:' . $id);
            else
                $this->redirect('/topics/view/' . $id);
        }
    }

    public function do_lock($id = null,$isRedirect = true) {
        $id = intval($id);
        $topic = $this->Topic->findById($id);
        $this->_checkTopic($topic);

        $this->Topic->id = $id;
        $this->Topic->save(array('locked' => 1));
        
        // event
        $cakeEvent = new CakeEvent('Plugin.Controller.Topic.afterLock', $this, array('item' => $topic));
        $this->getEventManager()->dispatch($cakeEvent);
        if($isRedirect) {
            $this->Session->setFlash(__( 'Topic has been locked'));

            if (!empty($topic['Topic']['group_id']))
                $this->redirect('/groups/view/' . $topic['Topic']['group_id'] . '/topic_id:' . $id);
            else
                $this->redirect('/topics/view/' . $id);
        }
    }

    public function do_unlock($id = null,$isRedirect = true) {
        $id = intval($id);
        $topic = $this->Topic->findById($id);
        $this->_checkTopic($topic);

        $this->Topic->id = $id;
        $this->Topic->save(array('locked' => 0));
        if($isRedirect) {
            $this->Session->setFlash(__( 'Topic has been unlocked'));

            if (!empty($topic['Topic']['group_id']))
                $this->redirect('/groups/view/' . $topic['Topic']['group_id'] . '/topic_id:' . $id);
            else
                $this->redirect('/topics/view/' . $id);
        }
    }

    private function _checkTopic($topic, $allow_author = false) {
        $this->_checkExistence($topic);
        $admins = array();

        if ($allow_author)
            $admins = array($topic['User']['id']); // topic creator

            
// if it's a group topic then group admins can do it
        if (!empty($topic['Topic']['group_id'])) {
            $this->loadModel('Group.GroupUser');

            $group_admins = $this->GroupUser->getUsersList($topic['Topic']['group_id'], GROUP_USER_ADMIN);
            $admins = array_merge($admins, $group_admins);
        }

        $this->_checkPermission(array('admins' => $admins));
    }

    public function admin_index() {
        if (!empty($this->request->data['keyword']))
            $this->redirect('/admin/topics/index/keyword:' . $this->request->data['keyword']);

        $cond = array();
        if (!empty($this->request->named['keyword']))
            $cond['MATCH(Topic.title) AGAINST(? IN BOOLEAN MODE)'] = $this->request->named['keyword'];

        $topics = $this->paginate('Topic', $cond);

        $this->loadModel('Category');
        $categories = $this->Category->getCategoriesListItem('Topic');

        $this->set('topics', $topics);
        $this->set('categories', $categories);
        $this->set('title_for_layout', 'Topics Manager');
    }

    public function admin_move() {
        if (!empty($_POST['topics']) && !empty($this->request->data['category'])) {
            foreach ($_POST['topics'] as $topic_id) {
                $this->Topic->id = $topic_id;
                $this->Topic->save(array('category_id' => $this->request->data['category']));
            }

            $this->Session->setFlash(__('Topic has been moved'));
        }

        $this->redirect($this->referer());
    }

    public function popular() {
        if ($this->request->is('requested')) {
            $num_item_show = $this->request->named['num_item_show'];
            return $this->Topic->getPopularTopics($num_item_show, Configure::read('core.popular_interval'));
        }
    }
    
    public function categories_list($isRedirect = true){
        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();
        $categories = $this->Category->getCategories('Topic', $role_id);
        if ($this->request->is('requested')) {
            return $categories;
        }
        if($isRedirect && $this->theme == "mooApp") {
            $this->render('/Elements/lists/categories_list');
        }
    }

}
