<?php
App::uses('SocialUser', 'SocialIntegration.Model');

/**
 * SocialUser Test Case
 *
 */
class SocialUserTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.social_integration.social_user'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->SocialUser = ClassRegistry::init('SocialIntegration.SocialUser');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->SocialUser);

		parent::tearDown();
	}

}
