<?php
/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
App::uses('Album', 'Album.Model');

class AlbumTest extends CakeTestCase {

    public $fixtures = array('plugin.photo.album');

    public function setUp() {
        parent::setUp();
        $this->Album = ClassRegistry::init('Album');
    }

    public function testDeleteAlbum() {
        $this->Album->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title',
            'body' => 'Body',
            'thumbnail' => '',
            'created' => '',
            'last_post' => 1,
            'comment_count' => 1,
            'lastposter_id' => 1,
            'like_count' => 1,
            'group_id' => 1,
            'pinned' => 0,
            'attachment' => '',
            'dislike_count' => 0,
        ));
        $this->Album->deleteAlbum(1);
        $after_delete = $this->Album->findById(1);
        $this->assertEqual($after_delete, array());
    }
    
    public function testDeleteAllAlbum(){
        $this->Album->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title',
            'body' => 'Body',
            'thumbnail' => '',
            'created' => '',
            'last_post' => 1,
            'comment_count' => 1,
            'lastposter_id' => 1,
            'like_count' => 1,
            'group_id' => 1,
            'pinned' => 0,
            'attachment' => '',
            'dislike_count' => 0,
        ));

        $this->Album->deleteAll(true);
        $count_after = $this->Album->find('count');
        $this->assertEqual($count_after, 0);
    }

    public function testCreateAlbum() {
        $count_before = $this->Album->find('count');
        $this->Album->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title',
            'description' => 'Body',
            'created' => '',
            'photo_count' => 1,
            'cover' => '',
            'modified' => '',
            'like_count' => 1,
            'privacy' => 1,
            'type' => '',
            'dislike_count' => 0,
        ));
        $count_after = $this->Album->find('count');
        $this->assertGreaterThan($count_before, $count_after);
    }

    public function testUpdateAlbum() {     
        $this->Album->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title',
            'description' => 'Body',
            'created' => '',
            'photo_count' => 1,
            'cover' => '',
            'modified' => '',
            'like_count' => 1,
            'privacy' => 1,
            'type' => '',
            'dislike_count' => 0,
        ));
        $album_before = $this->Album->findById(1);
        $this->Album->id = 1;
        $this->Album->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title Updated',
            'description' => 'Body Updated',
            'created' => '',
            'photo_count' => 1,
            'cover' => '',
            'modified' => '',
            'like_count' => 1,
            'privacy' => 1,
            'type' => '',
            'dislike_count' => 0,
        ));
        $album_after = $this->Album->findById(1);
        $this->assertNotEqual($album_before, $album_after);
    }

    public function testGetAlbum() {
        $this->Album->deleteAll(true);
        $this->Album->save(array(
            'id' => 1,
            'category_id' => 1,
            'user_id' => 1,
            'title' => 'Title Updated',
            'description' => 'Body Updated',
            'created' => '',
            'photo_count' => 1,
            'cover' => '',
            'modified' => '',
            'like_count' => 1,
            'privacy' => 1,
            'type' => '',
            'dislike_count' => 0,
        ));
        $albums = $this->Album->findById(1);
        $this->assertNotEqual($albums, array());
    }

}

?>