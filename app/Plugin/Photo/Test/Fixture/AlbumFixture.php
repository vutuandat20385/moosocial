<?php
/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
App::uses('Photo', 'Photo.Model');

class AlbumFixture extends CakeTestFixture {

    public $import = array('model' => 'Photo.Album', 'records' => true);

}
