<?php
class BlogFixture extends CakeTestFixture{
    public $import = array('model'=>'Blog.Blog');
    public function init() {
        $this->records = array(
            array(
                'id' => 2,
                'user_id' => 1,
                'title' => 'Blog',
                'body' => '<div>redisnfe efent nfa <img src="/moosocial/2.2.0/img/birthday.png" alt="" width="30" height="30" /></div><div><a href="/forum.gamevn.com">forum.gamevn.com</a></div><div>&nbsp;</div><div>&nbsp;</div><div><img src="/moosocial/2.2.0/img/birthday.png" alt="" width="30" height="30" /></div>',
                'thumbnail' => '',
                'created' => date('Y-m-d H:i:s'),
                'like_count' => 2,
                'privacy' => 1,
                'comment_count' => 3,
                'dislike_count' => 1,
            ),
        );
        parent::init();
    }
}