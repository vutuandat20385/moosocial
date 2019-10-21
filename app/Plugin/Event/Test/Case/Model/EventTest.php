<?php
App::uses('Event', 'Event.Model');
App::uses('Category','Model');
App::uses('Router', 'Routing');
require_once APP.'Plugin'. DS .'Video'. DS .'Test'. DS .'Fixture'. DS .'model.php';
class EventTest extends CakeTestCase{
    public $fixtures = array('plugin.event.event_rsvp',
        'category','core.translate','plugin.event.event',
        'plugin.video.user','plugin.video.comment',
        'plugin.video.like','plugin.video.tag',
        'plugin.video.role','plugin.video.activity_comment',
        'plugin.video.activity_fetch_video', 'plugin.video.activity',
        'plugin.video.i18n','core.cake_session','plugin.blog.blog','plugin.photo.photo',
        'plugin.photo.album','plugin.video.group'
    );

    public function setUp(){
        parent::setUp();
        $this->Event = ClassRegistry::init('Event.Event');
    }

    public function testGetEvents(){
        $this->Event->unbindModel(array('belongsTo' => array('Category','User')));
        $this->Event->unbindModel(array('hasMany' => array('Activity', 'EventRsvp')));
        $result = $this->Event->getEvents('all',1);
        unset($result[0]['Event']['created']);
        $expected = array(
            0 => array(
                'Event' =>array(
                    'id' => '4',
                    'category_id' => '5',
                    'title' => 'event',
                    'description' => 'event description',
                    'user_id' => '1',
                    'location' => 'here',
                    'from' => date('Y-m-d'),
                    'from_time' => '12:30 PM',
                    'to' => date('Y-m-d'),
                    'to_time' => '2:30 PM',
                    'type' => '1',
                    'photo' => '',
                    'event_rsvp_count' => '1',
                    'address' => '8 Nguyen Duy'
                ),
            )
        );
        $this->assertEqual($result, $expected);
    }

    public function testGetUpcoming(){
        $this->Event->unbindModel(array('belongsTo' => array('Category','User'),'hasMany' => array('Activity', 'EventRsvp')));
        $result = $this->Event->getUpcoming();
        unset($result[0]['Event']['created']);
        $expected = array(
            0 => array(
                'Event' =>array(
                    'id' => '4',
                    'category_id' => '5',
                    'title' => 'event',
                    'description' => 'event description',
                    'user_id' => '1',
                    'location' => 'here',
                    'from' => date('Y-m-d'),
                    'from_time' => '12:30 PM',
                    'to' => date('Y-m-d'),
                    'to_time' => '2:30 PM',
                    'type' => '1',
                    'photo' => '',
                    'event_rsvp_count' => '1',
                    'address' => '8 Nguyen Duy'
                ),
            )
        );
        $this->assertEqual($result, $expected);
    }

    public function testGetPopularEvents(){
        $this->Event->unbindModel(array('belongsTo' => array('Category','User'),'hasMany' => array('Activity', 'EventRsvp')));
        $result = $this->Event->getPopularEvents();
        unset($result[0]['Event']['created']);
        $expected = array(
            0 => array(
                'Event' =>array(
                    'id' => '4',
                    'category_id' => '5',
                    'title' => 'event',
                    'description' => 'event description',
                    'user_id' => '1',
                    'location' => 'here',
                    'from' => date('Y-m-d'),
                    'from_time' => '12:30 PM',
                    'to' => date('Y-m-d'),
                    'to_time' => '2:30 PM',
                    'type' => '1',
                    'photo' => '',
                    'event_rsvp_count' => '1',
                    'address' => '8 Nguyen Duy'
                ),
            )
        );
        $this->assertEqual($result, $expected);    }

    public function testDeleteEvent(){
        $old = $this->Event->find('count');
        $result = $this->Event->deleteEvent(array('Event'=>array('id' => 4)));
        $new = $this->Event->find('count');
        $this->assertEqual($new, $old -1);

    }
}