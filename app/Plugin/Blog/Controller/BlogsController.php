<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEvent', 'Event');

class BlogsController extends BlogAppController
{


    public $paginate = array('limit' => RESULTS_LIMIT);

    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->loadModel('Blog.Blog');

    }

    public function index($cat_id = null)
    {
        $this->loadModel('Category');

        $cat_id = intval($cat_id);

        $events = new CakeEvent('Plugin.Controller.Blog.index', $this);
        $this->getEventManager()->dispatch($events);

        $sFriendsList = '';
        $aFriendListId = array_keys($events->result['friends_list']);
        $sFriendsList = implode(',', $aFriendListId);

        $role_id = $this->_getUserRoleId();

        if (!empty($cat_id)) {
            $blogs = $this->Blog->getBlogs('category', $cat_id, 1, RESULTS_LIMIT, $sFriendsList, $role_id);
            $more_blogs = $this->Blog->getBlogs('category', $cat_id, 2, RESULTS_LIMIT, $sFriendsList, $role_id);
        } else {
            $blogs = $this->Blog->getBlogs(null, $this->Auth->user('id'), 1, RESULTS_LIMIT, $sFriendsList, $role_id);
            $more_blogs = $this->Blog->getBlogs(null, $this->Auth->user('id'), 2, RESULTS_LIMIT, $sFriendsList, $role_id);
        }
        $blogs_id = implode(',', Hash::extract($blogs, '{n}.Blog.id'));


        $more_result = 0;
        if (!empty($more_blogs))
            $more_result = 1;
        $this->loadModel('Tag');
        $tags = $this->Tag->getTags('Blog_Blog', Configure::read('core.popular_interval'), RESULTS_LIMIT, $blogs_id);

        $uid = $this->Auth->user('id');
        if (!empty($uid)) {
            $this->loadModel('Like');
            $like = $this->Like->getUserLikeByType($uid, 'Blog_Blog');
            $this->set('like', $like);
        }

        $this->set('tags', $tags);
        $this->set('blogs', $blogs);
        $this->set('title_for_layout', '');
        $this->set('more_result', $more_result);
        $this->set('cat_id', $cat_id);
    }

    public function profile_user_blog($uid = null,$isRedirect=true)
    {
        $uid = intval($uid);

        if($isRedirect) {
                    $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            }
            else {
                $page = $this->request->query('page') ? $this->request->query('page') : 1;
            }

        $role_id = $this->_getUserRoleId();

        $this->loadModel('Friend');
        $are_friend = 0;
        if ($this->Friend->areFriends($this->Auth->user('id'), $uid))
            $are_friend = 1;
        if ($this->Auth->user('id') == $uid)
            $role_id = ROLE_ADMIN; //viewer view his own blog
        $blogs = $this->Blog->getBlogs('user', $uid, $page, RESULTS_LIMIT, $are_friend, $role_id);

        $more_blogs = $this->Blog->getBlogs('user', $uid, $page + 1, RESULTS_LIMIT, $are_friend, $role_id);
        $more_result = 0;
        if (!empty($more_blogs))
            $more_result = 1;

        $this->set('blogs', $blogs);
        $this->set('more_url', '/blogs/profile_user_blog/' . $uid . '/page:' . ($page + 1));
        $this->set('user_id', $uid);
        $this->set('user_blog', true);
        $this->set('more_result', $more_result);

        if($isRedirect && $this->theme != "mooApp") {
            if ($page > 1)
                $this->render('/Elements/lists/blogs_list');
            else
                $this->render('Blog.Blogs/profile_user_blog');
        }

    }

    /*
	 * Browse entries based on $type
	 * @param string $type - possible value: all (default), my, home, friends, search
	 * @param mixed $param - could be uid (user) or a query string (search)
	 */
    public function browse($type = null, $param = null,$isRedirect = true)
    {
            if($isRedirect) {
                    $page = (!empty($this->request->named['page'])) ? $this->request->named['page'] : 1;
            }
            else {
                $page = $this->request->query('page') ? $this->request->query('page') : 1;
            }

            if (!empty($this->request->named['category_id'])) {
                $type = 'category';
                $param = $this->request->named['category_id'];
            }

            $url = (!empty($param)) ? $type . '/' . $param : $type;
            $uid = $this->Auth->user('id');
            $sFriendsList = '';

            switch ($type) {
                case 'home':
                case 'my':
                    $this->set('user_blog', true);
                    $this->_checkPermission();
                    $param = $uid;
                    break;

                case 'friends':
                    $this->_checkPermission();
                    $param = $uid;
                    break;

                case 'search':
                    $param = urldecode($param);

                    if (!Configure::read('core.guest_search') && empty($uid))
                        $this->_checkPermission();

                    break;
                case 'category':
                    $this->Session->write('cat_id', $param);
                    break;
                default:
                    $this->loadModel('Friend');
                    $friends_list = $this->Friend->getFriendsList($uid);
                    $aFriendListId = array_keys($friends_list);
                    $sFriendsList = implode(',', $aFriendListId);
                    $param = $uid;
            }
            $role_id = $this->_getUserRoleId();
            $blogs = $this->Blog->getBlogs($type, $param, $page, RESULTS_LIMIT, $sFriendsList, $role_id);
            $more_blogs = $this->Blog->getBlogs($type, $param, $page + 1, RESULTS_LIMIT, $sFriendsList, $role_id);
            $more_result = 0;
            if (!empty($more_blogs))
                $more_result = 1;

            if (!empty($uid)) {
                $this->loadModel('Like');
                $like = $this->Like->getUserLikeByType($uid, 'Blog_Blog');
                $this->set('like', $like);
            }

            $this->set('blogs', $blogs);
            $this->set('more_url', '/blogs/browse/' . h($url) . '/page:' . ($page + 1));
            $this->set('page', $page);
            $this->set('type', $type);
            $this->set('more_result', $more_result);
        if($this->theme != "mooApp" && $isRedirect){
            if ($page == 1 && $type == 'home') {
                $this->render('/Elements/ajax/home_blog');
            } else {
                if ($this->request->is('ajax')) {
                    $this->render('/Elements/lists/blogs_list');
                } else {
                    $this->render('/Elements/lists/blogs_list_m');
                }
            }
        }
        else {
            if($type == 'category') $this->set('categoryId', $param);
            $this->set('type', $type);
        }
    }

    public function api_browse($type = null, $param = null)
    {
        $this->browse($type, $param);
    }

    /*
         * Show add/edit blog form
         * @param int $id - blog id to edit
         */
    public function create($id = null)
    {

        $id = intval($id);
        $this->_checkPermission(array('confirm' => true));
        $this->_checkPermission(array('aco' => 'blog_create'));
        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();
        $cats = $this->Category->getCategoriesList('Blog', $role_id);
        if (!empty($id)) // editing
        {
            $blog = $this->Blog->findById($id);
            $this->_checkExistence($blog);
            $this->_checkPermission(array('admins' => array($blog['User']['id'])));

            $event = new CakeEvent('Plugin.Controller.Blog.edit', $this, array('id' => $id));
            $this->getEventManager()->dispatch($event);

            $this->set('title_for_layout', __('Edit Entry'));
        } else {
            $blog = $this->Blog->initFields();

            if ($this->Session->check('cat_id')) {
                $blog['Blog']['category_id'] = $this->Session->read('cat_id');
                $this->Session->delete('cat_id');
            }

            $this->set('title_for_layout', __('Write New Entry'));
        }

        $this->set('blog', $blog);
        $this->set('cats', $cats);
    }

    /*
     * Save add/edit form
     */
    public function save($isReturn = false)
    { 
        $this->_checkPermission(array('confirm' => true));
        $this->autoRender = false;
        $uid = $this->Auth->user('id');

        if (!empty($this->request->data['id'])) { // edit blog
            // check edit permission
            $blog = $this->Blog->findById($this->request->data['id']);
            $this->_checkPermission(array('admins' => array($blog['User']['id'])));
            $this->Blog->id = $this->request->data['id'];
        } else {
            $this->request->data['user_id'] = $uid;
        }

        $this->request->data['body'] = str_replace('../', '/', $this->request->data['body']);

        $this->Blog->set($this->request->data);
        $this->_validateData($this->Blog);
        
        if ($this->Blog->save()) {
            // update Blog item_id for photo thumbnail
            
        	if (!empty($this->request->data['blog_photo_ids'])) {
        		$photos = explode(',', $this->request->data['blog_photo_ids']);
        		if (count($photos))
        		{
		            $this->loadModel('Photo.Photo');
		            // Hacking for cdn
		            $result = $this->Photo->find("all",array(
		                'recursive'=>1,
		                'conditions' =>array(
		                    'Photo.type' => 'Blog',
		                    'Photo.user_id' => $uid,
		                    'Photo.target_id' => 0,
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
		                $this->Photo->updateAll(array('Photo.target_id' => $this->Blog->id), array(
		                		'Photo.type' => 'Blog',
		                		'Photo.user_id' => $uid,
		                		'Photo.target_id' => 0,
		                		'Photo.id' => $photos
		                ));
		            }
		            // End hacking
        		}
        	}

            $event = new CakeEvent('Plugin.Controller.Blog.afterSaveBlog', $this, array(
                'tags' => $this->request->data['tags'],
                'id' => $this->Blog->id,
                'privacy' => $this->request->data['privacy']
            ));

            $this->getEventManager()->dispatch($event);
            if(!$isReturn) {
                $response['result'] = 1;
                $response['id'] = $this->Blog->id;
                echo json_encode($response);
            }
            else {
                return $this->Blog->id;
            }
        } 
        else {

        }
    }

    public function view($id = null)
    {
        $id = intval($id);
        $this->Blog->recursive = 2;
        $blog = $this->Blog->findById($id);
        if ($blog['Category']['id'])
        {
        	foreach ($blog['Category']['nameTranslation'] as $translate)
        	{
        		if ($translate['locale'] == Configure::read('Config.language'))
        		{
        			$blog['Category']['name'] = $translate['content'];
        			break;
        		}
        	}
        }
        $this->Blog->recursive = 0;

        $this->_checkExistence($blog);
        $this->_checkPermission(array('aco' => 'blog_view'));
        $this->_checkPermission(array('user_block' => $blog['Blog']['user_id']));
        $uid = $this->Auth->user('id');
        
        $event = new CakeEvent('Plugin.Controller.Blog.beforeView', $this, array('id' => $id, 'uid' => $uid, 'blog' => $blog));
        $this->getEventManager()->dispatch($event);
            $other_entries = Cache::read('blog.other_entries_' . $id, 'blog');
            if ($other_entries == '') {
                $other_entries = $this->Blog->find('all', array('conditions' => array('Blog.user_id' => $blog['Blog']['user_id'],
                    'Blog.id <> ' . $id
                ),
                    'order' => 'Blog.id desc',
                    'limit' => 5
                ));
                Cache::write('blog.other_entries_' . $id, $other_entries, 'blog');
            }

            MooCore::getInstance()->setSubject($blog);

            $og = array('type' => 'blog');

            $this->loadModel('Like');
            $likes = $this->Like->getLikes($blog['Blog']['id'], 'Blog_Blog');
            $this->set('likes', $likes);

            if (!empty($uid)) {
                $like = $this->Like->getUserLike($blog['Blog']['id'], $uid, 'Blog_Blog');
                $this->set('like', $like);
            }

            $this->set('og', $og);
            $this->set('blog', $blog);
            $this->set('other_entries', $other_entries);

            $this->set('title_for_layout', $blog['Blog']['title']);
            $description = $this->getDescriptionForMeta($blog['Blog']['body']);
            if ($description) {
                $this->set('description_for_layout', $description);
                $tags = $this->viewVars['tags'];
                if (count($tags)) {
                    $tags = implode(",", $tags) . ' ';
                } else {
                    $tags = '';
                }
                $this->set('mooPageKeyword', $this->getKeywordsForMeta($tags . $description));
            }

            $this->set('admins', array($blog['Blog']['user_id']));
            // set og:image
            if ($blog['Blog']['thumbnail']) {
                $mooHelper = MooCore::getInstance()->getHelper('Core_Moo');
                $this->set('og_image', $mooHelper->getImageUrl($blog, array('prefix' => '850')));
            }
        if($this->theme == "mooApp"){
            $this->set('title_for_layout', $blog['Blog']['title']);
            $this->set('blogId',$id);
        }
    }

    /*
     * Delete blog
     * @param int $id - blog id to delete
     */
    public function delete($id = null,$isRedirect = true)
    {
        $id = intval($id);
        $blog = $this->Blog->findById($id);
        $this->_checkExistence($blog);
        $this->_checkPermission(array('admins' => array($blog['User']['id'])));

        $this->Blog->deleteBlog($blog);
        $cakeEvent = new CakeEvent('Plugin.Controller.Blog.afterDeleteBlog', $this, array('item' => $blog));
        $this->getEventManager()->dispatch($cakeEvent);
        if($isRedirect) {
            $this->Session->setFlash(__('Entry has been deleted'), 'default', array('class' => 'Metronic-alerts alert alert-success fade in'));
            $this->redirect('/blogs');
        }
        else {
            $this->set(array(
                'message' => __('success'),
                '_serialize' => array('message'),
            ));
        }
    }

    public function popular()
    {
        if ($this->request->is('requested')) {
            $num_item_show = $this->request->named['num_item_show'];
            $popular_blogs = $this->Blog->getPopularBlogs($num_item_show, Configure::read('core.popular_interval'));
            return $popular_blogs;
        }
    }

    public function _checkPrivacy($privacy, $owner, $areFriends = null, $redirect = true)
    {
        return parent::_checkPrivacy($privacy, $owner, $areFriends);
    }

    public function admin_index()
    {
        if (!empty($this->request->data['keyword']))
            $this->redirect('/admin/blogs/index/keyword:' . $this->request->data['keyword']);

        $cond = array();
        if (!empty($this->request->named['keyword']))
            $cond['MATCH(Blog.title) AGAINST(? IN BOOLEAN MODE)'] = $this->request->named['keyword'];

        $blogs = $this->paginate('Blog', $cond);

        $this->loadModel('Category');
        $categories = $this->Category->getCategoriesListItem('Blog');

        $this->set('blogs', $blogs);
        $this->set('categories', $categories);
        $this->set('title_for_layout', 'Blogs Manager');
    }

    public function admin_move()
    {
        if (!empty($_POST['blogs']) && !empty($this->request->data['category'])) {
            foreach ($_POST['blogs'] as $blog_id) {
                $this->Blog->id = $blog_id;
                $this->Blog->save(array('category_id' => $this->request->data['category']));
            }

            $this->Session->setFlash(__('Blog has been moved'));
        }

        $this->redirect($this->referer());
    }

    public function categories_list($isRedirect = true)
    {
        $this->loadModel('Category');
        $role_id = $this->_getUserRoleId();
        $categories = $this->Category->getCategories('Blog', $role_id);
        if ($this->request->is('requested')) {
            return $categories;
        }
        if($isRedirect && $this->theme == "mooApp") {
            $this->render('/Elements/lists/categories_list');
        }
    }
}

?>
