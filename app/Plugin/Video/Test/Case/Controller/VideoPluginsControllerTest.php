<?php
App::uses('Video', 'Video.Model');
///App::uses('Category', 'Model');
require_once APP.'Plugin'. DS .'Video'. DS .'Test'. DS .'Fixture'. DS .'model.php';
class VideoPluginsControllerTest extends ControllerTestCase {
    public $autoFixtures = false;
    public $fixtures = array('plugin.video.comment','plugin.video.like','plugin.video.activity_comment',
        'plugin.video.activity','plugin.video.group','plugin.video.activity_fetch_video',
        'plugin.video.i18n','plugin.video.video','core.translate',
        'plugin.video.role','plugin.video.user','plugin.video.category',
        'plugin.video.blog', 'core.cake_session','plugin.blog.friend',
        'plugin.video.tag','plugin.video.group_user');
    public function testIndex(){
        $this->loadFixtures('GroupUser','Group','Friend','Video','Category','I18n','CakeSession','Role','User','Blog','Tag');
        $TestModel = new Category();
        $TestModel->Behaviors->disable('Translate', array('name' => 'nameTranslation'));

        //$TestModel->locale = 'eng';
        $this->Video =& ClassRegistry::init('Video');
        //$this->Category =& ClassRegistry::init('Category');
        //$this->loadFixtures('Category');
        //$Model = new Category();
        //$Model->locale = 'eng';
        //$this->Category->Behaviors->disable('Translate');
        $this->Video->unbindModel(array('belongsTo' => array('Category','Group')), false);
        $result = $this->testAction('/videos/index');
        debug($result);
    }
    public function testSave(){
        $this->loadFixtures('Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog');
        $testModel = new Category();
        $testModel->Behaviors->disable('Translate', array('name' => 'nameTranslation'));
        $this->Video =& ClassRegistry::init('Video');
        $this->Video->unbindModel(array('belongsTo' => array('Category','Group','User')), false);
        $videos = array('data'=>array(
            //'id' => 1,
            'user_id' => 1,
            'category_id' => 0,
            'title' => 'Turn from planet X',
            'description' => 'Meaning of turn X',
            'source' => 'youtube',
            'privacy' => 1,
            'thumb' => 'http://i.ytimg.com/vi/UC32mv6z2cM/mqdefault.jpg'
        ));

        $result = $this->testAction('/videos/save',array('data' => $videos));
        debug($result);
    }
    public function testBrowse() {
        $this->loadFixtures('Friend', 'Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog');
        $data = array('type' => 'all','param'=> 1);
        $result = $this->testAction('/videos/browse', array('data' => $data,'method' => 'get'));
        debug($result);
    }
    public function testCreate(){
        $this->loadFixtures('Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog','Tag');
        $result = $this->testAction('/videos/create/1');
        debug($result);
    }

    /*public function testGroupCreate(){
        $this->loadFixtures('Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog','Tag');
        $result = $this->testAction('/videos/group_create/1');
        debug($result);
    }*/

    public function testFetch(){
        $this->loadFixtures('Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog','Tag');
        $data = array('data' => array(
            'source' => 'youtube',
            'url' => 'https://www.youtube.com/watch?v=UC32mv6z2cM',
        ));
        $result = $this->testAction('/videos/fetch',array('data' => $data));
        debug($result);
    }

    public function testAjValidate(){
        $this->loadFixtures('Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog');
        $data = array('data' => array(
            'source' => 'youtube',
            'url' => 'https://www.youtube.com/watch?v=UC32mv6z2cM',
        ));
        $result = $this->testAction('/videos/aj_validate',array('data' => $data));
        debug($result);
    }

    public function testEmbed(){
        $this->loadFixtures('Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog');
        $data = array('data' => array(
            'source' => 'youtube',
            'source_id' => 'https://www.youtube.com/watch?v=UC32mv6z2cM',
        ));
        $result = $this->testAction('/videos/embed',array('data' => $data));
        debug($result);
    }

    public function testView(){
        $this->loadFixtures('Comment','Like','Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog');
        $result = $this->testAction('/videos/view/1');
        debug($result);
    }

    /*public function testAjView(){
        $result = $this->testAction('/videos/ajax_view/1');
        debug($result);
    }*/

    public function testDelete(){
        $this->loadFixtures('Comment','Like','Tag','Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog','GroupUser');
        //$testModel = new GroupUser();
        $result = $this->testAction('/videos/delete/1');
        debug($result);
    }

    public function testPopular(){
        $this->loadFixtures('Comment','Like','Tag','Category','Video','Group','Activity','I18n','CakeSession','Role','User','Blog','GroupUser');
        $result = $this->testAction('/videos/popular/num_item_show:3',array('method' => 'requested'));
        debug($result);
    }}