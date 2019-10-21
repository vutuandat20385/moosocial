<?php
App::uses('Blog','Blog.Model');
App::uses('Mysql', 'Model/Datasource/Database');
require_once APP.'Plugin'. DS .'Video'. DS .'Test'. DS .'Fixture'. DS .'model.php';
class BlogTest extends CakeTestCase{
    public $fixtures = array('core.translate','plugin.blog.blog',
        'plugin.video.user','plugin.video.comment','plugin.video.like','plugin.video.tag',
        'plugin.video.role','plugin.video.activity_comment',
        'plugin.video.activity_fetch_video','plugin.video.activity');
    public function setUp(){
        parent::setUp();
        $this->Blog = ClassRegistry::init('Blog');
    }
    public function testGetBlogs(){
        $this->Blog->unbindModel(array('belongsTo' => array('User')));
        $this->Blog->unbindModel(array('hasMany' => array('Comment','Like','Tag')));
        $result = $this->Blog->getBlogs('all',1);
        unset($result[0]['Blog']['created']);
        $expected = array(
            0 => array(
                'Blog' =>array(
                    'id' => '2',
                    'user_id' => '1',
                    'title' => 'Blog',
                    'body' => '<div>redisnfe efent nfa <img src="/moosocial/2.2.0/img/birthday.png" alt="" width="30" height="30" /></div><div><a href="/forum.gamevn.com">forum.gamevn.com</a></div><div>&nbsp;</div><div>&nbsp;</div><div><img src="/moosocial/2.2.0/img/birthday.png" alt="" width="30" height="30" /></div>',
                    'thumbnail' => '',
                    'like_count' => '2',
                    'privacy' => '1',
                    'comment_count' => '3',
                    'dislike_count' => '1',
                ),
            )
        );
        $this->assertEquals($expected, $result);
    }

    public function testGetPopularBlogs(){
        $this->Blog->unbindModel(array('belongsTo' => array('User')));
        $this->Blog->unbindModel(array('hasMany' => array('Comment','Like','Tag')));
        $result = $this->Blog->getPopularBlogs(5,30);
        unset($result[0]['Blog']['created']);
        $expected = array(
            0 => array(
                'Blog' =>array(
                    'id' => '2',
                    'user_id' => '1',
                    'title' => 'Blog',
                    'body' => '<div>redisnfe efent nfa <img src="/moosocial/2.2.0/img/birthday.png" alt="" width="30" height="30" /></div><div><a href="/forum.gamevn.com">forum.gamevn.com</a></div><div>&nbsp;</div><div>&nbsp;</div><div><img src="/moosocial/2.2.0/img/birthday.png" alt="" width="30" height="30" /></div>',
                    'thumbnail' => '',
                    'like_count' => '2',
                    'privacy' => '1',
                    'comment_count' => '3',
                    'dislike_count' => '1',
                ),
            )
        );
        $this->assertEquals($expected, $result);
    }

    public function testDeleteBlog(){
        $old_result = $this->Blog->find('count');
        $this->Blog->deleteBlog(2);
        $new_result = $this->Blog->find('count');
        $this->assertNotEqual($new_result, $old_result);
    }
}