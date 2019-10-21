<?php
App::uses('MyCoreContentFixture','Page.Test/Fixture');
class CoreContentFixture extends MyCoreContentFixture{
    public $import = array('model'=>'CoreContent','records'=>true);
}