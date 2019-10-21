<?php
App::uses('Page', 'Page.Model');
App::uses('Category','Model');
require_once APP.'Plugin'. DS .'Video'. DS .'Test'. DS .'Fixture'. DS .'model.php';
class PageTest extends CakeTestCase{
    public $fixtures = array('plugin.page.core_content','category',
        'core.translate','plugin.page.page','plugin.page.theme','plugin.video.i18n',
        'core.cake_session'
    );

    public function setUp(){
        parent::setUp();
        $this->Page = ClassRegistry::init('Page');
    }

    public function testLoadMenuPages(){
        $this->Page->unbindModel(array('hasMany' => array('CoreContent')));
        $this->Page->unbindModel(array('belongsTo' => array('Theme')));
        $result = $this->Page->loadMenuPages(1);
        debug($result);
    }

    /*public function testSavePage(){
        $result = $this->Page->savePage(1);
        debug($result);
    }*/


}