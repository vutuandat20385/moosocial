<?php
class Category extends CakeTestModel{
    public $name = 'Category';
    public function getCategories($type) {
        $categories = Cache::read('category.'.$type);
        if(empty($categories)){
            $categories = $this->find('threaded', array('conditions' => array('Category.type' => $type, 'Category.active' => 1)));
            Cache::write('category.'.$type, $categories);
        }
        return $categories;
    }
    public function getCategoriesList($type, $role_id = null) {
        $re = Cache::read('category.drop_down.'.$type);
        if(empty($re))
        {
            $categories = $this->find('threaded', array('conditions' => array('Category.type' => $type, 'Category.active' => 1)));

            $re = array();
            foreach ($categories as $cat) {
                $removed = false;

                if (!empty($role_id) && !empty($cat['Category']['create_permission'])) {
                    $roles = explode(',', $cat['Category']['create_permission']);

                    if (!in_array($role_id, $roles))
                        $removed = true;
                }

                if (!$removed) {
                    if ($cat['Category']['header']) {
                        $subs = array();
                        foreach ($cat['children'] as $subcat)
                            $subs[$subcat['Category']['id']] = $subcat['Category']['name'];

                        $re[$cat['Category']['name']] = $subs;
                    } else
                        $re[$cat['Category']['id']] = $cat['Category']['name'];
                }
            }
            Cache::write('category.drop_down.'.$type, $re);
        }
        return $re;
    }
}

class GroupUser extends CakeTestModel{
    public $name = 'GroupUser';
    public function getUsersList($group_id, $status = null) {
        $cond = array('group_id' => $group_id);

        if (!empty($status))
            $cond['status'] = $status;

        $users = $this->find('list', array('conditions' => $cond,
            'fields' => array('GroupUser.user_id')
        ));

        return $users;
    }
}
class CoreContent extends CakeTestModel{
    public $name = 'CoreContent';
}

