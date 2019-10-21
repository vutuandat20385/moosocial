<?php
/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
App::uses('Photo', 'Photo.Model');

class PhotoTest extends CakeTestCase {

    public $fixtures = array('plugin.photo.photo', 'plugin.photo.album');

    public function setUp() {
        parent::setUp();
        $this->Photo = ClassRegistry::init('Photo');
    }

    public function testDeletePhoto() {
        $this->Photo->save(array(
            'id' => 1,
            'target_id' => 1,
            'type' => 'album',
            'user_id' => 1,
        ));
        $photo_before = $this->Photo->findById(1);
        $this->Photo->deletePhoto($photo_before);
        $photo_after = $this->Photo->findById(1);
        $this->assertEqual($photo_after, array());
    }
    
    public function testGetPhotoCount(){
        $this->Photo->deleteAll(true);
        $this->Photo->save(array(
            'id' => 1,
            'target_id' => 1,
            'type' => 'album',
            'user_id' => 1,
        ));
        $count = $this->Photo->find('count');
        $this->assertEqual($count, 1);
    }
    
    public function testGetPhoto(){
        $this->Photo->save(array(
            'id' => 1,
            'target_id' => 1,
            'type' => 'album',
            'user_id' => 1,
        ));
        $photos = $this->Photo->getPhotos('album', 1);
        $this->assertNotEqual($photos, array());
    }
}

?>