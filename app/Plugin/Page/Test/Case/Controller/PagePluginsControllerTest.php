<?php
App::uses('Page', 'Page.Model');
///App::uses('Category', 'Model');
require_once APP.'Plugin'. DS .'Video'. DS .'Test'. DS .'Fixture'. DS .'model.php';
class PagePluginsControllerTest extends ControllerTestCase {
    public $autoFixtures = false;
    public $fixtures = array('plugin.video.comment','plugin.video.like',
        'plugin.video.activity','plugin.video.group','plugin.video.activity_fetch_video',
        'plugin.video.i18n','plugin.page.page','core.translate',
        'plugin.video.role','plugin.video.user','plugin.video.category',
        'core.cake_session','plugin.blog.blog','plugin.video.activity_comment',
        'plugin.video.tag','plugin.page.theme');
    public function testDisplay(){
        /*$this->loadFixtures('Page','I18n');
        $result = $this->testAction('/pages/display');
        debug($result);*/
    }


}