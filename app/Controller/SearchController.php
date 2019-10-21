<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class SearchController extends AppController {


    public function index($keyword = null, $plugin = null) {
    	if (!$keyword)
    	{
    		$keyword = $this->request->query('q') ? $this->request->query('q') : null;
    	}
        if (!isset($keyword)) {
            $this->redirect('/');
        } else {
            $keyword = urldecode($keyword);

            if ($plugin != null) { //result by filter
                $this->keyword = $keyword;
                $this->plugin = $plugin;
                $results = $this->getEventManager()->dispatch(new CakeEvent("Controller.Search.search", $this));
            } else { //all results
                $uid = $this->Auth->user('id');

                if (!Configure::read('core.guest_search') && empty($uid))
                    $this->_checkPermission();

                $this->loadModel('Friend');
                $this->loadModel('FriendRequest');                
                
                $friends = $this->Friend->getFriends($uid);
                $friends_request = $this->FriendRequest->getRequestsList($uid);


                $this->loadModel('User');
                $params = array('User.active' => 1,
                    'User.name LIKE ' => '%'.$keyword.'%'
                );

                $users = $this->User->getUsers(1, $params, 5);
                if (empty($users)) {
                    $users = $this->Friend->searchFriends($uid, $keyword);

                    unset($users['Friends']);
                    if (empty($users))
                        $users = $this->User->getAllUser($keyword, array(), 5, 1);
                    else {
                        $idList = Hash::extract($users, '{n}.User.id');
                        $users = array_merge($users, $this->User->getAllUser($keyword, $idList));
                    }
                }
                if(count($users) > 5)
                    $users = array_slice($users,0,5);
                $this->set('users', $users);
                $this->set('keyword', $keyword);
                $this->set('title_for_layout', $keyword);
                $this->set('friends', $friends);
                $this->set('friends_request', $friends_request);
                
                $this->set('is_search',true);
                $this->loadModel("Activity");
                $activities = $this->Activity->getActivitySearch($uid,$keyword,4);
                $this->set('activities',$activities);

                //other plugins
                $this->keyword = $keyword;
                $searches = $this->getEventManager()->dispatch(new CakeEvent('Controller.Search.search', $this));
                
                $this->set('searches', $searches->result);
            }
        }
    }

    //search suggestion
    public function suggestion($type = 'all', $searchValue = null) {
        $this->loadModel('User');
        if (!empty($type)) {
        	
        	if (!$searchValue)
        	{
        		$searchValue = $this->request->query('q') ? $this->request->query('q') : null;
        	}
        	
            if ($searchValue === null)
                $searchVal = isset($this->request->data['searchVal']) ? $this->request->data['searchVal'] : '';
            else
                $searchVal = $searchValue;
            $uid = $this->Auth->user('id');

            if ($type != 'all') { //result by filter
                $event = new CakeEvent('Controller.Search.suggestion', $this, array('type' => $type, 'searchVal' => $searchVal));
                $result = $this->getEventManager()->dispatch($event);
                $this->set('other_header', $result->result);
            } else { //all result
                $event = new CakeEvent('Controller.Search.suggestion', $this, array('type' => 'all', 'searchVal' => $searchVal));
                $result = $this->getEventManager()->dispatch($event);
                $this->set('other_suggestion', $result->result);
            }
            if ($type == 'all' || $type == 'user') {
                $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;

                $this->loadModel('Friend');
                $params = array('User.active' => 1,
                	'User.name LIKE ' => '%'.$searchVal.'%'
                );
                $users = $this->User->getUsers($page, $params);
                $more_result = $this->User->getUsers($page + 1, $params);
            }
            
            if ( $type == 'activity') {
            	$page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            	
            	$this->loadModel("Activity");
            	$activites = $this->Activity->getActivitySearch($uid,$searchVal,RESULTS_LIMIT,$page);            	
            	$more_result= $this->Activity->getActivitySearch($uid,$searchVal,RESULTS_LIMIT,$page+1);
            }

            if ($type != 'all') {
                if ($type == 'user') {
                    $this->set('users', $users);
                    $this->set('more_result', $more_result);
                    $this->set('result', 1);
                    $more_url = isset($this->params['pass'][1]) ? '/search/suggestion/user/' . $this->params['pass'][1] . '/page:' . ( $page + 1 ) : "";
                    $this->set('more_url', $more_url);                    
                    $this->set('element_list_path', "lists/users_list");
                    $this->set('page',$page);
                }
                if ($type == 'activity') {
                	$this->set('activities', $activites);
                	$this->set('more_result', $more_result);
                	$this->set('bIsACtivityloadMore', count($more_result) ? 1 : 0);
                	$this->set('result', 1);
                	$this->set('is_activity',1);
                	$this->set('is_search',true);
                	$more_url = isset($this->params['pass'][1]) ? '/search/suggestion/activity/' . $this->params['pass'][1] . '/page:' . ( $page + 1 ) : "";
                	$this->set('more_url', $more_url);
                	$this->set('page',$page);
                }
                $this->set('keyword', $searchValue);
                if (!empty($this->request->named['page']) && $this->request->named['page'] > 1) {
                    $this->set('more_link', true);
                }

                $this->set('type', $type);
                $this->render('/Search/suggestion_filter');
            } else {
                if (count($users) > 2) {
                    $users = array_slice($users, 0, 2);
                }
                $this->set('users', $users);
            }
            $this->set('searchVal', $searchVal);
        }
    }

    public function hashtags($keyword = null,$type = 'all', $filter = null){
        $this->loadModel('Activity');
        $this->loadModel('ActivityComment');
        $this->loadModel('Comment');
        $this->loadModel('Hashtag');
        //$keyword = strtolower($keyword);
        $keyword1 = mb_strtolower($keyword);
        if (!empty($type))
        {
            $search_keyword = str_replace('_','',$keyword1);
            $uid = MooCore::getInstance()->getViewer(true);
            $items = $this->Hashtag->find('all',array(
                'conditions' => array(
                	'hashtags LIKE' => '%'.$search_keyword.'%'
                )
            ));
            foreach($items as $key => &$item)
            {
                $aHash = array_map('trim', explode(',', $item['Hashtag']['hashtags']));
                if(!in_array($search_keyword,$aHash))
                    unset($items[$key]);
            }
            if(!empty($items))
                $item_groups = Hash::combine($items,'{n}.Hashtag.id','{n}.Hashtag.item_id','{n}.Hashtag.item_table');
            else
                $item_groups = '';
            
            if ($type != 'all') { //result by filter
                if ($filter){
                    $event = new CakeEvent('Controller.Search.hashtags_filter', $this, array('search_keyword' => $search_keyword, 'type' => $type, 'item_ids' => !empty($item_groups[$type]) ? $item_groups[$type] : '' ) );
                }
                else {
                    $event = new CakeEvent('Controller.Search.hashtags', $this, array('search_keyword' => $search_keyword, 'type' => $type, 'item_ids' => !empty($item_groups[$type]) ? $item_groups[$type] : '' ) );
                }
                
                $result = $this->getEventManager()->dispatch($event);
                $this->set('other_suggestion', $result->result);
            } else { //all result
                if ($filter){
                    $event = new CakeEvent('Controller.Search.hashtags_filter', $this, array('search_keyword' => $search_keyword, 'type' => 'all', 'item_groups' => $item_groups));
                }
                else {
                    $event = new CakeEvent('Controller.Search.hashtags', $this, array('search_keyword' => $search_keyword, 'type' => 'all', 'item_groups' => $item_groups));
                }
                
                $result = $this->getEventManager()->dispatch($event);

                $this->set('other_suggestion', $result->result);
            }

            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            if ($type == 'all' || $type == 'activities')
            {
                $item_ids = !empty($item_groups['activities']) ? $item_groups['activities'] : array();

                $activities = $this->Activity->getActivityHashtags($item_ids, $uid, RESULTS_LIMIT,$page);
                
                // get activity likes
                if (!empty($uid)) {
                    $this->loadModel('Like');

                    $activity_likes = $this->Like->getActivityLikes($activities, $uid);
                    $this->set('activity_likes', $activity_likes);
                }

                if ($type == 'activities')
                {
                    $this->set('activities', $activities);
                    $activities = $this->Activity->getActivityHashtags($item_ids, $uid, RESULTS_LIMIT,$page + 1);                   
                    $this->set('bIsACtivityloadMore', count($activities) ? 1 : 0);
                    $this->set('result', 1);
                    $this->set('more_url', '/search/hashtags/' . $this->params['pass'][0] . '/activities/page:' . ( $page + 1 ));
                    $this->set('element_list_path', "activities");
                    $this->set('page',$page);
                }
                else
                {
                    if (count($activities) > 5) {
                        $activities = array_slice($activities, 0, 5);
                    }
                    $this->set('activities', $activities);
                }
                $this->set('is_search',true);
            }
            if ($type == 'all' || $type == 'comments')
            {
                $item_ids = !empty($item_groups['comments']) ? $item_groups['comments'] : '';
                $comments = $this->Comment->getCommentHashtags($item_ids, RESULTS_LIMIT,$page);

                $activity_comment_ids = !empty($item_groups['activity_comments']) ? $item_groups['activity_comments'] : '';
                $activity_comments = $this->ActivityComment->getActivityCommentHashtags($activity_comment_ids, RESULTS_LIMIT,$page);

                foreach($comments as $key => &$comment)
                {
                    $aCore = array('Photo_Photo','Photo_Album','Blog_Blog','Topic_Topic','Event_Event','Group_Group','Video_Video');
                    if(in_array($comment['Comment']['type'],$aCore))
                    {
                        list($plugin,$controller) = explode('_',$comment['Comment']['type']);
                        
                        $comment['Comment']['view_link'] = $this->request->base.'/'.lcfirst($controller).'s/view/'.$comment['Comment']['target_id'];
                    }
                    elseif ($comment['Comment']['type'] == 'comment')
                    {
                    	$comment_parent = MooCore::getInstance()->getItemByType($comment['Comment']['type'], $comment['Comment']['target_id']);
                    	if ($comment)
                    	{
                    		$object =  MooCore::getInstance()->getItemByType($comment_parent['Comment']['type'], $comment_parent['Comment']['target_id']);
                    		if (strpos($object[key($object)]['moo_href'], '?') === false)
                    		{                    		
                    			$extra = '?';
                    		}
                    		else 
                    		{
                    			$extra = '&';
                    		}
                    		$comment['Comment']['view_link'] = $object[key($object)]['moo_href'].$extra.'comment_id='.$comment_parent['Comment']['id'].'&reply_id='.$comment['Comment']['id'];;
                    	}
                    }
                    elseif ($comment['Comment']['type'] == 'core_activity_comment')
                    {
                    	$activty_comment = MooCore::getInstance()->getItemByType($comment['Comment']['type'], $comment['Comment']['target_id']);
                    	if ($activty_comment)
                    	{
                    		$activity =  MooCore::getInstance()->getItemByType('activity', $activty_comment['ActivityComment']['activity_id']);
                    		$comment['Comment']['view_link'] = $this->request->base.'/users/view/'.$activity['Activity']['user_id'].'/activity_id:'.$activty_comment['ActivityComment']['activity_id'].'/comment_id:'.$comment['Comment']['target_id'].'/reply_id:'.$comment['Comment']['id'];
                    	}
                    }
                    else
                    {
                    	$object =  MooCore::getInstance()->getItemByType($comment['Comment']['type'], $comment['Comment']['target_id']);
                    	$comment['Comment']['view_link'] = $object[key($object)]['moo_href'];
                    }
                }

                if ($type == 'comments')
                {
                    $this->set('comments', $comments);
                    $this->set('activity_comments', $activity_comments);
                    $this->set('result', 1);
                    $this->set('more_url', '/search/hashtags/' . $this->params['pass'][0] . '/comments/page:' . ( $page + 1 ));
                    $this->set('element_list_path', "lists/comments_list");
                    $this->set('page',$page);
                }
                else
                {
                    if (count($comments) > 5) {
                        $comments = array_slice($comments, 0, 5);
                        $this->set('comments', $comments);
                        $this->set('activity_comments', array());
                    }else{
                        $activity_comment_count = 5 - count($comments);
                        $activity_comments = array_slice($activity_comments, 0, $activity_comment_count);
                        $this->set('comments', $comments);
                        $this->set('activity_comments', $activity_comments);
                    }

                }
            }

            if ($type != 'all') {
                if ($page > 1) {
                    $this->set('more_link', true);
                }
            }
            $this->set('filter',$filter);
            $this->set('type', $type);
            $this->set('keyword', $keyword);
            if(isset($this->request->named['tabs']))
                $this->set('tabs',$this->request->named['tabs']);
        }
    }
    
    public function hashtags_filter($keyword = null,$type = 'all'){
        $this->loadModel('Activity');
        $this->loadModel('ActivityComment');
        $this->loadModel('Comment');
        $this->loadModel('Hashtag');
        if (!empty($type))
        {
            $search_keyword = str_replace('_','',$keyword);
            $uid = MooCore::getInstance()->getViewer(true);
            $items = $this->Hashtag->find('all',array(
                'conditions' => array(
                    'hashtags LIKE "%'.$search_keyword.'%"'
                )
            ));
            foreach($items as $key => &$item)
            {
                $aHash = explode(',',$item['Hashtag']['hashtags']);
                if(!in_array($search_keyword,$aHash))
                    unset($items[$key]);
            }
            if(!empty($items))
                $item_groups = Hash::combine($items,'{n}.Hashtag.id','{n}.Hashtag.item_id','{n}.Hashtag.item_table');
            else
                $item_groups = '';
            
            if ($type != 'all') { //result by filter
                $event = new CakeEvent('Controller.Search.hashtags', $this, array('search_keyword' => $search_keyword, 'type' => $type, 'item_ids' => !empty($item_groups[$type]) ? $item_groups[$type] : '' ) );
                $result = $this->getEventManager()->dispatch($event);
                $this->set('other_header', $result->result);
            } else { //all result
                $event = new CakeEvent('Controller.Search.hashtags', $this, array('search_keyword' => $search_keyword, 'type' => 'all', 'item_groups' => $item_groups));
                $result = $this->getEventManager()->dispatch($event);
                
                $this->set('other_suggestion', $result->result);
            }

            $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            if ($type == 'all' || $type == 'activities')
            {
                $item_ids = !empty($item_groups['activities']) ? $item_groups['activities'] : array();

                $activities = $this->Activity->getActivityHashtags($item_ids, $uid, RESULTS_LIMIT,$page);

                // get activity likes
                if (!empty($uid)) {
                    $this->loadModel('Like');

                    $activity_likes = $this->Like->getActivityLikes($activities, $uid);
                    $this->set('activity_likes', $activity_likes);
                }

                if ($type == 'activities')
                {
                    $this->set('activities', $activities);
                    $activities = $this->Activity->getActivityHashtags($item_ids, $uid, RESULTS_LIMIT,$page + 1);                   
                    $this->set('bIsACtivityloadMore', count($activities) ? 1 : 0);
                    $this->set('result', 1);
                    $this->set('more_url', '/search/hashtags/' . $this->params['pass'][0] . '/activities/page:' . ( $page + 1 ));
                    $this->set('element_list_path', "activities");
                    $this->set('page',$page);
                }
                else
                {
                    if (count($activities) > 5) {
                        $activities = array_slice($activities, 0, 5);
                    }
                    $this->set('activities', $activities);
                }
                $this->set('is_search',true);
            }
            if ($type == 'all' || $type == 'comments')
            {
                $item_ids = !empty($item_groups['comments']) ? $item_groups['comments'] : '';
                $comments = $this->Comment->getCommentHashtags($item_ids, RESULTS_LIMIT,$page);

                $activity_comment_ids = !empty($item_groups['activity_comments']) ? $item_groups['activity_comments'] : '';
                $activity_comments = $this->ActivityComment->getActivityCommentHashtags($activity_comment_ids, RESULTS_LIMIT,$page);

                foreach($comments as $key => &$comment)
                {
                	$aCore = array('Photo_Photo','Photo_Album','Blog_Blog','Topic_Topic','Event_Event','Group_Group','Video_Video');
                	if(in_array($comment['Comment']['type'],$aCore))
                	{
                		list($plugin,$controller) = explode('_',$comment['Comment']['type']);
                		
                		$comment['Comment']['view_link'] = $this->request->base.'/'.lcfirst($controller).'s/view/'.$comment['Comment']['target_id'];
                	}
                	elseif ($comment['Comment']['type'] == 'comment')
                	{
                		$comment_parent = MooCore::getInstance()->getItemByType($comment['Comment']['type'], $comment['Comment']['target_id']);
                		if ($comment)
                		{
                			$object =  MooCore::getInstance()->getItemByType($comment_parent['Comment']['type'], $comment_parent['Comment']['target_id']);
                			if (strpos($object[key($object)]['moo_href'], '?') === false)
                			{
                				$extra = '?';
                			}
                			else
                			{
                				$extra = '&';
                			}
                			$comment['Comment']['view_link'] = $object[key($object)]['moo_href'].$extra.'comment_id='.$comment_parent['Comment']['id'].'&reply_id='.$comment['Comment']['id'];;
                		}
                	}
                	elseif ($comment['Comment']['type'] == 'core_activity_comment')
                	{
                		$activty_comment = MooCore::getInstance()->getItemByType($comment['Comment']['type'], $comment['Comment']['target_id']);
                		if ($activty_comment)
                		{
                			$activity =  MooCore::getInstance()->getItemByType('activity', $activty_comment['ActivityComment']['activity_id']);
                			$comment['Comment']['view_link'] = $this->request->base.'/users/view/'.$activity['Activity']['user_id'].'/activity_id:'.$activty_comment['ActivityComment']['activity_id'].'/comment_id:'.$comment['Comment']['target_id'].'/reply_id:'.$comment['Comment']['id'];
                		}
                	}
                	else
                	{
                		$object =  MooCore::getInstance()->getItemByType($comment['Comment']['type'], $comment['Comment']['target_id']);
                		$comment['Comment']['view_link'] = $object[key($object)]['moo_href'];
                	}
                }

                if ($type == 'comments')
                {
                    $this->set('comments', $comments);
                    $this->set('activity_comments', $activity_comments);
                    $this->set('result', 1);
                    $this->set('more_url', '/search/hashtags/' . $this->params['pass'][0] . '/comments/page:' . ( $page + 1 ));
                    $this->set('element_list_path', "lists/comments_list");
                    $this->set('page',$page);
                }
                else
                {
                    if (count($comments) > 5) {
                        $comments = array_slice($comments, 0, 5);
                        $this->set('comments', $comments);
                        $this->set('activity_comments', array());
                    }else{
                        $activity_comment_count = 5 - count($comments);
                        $activity_comments = array_slice($activity_comments, 0, $activity_comment_count);
                        $this->set('comments', $comments);
                        $this->set('activity_comments', $activity_comments);
                    }

                }
            }

            if ($type != 'all') {
                if ($page > 1) {
                    $this->set('more_link', true);
                }
            }
            $this->set('type', $type);
            $this->set('keyword', $keyword);
            if(isset($this->request->named['tabs']))
                $this->set('tabs',$this->request->named['tabs']);
        }
    }
}
