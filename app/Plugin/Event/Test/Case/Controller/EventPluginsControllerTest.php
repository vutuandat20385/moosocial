<?php
App::uses('Event', 'Event.Model');
require_once APP.'Plugin'. DS .'Video'. DS .'Test'. DS .'Fixture'. DS .'model.php';
class EventPluginsControllerTest extends ControllerTestCase {
    public $autoFixtures = false;
    public $fixtures = array('plugin.video.comment','plugin.video.like',
        'plugin.video.activity','plugin.video.group','plugin.video.activity_fetch_video',
        'plugin.video.i18n','plugin.event.event','core.translate',
        'plugin.video.role','plugin.video.user','plugin.video.category',
        'core.cake_session','plugin.blog.blog','plugin.video.activity_comment',
        'plugin.video.tag','plugin.event.event_rsvp','plugin.blog.blog','plugin.photo.photo','plugin.photo.album');
    public function testIndex(){
        $this->loadFixtures('Activity','Event','EventRsvp','I18n','CakeSession','Role','User','Tag','Blog','Category');
        //$TestModel = new Category();
        //$TestModel->Behaviors->disable('Translate', array('name' => 'nameTranslation'));
        $result = $this->testAction('/events/index');
        debug($result);
    }

    public function testBrowse(){
        $this->loadFixtures('Blog','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/events/browse/home/1');
        debug($result);
    }

    public function testCreate(){
        $this->loadFixtures('Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/events/create/');
        debug($result);
    }

    public function testSave(){
        $this->loadFixtures('Category','Blog','Activity','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $this->Event = ClassRegistry::init('Event');
        $old = $this->Event->find('count');
        $data = array('data' => array(
            'title' => 'Event tiele',
            'category_id' => 6,
            'location' => 'here',
            'from' => '2014-12-23',
            'to' => '2014-12-26',
            'description' => 'party all night',
            'user_id' => 1,
            'type' => 1
        ));
        $this->testAction('/events/save', array('data' => $data));
        $new = $this->Event->find('count');
        $this->assertNotEqual($new, $old);
    }

    public function testView(){
        $this->loadFixtures('ActivityComment','ActivityFetchVideo','Activity','Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/events/view/4');
        debug($result);
    }

    public function testDoRsvp(){
        $this->loadFixtures('ActivityComment','ActivityFetchVideo','Activity','Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $data = array('data' => array(
            'event_id' => 4,
            'rsvp' => 1
        ));
        $result = $this->testAction('/events/do_rsvp', array('data' => $data));
        debug($result);
    }

    public function testInvite(){
        $this->loadFixtures('ActivityComment','ActivityFetchVideo','Activity','Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/events/invite/4');
        debug($result);
    }

    public function testSendInvite(){
        $this->loadFixtures('ActivityComment','ActivityFetchVideo','Activity','Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $data = array('data' => array(
            'friend' => 2,
            'email' => 'test1@gmail.com',
            'event_id' => 4,
        ));
        $result = $this->testAction('/events/sendInvite',array('data' => $data));
        debug($result);
    }

    public function testShowRsvp(){
        $this->loadFixtures('ActivityComment','ActivityFetchVideo','Activity','Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/events/showRsvp/4');
        debug($result);
    }

    public function testDoDelete(){
        $this->loadFixtures('Album','Group','Photo','Like','Comment','ActivityComment','ActivityFetchVideo','Activity','Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $this->Event = ClassRegistry::init('Event');
        $old = $this->Event->find('count');
        $this->testAction('/events/do_delete/4');
        $new = $this->Event->find('count');
        $this->assertEqual($new, $old -1);
    }

    public function testPopular(){
        $this->loadFixtures('ActivityComment','ActivityFetchVideo','Activity','Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/events/popular/num_item_show:5',array('method' => 'requested'));
        debug($result);
    }

    public function testUpcomingAll(){
        $this->loadFixtures('ActivityComment','ActivityFetchVideo','Activity','Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/events/upcomingAll/num_item_show:5',array('method' => 'requested'));
        debug($result);
    }

    /*public function testMyUpcoming(){
        $this->loadFixtures('ActivityComment','ActivityFetchVideo','Activity','Blog','Category','EventRsvp','Event','I18n','CakeSession','Role','User','Tag');
        $result = $this->testAction('/events/upcomming/uid:1',array('method' => 'requested'));
        debug($result);
    }*/
}