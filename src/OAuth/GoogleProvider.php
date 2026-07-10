<?php
/**
 * Google OAuth Provider.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\OAuth;

use GoogleLogin\Contracts\ProviderInterface;
use GoogleLogin\Core\Settings;
use GoogleLogin\Core\Logger;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleProvider
 *
 * Implements the Google OAuth 2.0 connection flow.
 */
class GoogleProvider implements ProviderInterface {

	/**
	 * Provider unique key identifier.
	 */
	private const PROVIDER_KEY = 'google';

	/**
	 * Provider label name.
	 */
	private const PROVIDER_LABEL = 'Google';

	/**
	 * Get the provider unique key.
	 *
	 * @return string
	 */
	public function getKey(): string {
		return self::PROVIDER_KEY;
	}

	/**
	 * Get the provider display label.
	 *
	 * @return string
	 */
	public function getLabel(): string {
		return self::PROVIDER_LABEL;
	}

	/**
	 * Check if provider is enabled and configured correctly.
	 *
	 * @return bool
	 */
	public function isEnabled(): bool {
		return Settings::isGoogleEnabled();
	}

	/**
	 * Get the redirect callback URL.
	 *
	 * Uses WordPress REST API URL generation.
	 *
	 * @return string
	 */
	public static function getCallbackUrl(): string {
		return get_rest_url( null, 'google-login/v1/callback/' . self::PROVIDER_KEY );
	}

	/**
	 * Generate the Google authorization endpoint URL.
	 *
	 * @param string $state Secure CSRF validation token.
	 * @return string
	 */
	public function getAuthorizationUrl( string $state ): string {
		$clientId = Settings::get( 'google_client_id' );

		$params = [
			'client_id'     => $clientId,
			'redirect_uri'  => self::getCallbackUrl(),
			'response_type' => 'code',
			'scope'         => 'openid email profile',
			'state'         => $state,
			'prompt'        => 'select_account',
			'access_type'   => 'offline', // Requests a refresh token if not already granted.
		];

		$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );

		Logger::log( 'Generated Google authorization URL: ' . $authUrl, 'INFO' );

		return $authUrl;
	}

	/**
	 * Exchange code for access tokens.
	 *
	 * @param string $code OAuth authorization code.
	 * @return array Tokens array.
	 * @throws Exception On API connection errors or auth failures.
	 */
	public function exchangeCodeForTokens( string $code ): array {
		$clientId     = Settings::get( 'google_client_id' );
		$clientSecret = Settings::get( 'google_client_secret' );

		$body = [
			'code'          => $code,
			'client_id'     => $clientId,
			'client_secret' => $clientSecret,
			'redirect_uri'  => self::getCallbackUrl(),
			'grant_type'    => 'authorization_code',
		];

		Logger::log( 'Requesting token exchange with body: ' . wp_json_encode( $body ), 'INFO' );

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			[
				'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
				'body'    => $body,
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			$errMsg = $response->get_error_message();
			Logger::log( 'Token exchange failed (WP_Error): ' . $errMsg, 'ERROR' );
			throw new Exception( sprintf( esc_html__( 'Failed to communicate with Google token endpoint: %s', 'google-login' ), $errMsg ) );
		}

		$responseCode = wp_remote_retrieve_response_code( $response );
		$responseBody = wp_remote_retrieve_body( $response );

		Logger::log( sprintf( 'Token exchange response [Code: %d] [Body: %s]', $responseCode, $responseBody ), 'INFO' );

		if ( 200 !== $responseCode ) {
			throw new Exception( sprintf( esc_html__( 'Google token exchange returned HTTP %d', 'google-login' ), $responseCode ) );
		}

		$tokenData = json_decode( $responseBody, true );
		if ( ! is_array( $tokenData ) || empty( $tokenData['access_token'] ) ) {
			throw new Exception( esc_html__( 'Invalid token format received from Google.', 'google-login' ) );
		}

		return $tokenData;
	}

	/**
	 * Fetch Google UserProfile DTO.
	 *
	 * @param array $tokens Array containing access token.
	 * @return UserProfile
	 * @throws Exception On connection or profile issues.
	 */
	public function getUserProfile( array $tokens ): UserProfile {
		$accessToken = $tokens['access_token'];

		$response = wp_remote_get(
			'https://www.googleapis.com/oauth2/v3/userinfo',
			[
				'headers' => [ 'Authorization' => 'Bearer ' . $accessToken ],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			$errMsg = $response->get_error_message();
			Logger::log( 'Fetching UserInfo failed (WP_Error): ' . $errMsg, 'ERROR' );
			throw new Exception( sprintf( esc_html__( 'Failed to retrieve profile from Google: %s', 'google-login' ), $errMsg ) );
		}

		$responseCode = wp_remote_retrieve_response_code( $response );
		$responseBody = wp_remote_retrieve_body( $response );

		Logger::log( sprintf( 'UserInfo response [Code: %d] [Body: %s]', $responseCode, $responseBody ), 'INFO' );

		if ( 200 !== $responseCode ) {
			throw new Exception( sprintf( esc_html__( 'Google UserInfo returned HTTP %d', 'google-login' ), $responseCode ) );
		}

		$profileData = json_decode( $responseBody, true );
		if ( ! is_array( $profileData ) || empty( $profileData['sub'] ) ) {
			throw new Exception( esc_html__( 'Invalid profile data structure received from Google.', 'google-login' ) );
		}

		// Parse individual parameters.
		$id          = $profileData['sub'];
		$email       = $profileData['email'] ?? '';
		$firstName   = $profileData['given_name'] ?? '';
		$lastName    = $profileData['family_name'] ?? '';
		$displayName = $profileData['name'] ?? '';
		$avatarUrl   = $profileData['picture'] ?? null;

		if ( empty( $email ) ) {
			throw new Exception( esc_html__( 'Google account does not provide an email address.', 'google-login' ) );
		}

		return new UserProfile(
			id: $id,
			email: $email,
			firstName: $firstName,
			lastName: $lastName,
			displayName: $displayName,
			avatarUrl: $avatarUrl
		);
	}
}



