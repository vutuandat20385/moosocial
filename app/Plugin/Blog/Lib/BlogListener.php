<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class BlogListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'Plugin.Controller.Blog.index' => 'processEventIndex',
            'Plugin.Controller.Blog.edit' => 'processEventEdit',
            'Plugin.Controller.Blog.afterSaveBlog' => 'processEventAfterSave',
            'Plugin.Controller.Blog.beforeView' => 'processEventBeforeView',
            'UserController.deleteUserContent' => 'deleteUserContent',
            'Model.User.afterSave' => 'afterSaveUser',
            'Controller.Search.search' => 'search',
            'Controller.Search.suggestion' => 'suggestion',
            'Controller.Search.hashtags' => 'hashtags',
            'Controller.Search.hashtags_filter' => 'hashtags_filter',
            'Controller.Widgets.tagCoreWidget' => 'hashtagEnable',
            'Controller.Like.afterLike' => 'afterLike',
            'Controller.Comment.afterComment' => 'afterComment',
            'Controller.User.deactivate' => 'deactivate',
            'Controller.Share.afterShare' => 'afterShare',
            'MooView.beforeRender' => 'beforeRender',
            'Controller.User.afterDeactive' => 'afterDeactiveUser',
            'Controller.User.afterEdit' => 'afterEditUser',

        );
    }

    public function afterEditUser($event)
    {
        $cuser = $event->data['item'];

        $categoryModel = Moocore::getInstance()->getModel('Category');
        $blogModel = Moocore::getInstance()->getModel('Blog.Blog');
        $blogCategory = $categoryModel->find('all', array('conditions' => array('Category.type' => 'Blog')));

        foreach ($blogCategory as $item) {
            $category_id = $item['Category']['id'];
            $blogs_count = $blogModel->find('count', array('conditions' => array(
                'Blog.category_id' => $category_id,
                'User.active' => true
            )));
            $categoryModel->updateAll(array('Category.item_count' => $blogs_count), array('Category.id' => $category_id));
        }
    }

    public function afterDeactiveUser($event)
    {
        $cuser = $event->data['item'];

        $categoryModel = Moocore::getInstance()->getModel('Category');
        $blogModel = Moocore::getInstance()->getModel('Blog.Blog');
        $blogCategory = $categoryModel->find('all', array('conditions' => array('Category.type' => 'Blog')));

        foreach ($blogCategory as $item) {
            $category_id = $item['Category']['id'];
            $blogs_count = $blogModel->find('count', array('conditions' => array(
                'Blog.category_id' => $category_id,
                'User.active' => true
            )));
            $categoryModel->updateAll(array('Category.item_count' => $blogs_count), array('Category.id' => $category_id));
        }
    }

    public function beforeRender($event)
    {
        $view = $event->subject();
        if ($view instanceof MooView) {
            $view->addPhraseJs(array(
                'drag_or_click_here_to_upload_photo' => __("Drag or click here to upload photo"),
                'are_you_sure_you_want_to_remove_this_entry' => __('Are you sure you want to remove this entry?')
            ));
        }
    }

    public function afterShare($event)
    {
        $data = $event->data['data'];
        if (isset($data['item_type']) && $data['item_type'] == 'Blog_Blog') {
            $blog_id = isset($data['parent_id']) ? $data['parent_id'] : 0;
            $blogModel = MooCore::getInstance()->getModel('Blog.Blog');
            $blogModel->updateAll(array('Blog.share_count' => 'Blog.share_count + 1'), array('Blog.id' => $blog_id));
        }
    }


    public function afterComment($event)
    {
        $data = $event->data['data'];
        $target_id = isset($data['target_id']) ? $data['target_id'] : null;
        $type = isset($data['type']) ? $data['type'] : '';
        if ($type == 'Blog_Blog' && !empty($target_id)) {
            $blogModel = MooCore::getInstance()->getModel('Blog.Blog');
            Cache::clearGroup('blog');
            $blogModel->updateCounter($target_id);
            Cache::delete('blog.blog_view_' . $target_id, 'blog');
        }
    }

    public function afterLike($event)
    {
        Cache::clearGroup('blog');
    }

    public function processEventIndex($event)
    {
        $v = $event->subject();

        $this->Friend = ClassRegistry::init('Friend');
        $event->result['friends_list'] = $this->Friend->getFriendsList($v->Auth->user('id'));
    }

    public function processEventEdit($event)
    {
        $v = $event->subject();
        App::import('Model', 'Tag');
        $this->Tag = new Tag();
        $tags = $this->Tag->getContentTags($event->data['id'], 'Blog_Blog');
        $v->set('tags', $tags);
    }

    public function processEventAfterSave($event)
    {
        $v = $event->subject();
        App::import('Model', 'Tag');
        $this->Tag = new Tag();
        $this->Tag->saveTags($event->data['tags'], $event->data['id'], 'Blog_Blog');

        // load feed model
        $this->Activity = ClassRegistry::init('Activity');

        // find activity which belong to event just created
        $activity = $this->Activity->find('first', array('conditions' => array(
            'Activity.item_type' => 'Blog_Blog',
            'Activity.item_id' => $event->data['id'],
        )));

        if (!empty($activity)) {
            $share = false;
            // only enable share feature for public event
            if ($event->data['privacy'] == PRIVACY_EVERYONE || $event->data['privacy'] == PRIVACY_FRIENDS) {
                $share = true;
            }
            $this->Activity->clear();
            $this->Activity->updateAll(array('Activity.share' => $share), array('Activity.id' => $activity['Activity']['id']));
        }
    }

    public function processEventBeforeView($event)
    {
        $v = $event->subject();
        $this->Friend = ClassRegistry::init('Friend');
        $this->FriendRequest = ClassRegistry::init('FriendRequest');
        $this->Tag = ClassRegistry::init('Tag');
        $this->Comment = ClassRegistry::init('Comment');
        $this->Like = ClassRegistry::init('Like');
        $areFriends = false;
        if (!empty($event->data['uid'])) { //  check if user is a friend
            $areFriends = $this->Friend->areFriends($event->data['uid'], $event->data['blog']['User']['id']);
        }
        $v->_checkPrivacy($event->data['blog']['Blog']['privacy'], $event->data['blog']['User']['id'], $areFriends);

        $tags = $this->Tag->getContentTags($event->data['id'], 'Blog_Blog');

        $comments = $this->Comment->getComments($event->data['id'], 'Blog_Blog');

        $comment_likes = array();
        // get comment likes
        if (!empty($event->data['uid'])) {
            $comment_likes = $this->Like->getCommentLikes($comments, $event->data['uid']);
            $v->set('comment_likes', $comment_likes);
        }

        $requests = $this->FriendRequest->getRequestsList($event->data['uid']);
        $respond = $this->FriendRequest->getRequests($event->data['uid']);
        $request_id = Hash::combine($respond, '{n}.FriendRequest.sender_id', '{n}.FriendRequest.id');
        $respond = Hash::extract($respond, '{n}.FriendRequest.sender_id');

        $v->set('respond', $respond);
        $v->set('request_id', $request_id);
        $v->set('friends_request', $requests);

        $v->set('tags', $tags);
        $v->set('areFriends', $areFriends);

        $comment_count = $this->Comment->getCommentsCount($event->data['id'], 'Blog_Blog');
        $page = 1;
        $data['bIsCommentloadMore'] = $comment_count - $page * RESULTS_LIMIT;
        $data['more_comments'] = '/comments/browse/blog_blog/' . $event->data['id'] . '/page:' . ($page + 1);
        $data['admins'] = array($event->data['blog']['Blog']['user_id']);
        $data['comments'] = $comments;
        $data['comment_likes'] = $comment_likes;
        $v->set('data', $data);
    }

    public function deleteUserContent($event)
    {
        App::import('Blog.Model', 'Blog');

        $this->Blog = new Blog();

        $blogs = $this->Blog->findAllByUserId($event->data['aUser']['User']['id']);
        foreach ($blogs as $blog) {
            $this->Blog->deleteBlog($blog);
        }
    }

    public function afterSaveUser($event)
    {
        $blogModel = ClassRegistry::init('Blog.Blog');
        $blogModel->unbindModel(array(
            'belongsTo' => array('Category')
        ));

        $blogByUser = $blogModel->findAllByUserId($event->data['id']);
        foreach ($blogByUser as &$blog) {
            Cache::delete('blog.blog_view_' . $blog['Blog']['id'], 'blog');
        }

        $blogModel->bindModel(array(
            'belongsTo' => array('Category' => array(
                'counterCache' => 'item_count',
                'counterScope' => array('Category.type' => 'Blog')
            ))
        ));
    }

    public function search($event)
    {
        $e = $event->subject();
        App::import('Model', 'Blog.Blog');
        $this->Blog = new Blog();
        $results = $this->Blog->getBlogs('search', $e->keyword, 1);
        if (count($results) > 5)
            $results = array_slice($results, 0, 5);
        if (empty($results))
            $results = $this->Blog->getBlogSuggestion($e->keyword, 5, 1);

        if (isset($e->plugin) && $e->plugin == 'Blog') {
            $e->set('blogs', $results);
            $e->render("Blog.Elements/lists/blogs_list");
        } else {
            $event->result['Blog']['header'] = __("Blogs");
            $event->result['Blog']['icon_class'] = "library_books";
            $event->result['Blog']['view'] = "lists/blogs_list";
            if (!empty($results))
                $event->result['Blog']['notEmpty'] = 1;
            $e->set('blogs', $results);

        }
    }

    public function suggestion($event)
    {
        $e = $event->subject();
        App::import('Model', 'Blog.Blog');
        $this->Blog = new Blog();

        $event->result['blog']['header'] = __('Blogs');
        $event->result['blog']['icon_class'] = 'library_books';

        if (isset($event->data['type']) && $event->data['type'] == 'blog') {
            $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;
            $blogs = $this->Blog->getBlogs('search', $event->data['searchVal'], $page);
            $more_blogs = $this->Blog->getBlogs('search', $event->data['searchVal'], $page + 1);
            $more_result = 0;
            if (!empty($more_blogs))
                $more_result = 1;
            if (empty($blogs))
                $blogs = $this->Blog->getBlogSuggestion($event->data['searchVal'], RESULTS_LIMIT, $page);

            $more_url = isset($e->params['pass'][1]) ? '/search/suggestion/blog/' . $e->params['pass'][1] . '/page:' . ($page + 1) : '';

            $e->set('blogs', $blogs);
            $e->set('result', 1);
            $e->set('more_url', $more_url);
            $e->set('more_result', $more_result);
            $e->set('element_list_path', "Blog.lists/blogs_list");
        }
        if (isset($event->data['type']) && $event->data['type'] == 'all') {
            $event->result['blog'] = null;
            $blogs = $this->Blog->getBlogs('search', $event->data['searchVal'], 1, 2);
            if (count($blogs) > 2) {
                $blogs = array_slice($blogs, 0, 2);
            }
            if (empty($blogs))
                $blogs = $this->Blog->getBlogSuggestion($event->data['searchVal'], 2);

            if (!empty($blogs)) {
                $event->result['blog'] = array(__('Blog'));
                $helper = MooCore::getInstance()->getHelper("Blog_Blog");
                foreach ($blogs as $index => &$detail) {
                    $index++;
                    $event->result['blog'][$index]['id'] = $detail['Blog']['id'];
                    if (!empty($detail['Blog']['thumbnail'])) {
                        //$thumb = explode('/',$detail['Blog']['thumbnail']);
                    	$event->result['blog'][$index]['img'] = $helper->getImage($detail,array('prefix'=>'75_square'));

                    }
                    $event->result['blog'][$index]['title'] = $detail['Blog']['title'];
                    $event->result['blog'][$index]['find_name'] = 'Find Blog';
                    $event->result['blog'][$index]['icon_class'] = 'library_books';
                    $event->result['blog'][$index]['view_link'] = 'blogs/view/';

                    $mooHelper = MooCore::getInstance()->getHelper('Core_Moo');

                    $utz = (!is_numeric(Configure::read('core.timezone'))) ? Configure::read('core.timezone') : 'UTC';
                    $cuser = MooCore::getInstance()->getViewer();
                    // user timezone
                    if (!empty($cuser['User']['timezone'])) {
                        $utz = $cuser['User']['timezone'];
                    }

                    $event->result['blog'][$index]['more_info'] = __('Posted by') . ' ' . $mooHelper->getNameWithoutUrl($detail['User'], false) . ' ' .
                        $mooHelper->getTime($detail['Blog']['created'], Configure::read('core.date_format'), $utz);
                }
            }
        }
    }

    public function hashtags($event)
    {
        $enable = Configure::read('Blog.blog_hashtag_enabled');
        $blogs = array();
        $e = $event->subject();
        App::import('Model', 'Blog.Blog');
        App::import('Model', 'Tag');
        $this->Tag = new Tag();
        $this->Blog = new Blog();
        $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;

        $uid = CakeSession::read('uid');
        if ($enable) {
            if (isset($event->data['type']) && $event->data['type'] == 'blogs') {
                $blogs = $this->Blog->getBlogHashtags($event->data['item_ids'], RESULTS_LIMIT, $page);
                $blogs = $this->_filterBlog($blogs);
            }
            $table_name = $this->Blog->table;
            if (isset($event->data['type']) && $event->data['type'] == 'all' && !empty($event->data['item_groups'][$table_name])) {
                $blogs = $this->Blog->getBlogHashtags($event->data['item_groups'][$table_name], 5);
                $blogs = $this->_filterBlog($blogs);
            }
        }

        // get tagged item
        $tag = h(urldecode($event->data['search_keyword']));
        $tags = $this->Tag->find('all', array('conditions' => array(
            'Tag.type' => 'Blog_Blog',
            'Tag.tag' => $tag
        )));
        $blog_ids = Hash::combine($tags, '{n}.Tag.id', '{n}.Tag.target_id');

        $friendModel = MooCore::getInstance()->getModel('Friend');

        $items = $this->Blog->find('all', array('conditions' => $this->Blog->addBlockCondition(array(
            'Blog.id' => $blog_ids
        )),
            'limit' => RESULTS_LIMIT,
            'page' => $page
        ));

        $viewer = MooCore::getInstance()->getViewer();

        foreach ($items as $key => $item) {
            $owner_id = $item[key($item)]['user_id'];
            $privacy = isset($item[key($item)]['privacy']) ? $item[key($item)]['privacy'] : 1;
            if (empty($viewer)) { // guest can view only public item
                if ($privacy != PRIVACY_EVERYONE) {
                    unset($items[$key]);
                }
            } else { // viewer
                $aFriendsList = array();
                $aFriendsList = $friendModel->getFriendsList($owner_id);
                if ($privacy == PRIVACY_ME) { // privacy = only_me => only owner and admin can view items
                    if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id) {
                        unset($items[$key]);
                    }
                } else if ($privacy == PRIVACY_FRIENDS) { // privacy = friends => only owner and friendlist of owner can view items
                    if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id && !in_array($viewer['User']['id'], array_keys($aFriendsList))) {
                        unset($items[$key]);
                    }
                } else {

                }
            }
        }
        $blogs = array_merge($blogs, $items);

        //only display 5 items on All Search Result page
        if (isset($event->data['type']) && $event->data['type'] == 'all') {
            $blogs = array_slice($blogs, 0, 5);
        }
        $blogs = array_map("unserialize", array_unique(array_map("serialize", $blogs)));
        if (!empty($blogs)) {
            $event->result['blogs']['header'] = __('Blogs');
            $event->result['blogs']['icon_class'] = 'library_books';
            $event->result['blogs']['view'] = "Blog.lists/blogs_list";
            if (isset($event->data['type']) && $event->data['type'] == 'blogs') {
                $e->set('result', 1);
                $e->set('more_url', '/search/hashtags/' . $e->params['pass'][0] . '/blogs/page:' . ($page + 1));
                $e->set('element_list_path', "Blog.lists/blogs_list");
            }
            $e->set('blogs', $blogs);
        }
    }

    public function hashtags_filter($event)
    {

        $e = $event->subject();
        App::import('Model', 'Blog.Blog');
        $this->Blog = new Blog();

        if (isset($event->data['type']) && $event->data['type'] == 'blogs') {
            $page = (!empty($e->request->named['page'])) ? $e->request->named['page'] : 1;
            $blogs = $this->Blog->getBlogHashtags($event->data['item_ids'], RESULTS_LIMIT, $page);
            $e->set('blogs', $blogs);
            $e->set('result', 1);
            $e->set('more_url', '/search/hashtags/' . $e->params['pass'][0] . '/blogs/page:' . ($page + 1));
            $e->set('element_list_path', "Blog.lists/blogs_list");
        }
        $table_name = $this->Blog->table;
        if (isset($event->data['type']) && $event->data['type'] == 'all' && !empty($event->data['item_groups'][$table_name])) {
            $event->result['blogs'] = null;

            $blogs = $this->Blog->getBlogHashtags($event->data['item_groups'][$table_name], 5);

            if (!empty($blogs)) {
                $event->result['blogs']['header'] = __('Blogs');
                $event->result['blogs']['icon_class'] = 'library_books';
                $event->result['blogs']['view'] = "Blog.lists/blogs_list";
                $e->set('blogs', $blogs);
            }
        }
    }

    private function _filterBlog($blogs)
    {
        if (!empty($blogs)) {
            $friendModel = MooCore::getInstance()->getModel('Friend');
            $viewer = MooCore::getInstance()->getViewer();
            foreach ($blogs as $key => &$blog) {
                $owner_id = $blog[key($blog)]['user_id'];
                $privacy = isset($blog[key($blog)]['privacy']) ? $blog[key($blog)]['privacy'] : 1;
                if (empty($viewer)) { // guest can view only public item
                    if ($privacy != PRIVACY_EVERYONE) {
                        unset($blogs[$key]);
                    }
                } else { // viewer
                    $aFriendsList = array();
                    $aFriendsList = $friendModel->getFriendsList($owner_id);
                    if ($privacy == PRIVACY_ME) { // privacy = only_me => only owner and admin can view items
                        if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id) {
                            unset($blogs[$key]);
                        }
                    } else if ($privacy == PRIVACY_FRIENDS) { // privacy = friends => only owner and friendlist of owner can view items
                        if (!$viewer['Role']['is_admin'] && $viewer['User']['id'] != $owner_id && !in_array($viewer['User']['id'], array_keys($aFriendsList))) {
                            unset($blogs[$key]);
                        }
                    } else {

                    }
                }
            }
        }

        return $blogs;
    }

    public function hashtagEnable($event)
    {
        $enable = Configure::read('Blog.blog_hashtag_enabled');
        $event->result['blogs']['enable'] = $enable;
    }

    public function deactivate($event)
    {
        $blogModel = MooCore::getInstance()->getModel('Blog.Blog');
        $blogCategory = $blogModel->find('all', array(
                'conditions' => array('Blog.user_id' => $event->data['uid']),
                'group' => array('Blog.category_id'),
                'fields' => array('category_id', '(SELECT count(*) FROM ' . $blogModel->tablePrefix . 'blogs WHERE category_id=Blog.category_id AND user_id = ' . $event->data['uid'] . ') as count')
            )
        );
        $blogCategory = Hash::combine($blogCategory, '{n}.Blog.category_id', '{n}.{n}.count');
        $event->result['Blog'] = $blogCategory;
    }
}
