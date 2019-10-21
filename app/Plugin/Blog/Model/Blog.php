<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('BlogAppModel','Blog.Model');
class Blog extends BlogAppModel {
    
    public $actsAs = array(
        'Activity' => array(
            'type' => 'user',
            'action_afterCreated' => 'blog_create',
            'item_type' => 'Blog_Blog',
            'query' => 1,
            'params' => 'item',
            'share' => true,
        ),
        
        'MooUpload.Upload' => array(
            'thumbnail' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}blogs{DS}{field}{DS}',
            ),
        ),
        'Hashtag',
        'Storage.Storage' => array(
            'type'=>array('blogs'=>'thumbnail'),
        ),
    );

    public $mooFields = array('title', 'href', 'plugin', 'type', 'url', 'thumb', 'privacy');

    public $belongsTo = array(
        'User' => array('counterCache' => true),
        'Category' => array(
            'counterCache' => 'item_count',
            'counterScope' => array('Category.type' => 'Blog')
        )
    );

    public $hasMany = array(
        'Comment' => array(
            'className' => 'Comment',
            'foreignKey' => 'target_id',
            'conditions' => array('Comment.type' => 'Blog_Blog'),
            'dependent' => true,
        ),
        'Like' => array(
            'className' => 'Like',
            'foreignKey' => 'target_id',
            'conditions' => array('Like.type' => 'Blog_Blog'),
            'dependent' => true,
        ),
        'Tag' => array(
            'className' => 'Tag',
            'foreignKey' => 'target_id',
            'conditions' => array('Tag.type' => 'Blog_Blog'),
            'dependent' => true,
        ),
    );

    public $order = 'Blog.id desc';

    public $validate = array(
        'title' => array(
            'rule' => 'notBlank',
            'message' => 'Title is required',
        ),
        'category_id' => array(
            'rule' => 'notBlank',
            'message' => 'Category is required'
        ),
        'body' => array(
            'rule' => 'notBlank',
            'message' => 'Body is required',
        ),
        'tags' => array(
        	'validateTag' => array(
        		'rule' => array('validateTag'),
        		'message' => 'No special characters ( /,?,#,%,...) allowed in Tags',
        	)
        )
    );

	/*
	 * Get blog entries based on type
	 * @param string $type - possible value: all (default), my, home, category, friends, user, search
	 * @param mixed $param - could be catid (category), uid (friends, home, my, user) or a query string (search)
	 * @param int $page - page number
	 * @return array $blogs
	 */
	public function getBlogs( $type = null, $param = null, $page = 1, $limit = RESULTS_LIMIT, $friend_list = '',$role_id = null)
	{
            $pp = Configure::read('Blog.blog_item_per_pages');
            
            if (!empty($pp)){
                $limit = $pp;
            }
            
            $cond = array();
            
            $viewer = MooCore::getInstance()->getViewer();
            $uid = isset($viewer['User']['id']) ? $viewer['User']['id'] : 0;
            
            $friends = array();
            if ($uid){
                App::import('Model', 'Friend');
                $friend = new Friend();
                $friends = $friend->getFriends($uid);
            }

            switch ($type) {
                case 'category':
                    if (!empty($param)) {
                        $cond = array('Blog.category_id' => $param, 'Category.type' => 'Blog');
                    }

                    break;
                case 'friends':
                    if ($param) {
                        if ($role_id == ROLE_ADMIN) {
                            $cond = array('Blog.user_id' => $friends);
                        } else {
                            $cond = array('Blog.user_id' => $friends, 'Blog.privacy <> ' . PRIVACY_ME);
                        }
                    }
                    break;

                case 'home':
                case 'my':
                    if ($param)
                        $cond = array('Blog.user_id' => $param);

                    break;

                case 'user':
                    if ($param) {
                        if ($role_id == ROLE_ADMIN) //viewer is admin or owner himself
                            $cond = array('Blog.user_id' => $param);
                        elseif (!empty($friend_list)) //viewer is a friend
                            $cond = array('Blog.user_id' => $param, 'Blog.privacy <> ' . PRIVACY_ME);
                        else // normal viewer
                            $cond = array('Blog.user_id' => $param, 'Blog.privacy' => PRIVACY_EVERYONE);
                    }
                    break;

                case 'search':
                    if ($role_id == ROLE_ADMIN){
                       if ($param){
                            $cond = array('MATCH(Blog.title, Blog.body) AGAINST(? IN BOOLEAN MODE)' => urldecode($param));
                       }
                       
                    }
                    else{
                        if ($param){
                            $cond = array(
                                'OR' => array(
                                    array('MATCH(Blog.title, Blog.body) AGAINST(? IN BOOLEAN MODE)' => urldecode($param), 'Blog.privacy' => PRIVACY_EVERYONE),
                                    array('Blog.user_id' => $uid, 'MATCH(Blog.title, Blog.body) AGAINST(? IN BOOLEAN MODE)' => urldecode($param)),
                                    
                                ),
                            );
                            if (count($friends)){
                               $cond['OR'][] =  array('Blog.user_id' => $friends, 'MATCH(Blog.title, Blog.body) AGAINST(? IN BOOLEAN MODE)' => urldecode($param), 'Blog.privacy' => PRIVACY_FRIENDS);
                            }
                        }else {
                            $cond = array(
                                'OR' => array(
                                    array('Blog.privacy' => PRIVACY_EVERYONE),
                                    array('Blog.user_id' => $uid),
                                ),
                            );
                            
                            if (count($friends)){
                                $cond['OR'][] =  array('Blog.user_id' => $friends, 'Blog.privacy' => PRIVACY_FRIENDS);
                            }
                        }
                    }
                    break;

                default:
                    if ($role_id == ROLE_ADMIN) {
                        $cond = array();
                    } else {
                        $cond = array(
                            'OR' => array(
                                array(
                                    'Blog.privacy' => PRIVACY_EVERYONE,
                                ),
                                array(
                                    'Blog.user_id' => $param
                                ),
                                array(
                                    'Find_In_Set(Blog.user_id,"' . $friend_list . '")',
                                    'Blog.privacy' => PRIVACY_FRIENDS
                                )
                            ),
                        );
                    }
            }

            //get blogs of active user
            $cond['User.active'] = 1;
            $cond = $this->addBlockCondition($cond);
            $blogs = $this->find('all', array('conditions' => $cond, 'limit' => $limit, 'page' => $page)); 
            return $blogs;
        }

        public function getPopularBlogs( $limit = 5, $days = null )
	{
		$cond = array('Blog.privacy' => PRIVACY_EVERYONE);

        //get blogs of active user
        $cond['User.active'] = 1;

		if ( !empty( $days ) )
			$cond['DATE_SUB(CURDATE(),INTERVAL ? DAY) <= Blog.created'] = intval($days);
		$cond = $this->addBlockCondition($cond);
		$blogs = $this->find( 'all', array( 'conditions' => $cond, 
											'order' => 'Blog.like_count desc', 
											'limit' => intval($limit)
							) );
									 
		return $blogs;
	}
	
	public function deleteBlog( $blog )
	{
            
            // delete activity
            $activityModel = MooCore::getInstance()->getModel('Activity');
            $parentActivity = $activityModel->find('list', array('fields' => array('Activity.id') , 'conditions' => 
                array('Activity.item_type' => 'Blog_Blog', 'Activity.item_id' => $blog['Blog']['id'])));

            $activityModel->deleteAll(array( 'Activity.item_type' => 'Blog_Blog', 'Activity.item_id' => $blog['Blog']['id'] ), true, true);

            // delete child activity
            $activityModel->deleteAll(array('Activity.item_type' => 'Blog_Blog', 'Activity.parent_id' => $parentActivity));
        
            $this->delete( $blog['Blog']['id'] );

	}

    public function getBlogSuggestion($q, $limit = RESULTS_LIMIT,$page = 1){
    	$cond = array('Blog.title LIKE'=> $q . "%",'Blog.privacy' => PRIVACY_EVERYONE );

        //get blogs of active user
        $cond['User.active'] = 1;

        $blogs = $this->find( 'all', array( 'conditions' => $this->addBlockCondition($cond), 'limit' => $limit, 'page' => $page ) );
        return $blogs;
    }

    public function getBlogHashtags($qid, $limit = RESULTS_LIMIT,$page = 1){
        $cond = array(
            'Blog.id' => $qid,
          
        );

        //get blogs of active user
        $cond['User.active'] = 1;		
        $blogs = $this->find( 'all', array( 'conditions' => $this->addBlockCondition($cond), 'limit' => $limit, 'page' => $page ) );
        
        return $blogs;
    }

    public function afterSave($created, $options = array()){
        Cache::clearGroup('blog');
    }
    public function afterDelete(){
        Cache::clearGroup('blog');
        
        // delete attached images in blog
        $photoModel = MooCore::getInstance()->getModel('Photo.Photo');
        $photos = $photoModel->find('all', array('conditions' => array('Photo.type' => 'Blog',
            'Photo.target_id' => $this->id)));
        foreach ($photos as $p){
            $photoModel->delete($p['Photo']['id']);
        }
    }
    
    public function getHref($row)
    {
    	$request = Router::getRequest();
    	if (isset($row['id']) && isset($row['title'])){
            return $request->base.'/blogs/view/'.$row['id'].'/'.seoUrl($row['title']);
        }
        else{
            return '';
        }
    		
    	return false;
    }
    
    public function getThumb($row){
        return 'thumbnail';
    }
    
    public function getPrivacy($row){
        if (isset($row['privacy'])){
            return $row['privacy'];
        }
        return false;
    }

    public function updateCounter($id, $field = 'comment_count',$conditions = '',$model = 'Comment') {
        if(empty($conditions)){
            $conditions = array('Comment.type' => 'Blog_Blog', 'Comment.target_id' => $id);
        }
        parent::updateCounter($id,$field, $conditions, $model);
        Cache::delete('blog.blog_view_'.$id,'blog');
    }

    public function getTitle(&$row)
    {
        if (isset($row['title']))
        {
            $row['title'] = htmlspecialchars($row['title']);
            return $row['title'];
        }
        return '';
    }
}
