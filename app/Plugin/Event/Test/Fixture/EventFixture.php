<?php
App::uses('Router', 'Routing');
class EventFixture extends CakeTestFixture{
    public $import = array('model'=>'Event.Event');
    public function init() {
        $this->records = array(
            array(
                'id' => 4,
                'category_id' => 5,
                'title' => 'event',
                'description' => 'event description',
                'user_id' => 1,
                'location' => 'here',
                'from' => date('Y-m-d'),
                'from_time' => '12:30 PM',
                'to' => date('Y-m-d'),
                'to_time' => '2:30 PM',
                'created' => date('Y-m-d H:i:s'),
                'type' => 1,
                'photo' => '',
                'event_rsvp_count' => 1,
                'address' => '8 Nguyen Duy'
            ),
        );
        parent::init();
    }
}