<?php

/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
App::uses('Group', 'Group.Model');

class GroupTest extends CakeTestCase {

    public $fixtures = array('plugin.group.group', 'plugin.group.group_user');

    public function setUp() {
        parent::setUp();
        $this->Group = ClassRegistry::init('Group');
    }

    public function testGetGroups() {
        $this->Group->save(array(
            'id' => 1,
            'category_id' => 1,
            'name' => 'group 1',
            'user_id' => 1,
            'description' => 'group description',
            'type' => PRIVACY_PUBLIC
        ));
        $groups = $this->Group->getGroups(PRIVACY_PUBLIC);
        $this->assertNotEqual($groups, array());
    }

    public function testGetPopularGroups() {
        $this->Group->save(array(
            'id' => 1,
            'category_id' => 1,
            'name' => 'group 1',
            'user_id' => 1,
            'description' => 'group description',
            'type' => PRIVACY_PUBLIC
        ));
        $groups = $this->Group->getPopularGroups();
        $this->assertNotEqual($groups, array());
    }

    public function testDeleteGroup() {
        $this->Group->save(array(
            'id' => 1,
            'category_id' => 1,
            'name' => 'group 1',
            'user_id' => 1,
            'description' => 'group description',
            'type' => PRIVACY_PUBLIC
        ));
        $group_before = $this->Group->findById(1);
        $this->Group->deleteGroup($group_before);
        $group_after = $this->Group->findById(1);
        $this->assertEqual($group_after, array());
    }

    public function testGetMyGroupsCount() {
        $this->Group->deleteAll(true);
        $this->Group->save(array(
            'id' => 1,
            'category_id' => 1,
            'name' => 'group 1',
            'user_id' => 1,
            'description' => 'group description',
            'type' => PRIVACY_PUBLIC
        ));
        $count = $this->Group->find('count');
        $this->assertEqual($count, 1);
    }

}

?>