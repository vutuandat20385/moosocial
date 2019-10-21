<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
class AlbumsControllerTest extends ControllerTestCase {

    public $fixtures = array('plugin.photo.photo', 'plugin.photo.album');


    public function testAjaxBrowse() {
        $result = $this->testAction('/albums/browse/category_id:1/page:1');
        debug($result);
    }

    public function testCreate() {
        $result = $this->testAction('/albums/create/');
        debug($result);
    }

    public function testDoDelete() {
        $result = $this->testAction('/albums/do_delete/1');
        debug($result);
    }

    public function testIndex() {
        $result = $this->testAction('/albums/index/');
        debug($result);
    }

    public function testEdit() {
        $result = $this->testAction('/albums/edit/1');
        debug($result);
    }

    public function testSave() {
        $data = array(
            'tags' => 'album tag'
        );
        $result = $this->testAction('/albums/save/', array('data' => $data, 'method' => 'POST'));
        debug($result);
    }

    public function testView() {
        $result = $this->testAction('/albums/view/1');
        debug($result);
    }

}
