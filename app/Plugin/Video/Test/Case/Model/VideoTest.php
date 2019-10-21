<?php
App::uses('Video', 'Video.Model');
App::uses('Category','Model');
class VideoTest extends CakeTestCase{
    public $fixtures = array('core.translate','plugin.video.i18n','plugin.video.video','plugin.video.user','plugin.video.activity','plugin.video.comment','plugin.video.like','plugin.video.tag');

    public function setUp(){
        parent::setUp();
        $this->Video = ClassRegistry::init('Video.Video');
        $this->Video->unbindModel(array('belongsTo' => array('Category','Group')));
        //$this->$model->unbindModel(array('belongsTo' => array('Category')));
        //$this->loadFixtures('Translate', 'TranslatedItem');
        //$Model = new TranslatedItem();
        //$Model->locale = 'eng';
    }
    public function testGetVideos(){
        $Model = ClassRegistry::init('Category');
        $Model->locale = 'eng';
        $this->Video->unbindModel(array('belongsTo' => array('Group')));
        $result = $this->Video->getVideos('all',1);
        debug($result);
    }
    public function testFetchVideo(){
        $result = $this->Video->fetchVideo('youtube','https://www.youtube.com/watch?v=oninzEKKFH0');
        debug($result);
    }

    public function testParseStatus(){
        $arr = array(
            'Activity' => array(
                'content' => 'https://www.youtube.com/watch?v=UC32mv6z2cM',
            ),
            'Content' => array()
        );
        $this->Video->parseStatus($arr);
        debug($arr);
    }

    public function testGetPopularVideos(){
        //$this->Video->unbindModel(array('belongsTo' => array('Category')));
        $result = $this->Video->getPopularVideos(5, 30);
        debug($result);
    }
    public function testDeleteVideo(){
        //$Model = new TranslatedItem();
        //$Model->locale = 'eng';
        //$this->loadFixtures('Category');
        $this->Video->unbindModel(array('belongsTo' => array('User')));
        $first_count = $this->Video->find('count');
        $videos = array('Video'=>array('id' => 5));
        //$this->Video->unbindModel(array('belongsTo' => array('Category','Group')));
        $this->Video->deleteAll(array('Video.id'=>5), false, false);
        $this->Video->unbindModel(array('belongsTo' => array('Category','Group')));
        $second_count = $this->Video->find('count');
        $this->assertNotEqual($second_count, $first_count);
    }
}