<?php
/**
 * Settings Unit Test.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Tests\Core;

use PHPUnit\Framework\TestCase;
use GoogleLogin\Core\Settings;

/**
 * Class SettingsTest
 *
 * Verifies plugin settings loading, defaults, and sanitization logic.
 */
class SettingsTest extends TestCase {

	/**
	 * Reset mock options array before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_mock_options'] = [
			'users_can_register' => 1,
		];
	}

	/**
	 * Test default values fallback.
	 */
	public function testGetDefaults(): void {
		$this->assertFalse( Settings::isGoogleEnabled() );
		$this->assertTrue( Settings::isRegistrationEnabled() );
		$this->assertTrue( Settings::isLinkingEnabled() );
		$this->assertFalse( Settings::isDebugEnabled() );
		$this->assertEquals( 'subscriber', Settings::getDefaultRole() );
	}

	/**
	 * Test settings sanitization.
	 */
	public function testSettingsSanitization(): void {
		$input = [
			'google_enabled'       => '1',
			'google_client_id'      => '  98765-google.apps.com   ',
			'google_client_secret'  => 'super-secret-key-123',
			'allow_registration'   => '0',
			'allow_linking'        => '1',
			'default_role'         => 'editor', // Valid role.
			'login_redirect_type'  => 'custom',
			'custom_redirect_url'  => 'https://mysite.com/dashboard',
			'debug_enabled'        => '1',
		];

		$sanitized = Settings::sanitizeSettings( $input );

		$this->assertEquals( 1, $sanitized['google_enabled'] );
		$this->assertEquals( '98765-google.apps.com', $sanitized['google_client_id'] );
		$this->assertEquals( 'super-secret-key-123', $sanitized['google_client_secret'] );
		$this->assertEquals( 0, $sanitized['allow_registration'] );
		$this->assertEquals( 1, $sanitized['allow_linking'] );
		$this->assertEquals( 'editor', $sanitized['default_role'] );
		$this->assertEquals( 'custom', $sanitized['login_redirect_type'] );
		$this->assertEquals( 'https://mysite.com/dashboard', $sanitized['custom_redirect_url'] );
		$this->assertEquals( 1, $sanitized['debug_enabled'] );
	}

	/**
	 * Test option updates.
	 */
	public function testOptionUpdate(): void {
		Settings::update( 'google_client_id', 'test-client-id' );
		$this->assertEquals( 'test-client-id', Settings::get( 'google_client_id' ) );
	}
}



