<?php
App::uses('MyPageFixture','Page.Test/Fixture');
class PageFixture extends MyPageFixture{
    public $import = array('model'=>'Page.Page','records'=>true);
}