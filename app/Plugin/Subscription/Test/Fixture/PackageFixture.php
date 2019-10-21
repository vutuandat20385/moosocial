<?php
App::uses('MyPackageFixture','Subscription.Test/Fixture');
class PackageFixture extends MyPackageFixture{
    public $import = array('model'=>'Subscription.Package');
    public function init() {
        $this->records = array(
            array(
                'id' => 1,
                'name' => 'Gold member',
                'description' => 'gold member',
                'role_id' => '2',
                'price' => 10.00,
                'duration' => 1,
                'duration_type' => 'month',
                'warning_email' => 1,
                'warning_email_type' => 'day',
                'enabled' => 1,
                'ordering' => 1,
            ),
        );
        parent::init();
    }
}