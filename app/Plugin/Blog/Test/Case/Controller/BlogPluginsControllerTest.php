<?php
App::uses('Blog', 'Blog.Model');
///App::uses('Category', 'Model');
require_once APP.'Plugin'. DS .'Video'. DS .'Test'. DS .'Fixture'. DS .'model.php';
class BlogPluginsControllerTest extends ControllerTestCase {
    public $autoFixtures = false;
    public $fixtures = array('plugin.video.comment','plugin.video.like','plugin.video.activity_comment',
        'plugin.video.activity','plugin.video.group','plugin.video.activity_fetch_video',
        'plugin.video.i18n','plugin.blog.blog','core.translate',
        'plugin.video.role','plugin.video.user','plugin.video.category',
        'core.cake_session','plugin.blog.friend',
        'plugin.video.tag','plugin.video.group_user','plugin.photo.photo','plugin.photo.album');
    public function testIndex(){
        $this->loadFixtures('Like','Comment','Friend','Blog','I18n','CakeSession','Role','User','Tag');
        //$TestModel = new Category();
        //$TestModel->Behaviors->disable('Translate', array('name' => 'nameTranslation'));
        $result = $this->testAction('/blogs/index');
        debug($result);
    }

    public function testBrowse(){
        $this->loadFixtures('Like','Comment','Friend','Blog','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/blogs/browse/all');
        debug($result);
    }

    public function testApiBrowse(){
        $this->loadFixtures('Blog','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/blogs/api_browse/home/1');
        debug($result);
    }

    public function testCreate(){
        $this->loadFixtures('Blog','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/blogs/create/2');
        debug($result);
    }

    public function testSave(){
        $this->loadFixtures('ActivityComment','Group', 'Photo', 'Album', 'Blog','I18n','CakeSession','Role','User','Tag', 'Activity');
        $this->Blog = ClassRegistry::init('Blog');
        $old = $this->Blog->find('count');
        $data = array('data'=>array(
            'title' => 'Blog testsuit',
            'body' => 'abc dfc',
            'privacy' => 1,
            'tags' => ''
        ));
        $this->testAction('/blogs/save', array('data' => $data));
        $new = $this->Blog->find('count');
        $this->assertNotEqual($new, $old);
    }

    public function testView(){
        $this->loadFixtures('Blog','I18n','CakeSession','Role','User','Tag', 'Friend','Comment','Like');
        $result = $this->testAction('/blogs/view/2');
        debug($result);
    }

    public function testDelete(){
        $this->loadFixtures('ActivityComment','Group','Photo','Album','Blog','I18n','CakeSession','Role','User','Tag', 'Friend','Comment','Like');
        $this->Blog = ClassRegistry::init('Blog');
        $old = $this->Blog->find('count');
        $this->testAction('/blogs/delete/2');
        $new = $this->Blog->find('count');
        $this->assertEqual($new, $old - 1);
    }

    public function testPopular(){
        $this->loadFixtures('Blog','I18n','CakeSession','Role','User','Tag', 'Friend','Comment','Like');
        $result = $this->testAction('/blogs/popular/num_item_show:3',array('method'=>'requested'));
        debug($result);
    }
}