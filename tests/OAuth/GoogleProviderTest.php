<?php
/**
 * Google Provider Unit Test.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Tests\OAuth;

use PHPUnit\Framework\TestCase;
use GoogleLogin\Core\Settings;
use GoogleLogin\OAuth\GoogleProvider;

/**
 * Class GoogleProviderTest
 *
 * Verifies key/label mappings and authorization URL parameters for Google.
 */
class GoogleProviderTest extends TestCase {

	/**
	 * Reset mock options.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_mock_options'] = [];
	}

	/**
	 * Test provider identity getters.
	 */
	public function testProviderMeta(): void {
		$provider = new GoogleProvider();

		$this->assertEquals( 'google', $provider->getKey() );
		$this->assertEquals( 'Google', $provider->getLabel() );
	}

	/**
	 * Test authorization Redirect URL building.
	 */
	public function testAuthorizationUrl(): void {
		$provider = new GoogleProvider();

		// Configure client ID.
		Settings::update( 'google_client_id', 'mock-google-client-id' );

		$state = 'abc-123-xyz-secure-state';
		$url   = $provider->getAuthorizationUrl( $state );

		$this->assertStringStartsWith( 'https://accounts.google.com/o/oauth2/v2/auth', $url );
		$this->assertStringContainsString( 'client_id=mock-google-client-id', $url );
		$this->assertStringContainsString( 'state=abc-123-xyz-secure-state', $url );
		$this->assertStringContainsString( 'response_type=code', $url );
		$this->assertStringContainsString( 'scope=openid+email+profile', $url );
	}
}



