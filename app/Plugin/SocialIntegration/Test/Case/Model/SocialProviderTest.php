<?php
App::uses('SocialProvider', 'SocialIntegration.Model');

/**
 * SocialProvider Test Case
 *
 */
class SocialProviderTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.social_integration.social_provider',
		'plugin.social_integration.client'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->SocialProvider = ClassRegistry::init('SocialIntegration.SocialProvider');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->SocialProvider);

		parent::tearDown();
	}

}