class Blog extends CakeTestModel{
    public $name = 'Blog';
    public $actsAs = array(
        'Activity' => array(
            'type' => 'user',
            'action_afterCreated' => 'blog_create',
            'item_type' => 'Blog_Blog',
            'query' => 1,
            'params' => 'item'
        ),

        'MooUpload.Upload' => array(
            'thumbnail' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}blogs{DS}{field}{DS}',
            )
        )
    );
    public $belongsTo = array( 'User'  => array('counterCache' => true	));

    public $hasMany = array( 'Comment' => array(
        'className' => 'Comment',
        'foreignKey' => 'target_id',
        'conditions' => array('Comment.type' => 'Blog_Blog'),
        'dependent'=> true
    ),
        'Like' => array(
            'className' => 'Like',
            'foreignKey' => 'target_id',
            'conditions' => array('Like.type' => 'Blog_Blog'),
            'dependent'=> true
        ),
        'Tag' => array(
            'className' => 'Tag',
            'foreignKey' => 'target_id',
            'conditions' => array('Tag.type' => 'Blog_Blog'),
            'dependent'=> true
        )
    );

    public $order = 'Blog.id desc';
    public function getBlogs( $type = null, $param = null, $page = 1, $limit = RESULTS_LIMIT, $subject_type = '', $subject_id = 0, $friend_list = '',$role_id = null)
    {
        $pp = Configure::read('Blog.blog_item_per_pages');
        if(!empty($pp))
            $limit = $pp;
        $cond = array();

        switch ( $type )
        {
            case 'friends':
                if ( $param )
                {
                    App::import('Model', 'Friend');
                    $friend = new Friend();
                    $friends = $friend->getFriends( $param );
                    if($role_id == ROLE_ADMIN){
                        $cond = array( 'Blog.user_id' => $friends);
                    }
                    else{
                        $cond = array( 'Blog.user_id' => $friends, 'Blog.privacy <> ' . PRIVACY_ME );
                    }
                }
                break;

            case 'home':
            case 'my':
                if ( $param )
                    $cond = array( 'Blog.user_id' => $param );

                break;

            case 'user':
                if ( $param ){
                    if($role_id == ROLE_ADMIN) //viewer is admin or owner himself
                        $cond = array( 'Blog.user_id' => $param);
                    elseif(!empty($friend_list)) //viewer is a friend
                        $cond = array( 'Blog.user_id' => $param, 'Blog.privacy <> '.PRIVACY_ME );
                    else // normal viewer
                        $cond = array( 'Blog.user_id' => $param, 'Blog.privacy' => PRIVACY_EVERYONE );
                }
                break;

            case 'search':
                if ( $param ){
                    if($role_id == ROLE_ADMIN)
                        $cond = array( 'MATCH(Blog.title, Blog.body) AGAINST(? IN BOOLEAN MODE)' => urldecode($param));
                    else
                        $cond = array( 'MATCH(Blog.title, Blog.body) AGAINST(? IN BOOLEAN MODE)' => urldecode($param), 'Blog.privacy' => PRIVACY_EVERYONE );
                }
                break;

            default:
                if($role_id == ROLE_ADMIN){
                    $cond = array();
                }
                else{
                    $cond = array(
                        'OR' => array(
                            array(
                                'Blog.privacy' => PRIVACY_EVERYONE,
                            ),
                            array(
                                'Blog.user_id' => $param
                            ),
                            array(
                                'Find_In_Set(Blog.user_id,"'.$friend_list.'")',
                                'Blog.privacy' => PRIVACY_FRIENDS
                            )
                        ),
                    );
                }
        }
        if(!empty($subject_type))
            $cond['Blog.subject_type'] = $subject_type;
        if(!empty($subject_id))
            $cond['Blog.subject_id'] = $subject_id;

        $blogs = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
        return $blogs;
    }

    public function getPopularBlogs( $limit = 5, $days = null )
    {
        $cond = array('Blog.privacy' => PRIVACY_EVERYONE);

        if ( !empty( $days ) )
            $cond['DATE_SUB(CURDATE(),INTERVAL ? DAY) <= Blog.created'] = intval($days);

        $blogs = $this->find( 'all', array( 'conditions' => $cond,
            'order' => 'Blog.like_count desc',
            'limit' => intval($limit)
        ) );

        return $blogs;
    }

    public function deleteBlog( $id )
    {
        $this->delete( $id );

        // delete activity
        App::import('Model', 'Activity');
        $activity = new Activity();
        $activity->deleteAll( array( 'Activity.item_type' => 'Blog_Blog', 'Activity.item_id' => $id ), true, true );
    }

    public function getBlogSuggestion($q, $limit = RESULTS_LIMIT){
        $cond = array('Blog.title LIKE "' . $q . '%"','Blog.privacy' => PRIVACY_EVERYONE );

        $blogs = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => 1 ) );
        return $blogs;
    }
}
class Event extends CakeTestModel{
    public $name = 'Event';
    public $findMethods = array(
        'translated' => true
    );
    public $actsAs = array(
        'Activity' => array(
            'type' => 'user',
            'action_afterCreated'=>'event_create',
            'item_type'=>'Event_Event',
            'query'=>1,
            'params' => 'item'
        ),
        'MooUpload.Upload' => array(
            'photo' => array(
                'path' => '{ROOT}webroot{DS}uploads{DS}events{DS}{field}{DS}',
            )
        )

    );
    public $belongsTo = array( 'User',
        'Category' => array( 'counterCache' => 'item_count',
            'counterScope' => array( 'Event.type' => PRIVACY_PUBLIC,
                'Category.type' => 'Event',
                'Event.to >= CURDATE()' ) )
    );

    public $hasMany = array( 'Activity' => array(
        'className' => 'Activity',
        'foreignKey' => 'target_id',
        'conditions' => array('Activity.type' => 'Event_Event'),
        'dependent'=> true
    ),
        'EventRsvp' => array(
            'className' => 'Event.EventRsvp',
            'dependent'=> true
        ),
    );

    public $order = 'Event.from asc';

    public $validate = array(
        'title' => 	array(
            'rule' => 'notBlank',
            'message' => 'Title is required'
        ),
        'category_id' =>     array(
            'rule' => 'notBlank',
            'message' => 'Category is required'
        ),
        'location' => array(
            'rule' => 'notBlank',
            'message' => 'Location is required'
        ),
        'from' => 	array(
            'rule' => array('date','ymd'),
            'message' => 'From is not a valid date format (yyyy-mm-dd)',
            'allowEmpty' => false
        ),
        'to' => 	array(
            'rule' => array('date','ymd'),
            'message' => 'To is not a valid date format (yyyy-mm-dd)',
            'allowEmpty' => false
        ),
        'description' => array(
            'rule' => 'notBlank',
            'message' => 'Description is required'
        ),
        'user_id' => array( 'rule' => 'notBlank')
    );
    public function initFields() {
        $res[$this->name] = array_fill_keys(array_keys($this->schema()), '');
        return $res;
    }

