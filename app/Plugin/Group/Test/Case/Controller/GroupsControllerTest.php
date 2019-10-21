<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
class PhotosControllerTest extends ControllerTestCase {

    public $fixtures = array('plugin.group.group', 'plugin.group.group_user', 'plugin.photo.photo');

    public function testIndex() {
        $result = $this->testAction('/groups/index/');
        debug($result);
    }

    public function testBrowse() {
        $result = $this->testAction('/groups/browse/');
        debug($result);
    }

    public function testCreate() {
        $result = $this->testAction('/groups/create/');
        debug($result);
    }

    public function testAjaxSave() {
        $data = array(
            'type' => PRIVACY_PUBLIC
        );
        $result = $this->testAction('/groups/save/', array('data' => $data, 'method' => 'POST'));
        debug($result);
    }

    public function testView() {
        $result = $this->testAction('/groups/view/1');
        debug($result);
    }

    public function testAjaxDetails() {
        $result = $this->testAction('/groups/ajax_details/1');
        debug($result);
    }

    public function testDoRequest() {
        $result = $this->testAction('/groups/do_request/1');
        debug($result);
    }

    public function testAjaxMembers() {
        $result = $this->testAction('/groups/ajax_members/1');
        debug($result);
    }

    public function testAjaxInvite() {
        $result = $this->testAction('/groups/ajax_invite/1');
        debug($result);
    }

    public function testAjaxSendInvite() {
        $result = $this->testAction('/groups/ajax_sendInvite/');
        debug($result);
    }

    public function testAjaxRemoveMember() {
        $result = $this->testAction('/groups/ajax_remove_member/');
        debug($result);
    }

    public function testAjaxChangeAdmin() {
        $result = $this->testAction('/groups/ajax_change_admin/1/make');
        debug($result);
    }

    public function testAjaxRequest() {
        $result = $this->testAction('/groups/ajax_requests/1/');
        debug($result);
    }

    public function testAjaxRespond() {
        $data = array(
            'id' => 1
        );
        $result = $this->testAction('/groups/ajax_respond/', array('data' => $data, 'method' => 'POST'));
        debug($result);
    }

    public function testDoLeave() {
        $result = $this->testAction('/groups/do_leave/1');
        debug($result);
    }

    public function testDoFeature() {
        $result = $this->testAction('/groups/do_feature/1');
        debug($result);
    }

    public function testDoUnfeature() {
        $result = $this->testAction('/groups/do_unfeature/1');
        debug($result);
    }

    public function testDoDelete() {
        $result = $this->testAction('/groups/do_delete/1');
        debug($result);
    }

    public function testPopular() {
        $result = $this->testAction('/groups/popular/num_item_show:10');
        debug($result);
    }

    public function testJoinedGroup() {
        $result = $this->testAction('/groups/joined_group/num_joined_groups:10');
        debug($result);
    }

}
