<?php
/**
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 */
App::uses('GroupUser', 'Group.Model');

class GroupUserFixture extends CakeTestFixture {

    public $import = array('model' => 'Group.GroupUser', 'records' => true);

}
