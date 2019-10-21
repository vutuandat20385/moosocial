<?php
App::uses('MySubscribeFixture','Subscription.Test/Fixture');
class SubscribeFixture extends MySubscribeFixture{
    public $import = array('model'=>'Subscription.Subscribe');
    public function init() {
        $this->records = array(
            array(
                'id' => 1,
                'user_id' => 1,
                'package_id' => 1,
                'status' => 'active',
                'active' => '1',
                'creation_date' => date('Y-m-d H:i:s'),
                'payment_date' => date('Y-m-d H:i:s'),
                'modified_date' => date('Y-m-d H:i:s'),
                'expiration_date' => '2015-09-10 02:06:12',
                'onetime' => 1,
                'notes' => 'this is a note',
                'gateway_id' => 1,
                'is_warning_email_sent' => 1,
                /*
                'body' => '<div>redisnfe efent nfa <img src="/moosocial/2.2.0/img/birthday.png" alt="" width="30" height="30" /></div><div><a href="/forum.gamevn.com">forum.gamevn.com</a></div><div>&nbsp;</div><div>&nbsp;</div><div><img src="/moosocial/2.2.0/img/birthday.png" alt="" width="30" height="30" /></div>',
                'thumbnail' => '',
                'created' => date('Y-m-d H:i:s'),
                'like_count' => 2,
                'privacy' => 1,
                'comment_count' => 3,
                'dislike_count' => 1,*/
            ),
        );
        parent::init();
    }
}