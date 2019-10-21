<?php
App::uses('Subscribe', 'Subscription.Model');
App::uses('Category','Model');
class SubscribeTest extends CakeTestCase{
    public $fixtures = array('core.translate','plugin.video.i18n','plugin.video.video',
        'plugin.video.user','plugin.video.activity','plugin.video.comment',
        'plugin.video.like','plugin.video.tag','plugin.subscription.subscribe',
        'plugin.subscription.package'
    );
    public function setUp(){
        parent::setUp();
        $this->Subscribe = ClassRegistry::init('Subscribe');
    }
    public function testSubscribeStatus(){
        $result = $this->Subscribe->subscribeStatus();
        $expected = array('initial' => 'Initial', 'trial' => 'Trial', 'pending' => 'Pending', 'active' => 'Active',
            'expired' => 'Expired', 'failed' => 'Failed', 'refunded' => 'Refunded');
        $this->assertEqual($result,$expected);
    }
    public function testIsIdExist(){
        $result = $this->Subscribe->isIdExist(1);
        $this->assertEqual($result,true);
    }
    public function testIsBelongToPackage(){
        $result = $this->Subscribe->isBelongToPackage(1);
        $this->assertEqual($result,true);
    }
}