    function _findTranslated($state, $query, $results = array()) {
        if ($state == 'before') {
            return array_merge($query, array(
                //'fields' => array('id', 'name'),
                //'recursive' => -1
            ));
        } elseif ($state == 'after') {
            if (empty($results)) {
                return $results;
            }
            //  pr($results);
            // get the model's belongs to relation model names
            $belongsTo = Set::extract($this->belongsTo, '/@*');

            if (!empty($belongsTo) && isset($belongsTo[0]) && is_array($belongsTo[0]))
                $belongsTo = $belongsTo[0];

            if (!empty($belongsTo))
                foreach ($results as &$result) {
                    foreach ($belongsTo as $modelName) {
                        if (isset($result[$modelName]) &&
                            isset($result[$modelName]['id']) &&
                            !empty($result[$modelName]['id'])) {

                            $data = $this->$modelName->find('first', array(
                                'conditions' => array(
                                    $modelName . '.id' => $result[$modelName]['id']
                                ),
                                'recursive' => -1
                            ));

                            if (!empty($data))
                                $result[$modelName] = $data[$modelName];
                        }
                    }
                }

            return $results;
        }
    }
    public function getEvents( $type = null, $param = null, $page = 1 ,$role_id = null, $event_id = null)
    {
        $pp = Configure::read('Event.event_item_per_pages');
        $limit = (!empty($pp)) ? $pp : RESULTS_LIMIT;
        $cond = array();

        switch ( $type )
        {
            case 'category':
                if($role_id == ROLE_ADMIN){
                    $cond = array(
                        'Event.category_id' => $param,
                        'Event.to >= CURDATE()'
                    );
                }else{
                    $cond = array( 'Event.category_id' => $param,
                        'Event.to >= CURDATE()',
                        'Event.type' => PRIVACY_PUBLIC
                    );
                }
                break;

            // Get all past events that have public view access
            case 'past':
                if($role_id == ROLE_ADMIN){
                    $cond = array(
                        'Event.to < CURDATE()'
                    );
                }else{
                    $cond = array( 'Event.to < CURDATE()',
                        'Event.type' => PRIVACY_PUBLIC
                    );
                }
                break;

            case 'search':
                if ( $param )
                    $cond = array( 'Event.title LIKE "'.$param.'%"');

                break;

            default:
                if($role_id == ROLE_ADMIN){
                    $cond = array();
                }
                else{
                    $cond = array(
                        'OR' => array(
                            array(
                                'Event.type' => PRIVACY_PUBLIC,
                            ),
                            array(
                                'Event.user_id' => $param
                            ),
                            array(
                                'Find_In_Set(Event.id,"'.$event_id.'")',
                            )
                        ),
                    );
                }
        }
        if($type === null || !in_array($type,array('category','past','search')))
            $events = Cache::read('event.'.($type === null ? 'all' : $type).'.user.'.$param.'.page.'.$page.'.role.'.$role_id,'event');
        elseif($type != 'search')
            $events = Cache::read('event.'.$type.'.'.$param.'.page.'.$page.'.role.'.$role_id,'event');
        if(empty($events)){
            $events = $this->find( 'all', array( 'conditions' => $cond, 'limit' => $limit, 'page' => $page ) );
            if($type === null || !in_array($type,array('category','past','search')))
                Cache::write('event.'.($type === null ? 'all' : $type).'.user.'.$param.'.page.'.$page.'.role.'.$role_id,$events,'event');
            elseif($type != 'search')
                Cache::write('event.'.$type.'.'.$param.'.page.'.$page.'.role.'.$role_id,$events,'event');
        }

        return $events;
    }
    public function getUpcoming($limit = 5){
        $cond = array( 'Event.to >= CURDATE()',
            'Event.type' => PRIVACY_PUBLIC
        );
        $events = $this->find( 'all', array( 'conditions' => $cond, 'limit' => intval($limit) ) );
        return $events;
    }

    public function getPopularEvents( $limit = 5, $days = null )
    {
        $cond = array( 'Event.to >= CURDATE()', 'Event.type' => PRIVACY_PUBLIC );

        if ( !empty( $days ) )
            $cond['DATE_ADD(CURDATE(),INTERVAL ? DAY) >= Event.to'] = intval($days);

        $events = $this->find( 'all', array( 'conditions' => $cond,
            'order' => 'Event.event_rsvp_count desc',
            'limit' => intval($limit)
        ) 	);
        return $events;
    }

    public function deleteEvent( $event )
    {
        $this->delete( $event['Event']['id'] );
    }

    public function countEventByCategory($category_id){
        $num_events = $this->find('count',array(
            'conditions' => array(
                'Event.category_id' => $category_id,
            )
        ));
        return $num_events;
    }
}