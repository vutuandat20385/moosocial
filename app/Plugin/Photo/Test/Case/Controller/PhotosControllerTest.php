<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
class PhotosControllerTest extends ControllerTestCase {

     public $fixtures = array('plugin.photo.photo', 'plugin.photo.album');

    public function testAjaxBrowse() {
        $result = $this->testAction('/photos/ajax_browse');
        debug($result);
    }

    public function testAjaxFetch() {
        $data = array('type' => 'album', 'target_id' => '4', 'page' => 1);
        $result_album = $this->testAction('/photos/ajax_fetch/', array('data' => $data, 'method' => 'POST'));
        debug($result_album);
        
        $data['type'] = 'group';
        $result_group = $this->testAction('/photos/ajax_fetch/', array('data' => $data, 'method' => 'POST'));
        debug($result_group);
        
        $data['type'] = 'user';
        $result_user = $this->testAction('/photos/ajax_fetch/', array('data' => $data, 'method' => 'POST'));
        debug($result_user);
    }

    public function testAjaxFriendList() {
        $result = $this->testAction('/photos/ajax_friends_list/');
        debug($result);
    }

    public function testAjaxRemove() {
        $result = $this->testAction('/photos/ajax_remove/photo_id:1/next_photo:2');
        debug($result);
    }

    public function testAjaxRemoveTag() {
        $data = array(
            'tag_id' => 1
        );
        $result = $this->testAction('/photos/ajax_remove_tag/', array('data' => $data, 'method' => 'POST'));
        debug($result);
    }

    public function testAjaxTag() {
        $data = array(
            'uid' => 1,
            'photo_id' => 1,
            'value' => 'abc tag',
            'style' => 'photo'
        );
        $result = $this->testAction('/photos/ajax_tag/', array('data' => $data, 'method' => 'POST'));
        debug($result);
    }

    public function testAjaxUpload() {
        $result = $this->testAction('/photos/ajax_upload/');
        debug($result);
    }

    public function testAjaxView() {
        $result = $this->testAction('/photos/ajax_view/1/');
        debug($result);
    }

    public function testIndex() {
        $result = $this->testAction('/photos/index/');
        debug($result);
    }

    public function testUpload() {
        $result = $this->testAction('/photos/upload/');
        debug($result);
    }

    public function testView() {
        $result = $this->testAction('/photos/view/31');
        debug($result);
    }

}
