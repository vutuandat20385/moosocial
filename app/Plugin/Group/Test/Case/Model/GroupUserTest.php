<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
App::uses('GroupUser', 'Group.Model');

class GroupUserTest extends CakeTestCase {

    public $fixtures = array('plugin.group.group', 'plugin.group.group_user');

    public function setUp() {
        parent::setUp();
        $this->GroupUser = ClassRegistry::init('GroupUser');
    }

    public function testGetGroups() {
        $this->GroupUser->deleteAll(true);
        $this->GroupUser->save(array(
            'group_id' => 1,
            'user_id' => 1,
            'status' => 1
        ));
       
        $groups = $this->GroupUser->getGroups('home', 1);
        $this->assertNotEqual($groups, array());
        
        $groups = $this->GroupUser->getGroups('home', 3);
        $this->assertEqual($groups, array());
        
        $groups = $this->GroupUser->getGroups('my', 1);
        $this->assertNotEqual($groups, array());
        
        $groups = $this->GroupUser->getGroups('my', 3);
        $this->assertEqual($groups, array());
    }

    public function testGetUsers() {
        $this->GroupUser->deleteAll(true);
        $this->GroupUser->save(array(
            'group_id' => 1,
            'user_id' => 1,
            'status' => 1
        ));
       
        $groups = $this->GroupUser->getUsers(1, 1);
        $this->assertNotEqual($groups, array());
        
        $groups = $this->GroupUser->getUsers(1, 3);
        $this->assertEqual($groups, array());
    }

    public function testGetUserCount() {
        $this->GroupUser->deleteAll(true);
        $this->GroupUser->save(array(
            'group_id' => 1,
            'user_id' => 1,
            'status' => 1
        ));
       
        $groups = $this->GroupUser->getUserCount(1, 1);
        $this->assertNotEqual($groups, array());
        
        $groups = $this->GroupUser->getUserCount(1, 3);
        $this->assertEqual($groups, 0);
    }

    public function testGetUsersList() {
        $this->GroupUser->deleteAll(true);
        $this->GroupUser->save(array(
            'group_id' => 1,
            'user_id' => 1,
            'status' => 1
        ));
       
        $groups = $this->GroupUser->getUsersList(1, 1);
        $this->assertNotEqual($groups, array());
        
        $groups = $this->GroupUser->getUsersList(1, 3);
        $this->assertEqual($groups, array());
    }

    public function testGetMyStatus() {
        $this->GroupUser->deleteAll(true);
        $this->GroupUser->save(array(
            'group_id' => 1,
            'user_id' => 1,
            'status' => 1
        ));
       
        $groups = $this->GroupUser->getMyStatus(1, 1);
        $this->assertNotEqual($groups, array());
        
        $groups = $this->GroupUser->getMyStatus(1, 2);
        $this->assertEqual($groups, array());
    }

    public function testIsMember() {
        $this->GroupUser->deleteAll(true);
        $this->GroupUser->save(array(
            'group_id' => 1,
            'user_id' => 1,
            'status' => 1
        ));
       
        $groups = $this->GroupUser->isMember(1, 1);
        $this->assertNotEqual($groups, array());
        
        $groups = $this->GroupUser->isMember(1, 2);
        $this->assertEqual($groups, false);
    }

    public function testGetMyGroupsList() {
        $this->GroupUser->deleteAll(true);
        $this->GroupUser->save(array(
            'group_id' => 1,
            'user_id' => 1,
            'status' => 1
        ));
       
        $groups = $this->GroupUser->getMyGroupsList(1);
        $this->assertNotEqual($groups, array());
        
        $groups = $this->GroupUser->getMyGroupsList(2);
        $this->assertEqual($groups, array());
    }

    public function testGetJoinedGroups() {
        $this->GroupUser->deleteAll(true);
        $this->GroupUser->save(array(
            'group_id' => 1,
            'user_id' => 1,
            'status' => 1
        ));
       
        $groups = $this->GroupUser->getJoinedGroups(1);
        $this->assertNotEqual($groups, array());
        
        $groups = $this->GroupUser->getJoinedGroups(2);
        $this->assertEqual($groups, array());
    }

}

?>