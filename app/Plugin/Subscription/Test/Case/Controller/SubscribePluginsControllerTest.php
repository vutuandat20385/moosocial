<?php
App::uses('Subscribe', 'Subscription.Model');
App::uses('SubscribesController', 'Subscription.Controller');
///App::uses('Category', 'Model');
App::import('Controller', 'Subscription.Subscribes');
require_once APP.'Plugin'. DS .'Video'. DS .'Test'. DS .'Fixture'. DS .'model.php';

class TestSubscribesController extends SubscribesController {
    public $redirectUrl = null;
    public function redirect($url, $status = null, $exit = true) {
        $this->redirectUrl = $url;
    }
}

class SubscribePluginsControllerTest extends ControllerTestCase{
    public $autoFixtures = false;
    /*public $fixtures = array('plugin.video.comment','plugin.video.like','plugin.video.activity_comment',
        'plugin.video.activity','plugin.video.group','plugin.video.activity_fetch_video',
        'plugin.video.i18n','plugin.video.video','core.translate',
        'plugin.video.role','plugin.video.user','plugin.video.category',
        'plugin.video.blog', 'core.cake_session',
        'plugin.video.tag','plugin.video.group_user');*/
    public $fixtures = array('plugin.video.like','plugin.video.comment','plugin.subscription.subscribe','plugin.subscription.package',
        'plugin.subscription.gateway','plugin.subscription.currency','plugin.video.i18n',
        'core.cake_session','plugin.video.role','plugin.video.user','plugin.video.blog',
        'plugin.subscription.setting','plugin.subscription.setting_group','core.translate',
        'plugin.subscription.language','plugin.page.theme','plugin.video.tag','plugin.page.page',
        'plugin.subscription.transaction','plugin.page.core_content','plugin.subscription.plugin'
        );

    //public $Subscribe;
    /*function startTest() {
        $this->Subscribes = new TestSubscribesController();
        $this->Subscribes->constructClasses();
    }*/
    public function setUp(){
        parent::setUp();
    }

    public function tearDown() {
        CakeSession::delete('Auth.User');
        CakeSession::delete('admin_login');
        parent::tearDown();
    }

    public function testAdminIndex(){
        $this->loadFixtures('Package','Like','Comment','Subscribe','Plugin','Currency','Gateway','Blog','Page','Theme','CoreContent', 'I18n','CakeSession','Role','User');
        CakeSession::write('Auth.User', array(
            'role_id' => 1,
            'id' => 1,
        ));
        CakeSession::write('admin_login', 1);
        $url = Router::url(array('admin' => true,'plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'index'));
        $options = array(
            'return' => 'vars',
            'method' => 'get'
        );
        //$result = $this->testAction('/subscription/subscribes/admin_index/1',array('return' => "vars" ));
        $result = $this->testAction($url, $options);
        $this->assertNotEmpty($result);
    }

    /*public function testAdminCreate(){
        $this->loadFixtures('CoreContent', 'Theme', 'Page', 'Currency','Gateway', 'Tag', 'Blog','I18n','CakeSession','Role','User','Tag');
        CakeSession::write('Auth.User', array(
            'role_id' => 1,
            'id' => 1,
        ));
        CakeSession::write('admin_login', 1);

        $url = Router::url(array('admin' => true, 'plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'create'));
        $options = array(
            'return' => 'vars',
            'method' => 'get'
        );
        //$this->Subscribe = ClassRegistry::init('Subscribe');
        //$old = $this->Subscribe->find('count');
        $result = $this->testAction($url, $options);
        //$new = $this->Subscribe->find('count');
        //$this->assertNotEqual($new, $old);
        $this->assertNotEmpty($result);
    }
*/
    public function testAdminAjaxView(){
        $this->loadFixtures('Package','Subscribe','Theme', 'Page', 'Currency','Gateway', 'Tag', 'Blog','I18n','CakeSession','Role','User','Tag');
        CakeSession::write('Auth.User', array(
            'role_id' => 1,
            'id' => 1,
        ));
        CakeSession::write('admin_login', 1);
        $url = Router::url(array('admin' => true, 'plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'ajax_view','1'));
        $options = array(
            'return' => 'vars',
            'method' => 'get'
        );
        $result = $this->testAction($url, $options);
        $this->assertNotEmpty($result);
    }
    public function testAdminAjaxEdit(){
        $this->loadFixtures('Package','Subscribe','Theme', 'Page', 'Currency','Gateway', 'Tag', 'Blog','I18n','CakeSession','Role','User','Tag');
        CakeSession::write('Auth.User', array(
            'role_id' => 1,
            'id' => 1,
        ));
        CakeSession::write('admin_login', 1);
        $url = Router::url(array('admin' => true, 'plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'ajax_edit','1'));
        $options = array(
            'return' => 'vars',
            'method' => 'get'
        );
        $result = $this->testAction($url, $options);
        $this->assertNotEmpty($result);
    }

    public function testAdminAjaxSave(){
        $this->loadFixtures('Subscribe','Theme', 'Page', 'Currency','Gateway', 'Tag', 'Blog','I18n','CakeSession','Role','User','Tag');
        CakeSession::write('Auth.User', array(
            'role_id' => 1,
            'id' => 1,
        ));
        CakeSession::write('admin_login', 1);
        $url = Router::url(array('admin' => true, 'plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'ajax_save'));
        $options = array(
            'data' => array(
                'Subscribe' => array(
                    'id' => 1,
                    'user_id' => 1,
                    'package_id' => 1,
                    'status' => 'pending',
                    'active' => '1',
                    'creation_date' => date('Y-m-d H:i:s'),
                    'payment_date' => date('Y-m-d H:i:s'),
                    'modified_date' => date('Y-m-d H:i:s'),
                    'expiration_date' => '2015-09-19 02:06:12',
                    'onetime' => 1,
                    'notes' => 'this is a note mk2',
                    'gateway_id' => 1,
                    'is_warning_email_sent' => 1,
                )
            )
        );
        //$subscribe =& ClassRegistry::init('Subscribe');
        //$old = $subscribe->findById(1);
        $result = $this->testAction($url, $options);
        debug($result);
        //$new = $this->Subscribe->find('count');
        //$this->assertNotEqual($new['Subscribe']['status'], $old['Subscribe']['status']);
    }

    public function testGateway(){
        $this->loadFixtures('CoreContent','Package','Theme', 'Page', 'Currency','Gateway', 'Tag', 'Blog','I18n','CakeSession','Role','User','Tag');
        $url = Router::url(array('plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'gateway'));
        $options = array(
            'return' => 'vars',
            'method' => 'get'
        );
        $result = $this->testAction($url, $options);
        $this->assertNotEmpty($result);
    }
    public function testProcess(){
        $this->loadFixtures('Transaction', 'Subscribe', 'Package', 'Theme', 'Page', 'Currency','Gateway','Blog','I18n','CakeSession','Role','User','Tag');
        CakeSession::write('allow_process', 1);
        CakeSession::write('package_id', 1);
        $url = Router::url(array('plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'process'));
        $options = array(
            'return' => 'vars',
            'method' => 'post',
            'data' => array(
                'gateway_id' => 1
            )
        );
        $result = $this->testAction($url,$options);
        $this->assertNotEmpty($result);
    }
}