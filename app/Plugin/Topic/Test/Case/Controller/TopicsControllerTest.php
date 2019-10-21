<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
App::import('Controller', 'Topic.Topics');

class TopicsControllerTest extends ControllerTestCase {

    public $fixtures = array('plugin.topic.topic');

    public function testAjaxDelete() {
        $result = $this->testAction('/topics/ajax_delete/1');
        debug($result);
    }

    public function testAjaxView() {
        $result = $this->testAction('/topics/ajax_view/1');
        debug($result);
    }

    public function testBrowse() {
        $result = $this->testAction('/topics/browse');
        debug($result);
    }

    public function testCreate() {
        $result = $this->testAction('/topics/create');
        debug($result);
    }

    public function testDoDelete() {
        $result = $this->testAction('/topics/do_delete/1');
        debug($result);
    }

    public function testDoLock() {
        $result = $this->testAction('/topics/do_lock/1');
        debug($result);
    }

    public function testDoPin() {
        $result = $this->testAction('/topics/do_pin/1');
        debug($result);
    }

    public function testDoUnlock() {
        $result = $this->testAction('/topics/do_unlock/1');
        debug($result);
    }

    public function testDoUnpin() {
        $result = $this->testAction('/topics/do_unpin/1');
        debug($result);
    }

    public function testIndex() {
        $result = $this->testAction('/topics/index');
        debug($result);
    }

    public function testPopular() {
        $result = $this->testAction('/topics/popular/num_item_show:10');
        debug($result);
    }

    public function testSave() {
        $data = array(
            'attachments' => '',
            'title' => 'topic test unit',
            'category_id' => 1,
            'body' => 'topic body test',
            'tags' => ''
        );

        $result = $this->testAction('/topics/save', array('data' => $data, 'method' => 'POST'));
        debug($result);
    }

    public function testView() {
        $result = $this->testAction('/topics/view/1');
        debug($result);
    }

}
