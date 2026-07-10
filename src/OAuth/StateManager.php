<?php
/**
 * OAuth State Manager.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\OAuth;

defined( 'ABSPATH' ) || exit;

/**
 * Class StateManager
 *
 * Handles stateless generation and verification of CSRF protection states.
 */
class StateManager {

	/**
	 * Expiration time of the state parameter in seconds.
	 *
	 * Default: 3600 seconds (1 hour).
	 */
	private const EXPIRATION_SECONDS = 3600;

	/**
	 * Generate a secure, signed state string representing the authentication request.
	 *
	 * @param string $action     The action to perform (e.g., 'login', 'link').
	 * @param string $redirectTo Optional local URL to redirect to after successful authentication.
	 * @return string
	 */
	public static function generateState( string $action, string $redirectTo = '' ): string {
		$nonce      = wp_generate_password( 16, false );
		$expiration = time() + self::EXPIRATION_SECONDS;
		$userId     = get_current_user_id();

		// Create state payload payload.
		$payload = [
			'nonce'       => $nonce,
			'expiration'  => $expiration,
			'action'      => $action,
			'redirect_to' => esc_url_raw( $redirectTo ),
			'user_id'     => $userId,
		];

		// Sign payload with site-specific salt to ensure integrity and authenticity.
		$signature = self::generateSignature( $payload );
		$payload['signature'] = $signature;

		return self::base64UrlEncode( wp_json_encode( $payload ) );
	}

	/**
	 * Validate and decode a state string.
	 *
	 * Checks expiration, signature verification, and user session context mapping.
	 *
	 * @param string $state          The signed state string to validate.
	 * @param string $expectedAction The expected action ('login' or 'link').
	 * @return array|null Decoded state payload if valid, null otherwise.
	 */
	public static function validateState( string $state, string $expectedAction ): ?array {
		$decodedJson = self::base64UrlDecode( $state );
		if ( ! $decodedJson ) {
			\GoogleLogin\Core\Logger::log( 'validateState: Failed to decode base64 state string.', 'WARNING' );
			return null;
		}

		$payload = json_decode( $decodedJson, true );
		if ( ! is_array( $payload ) ) {
			\GoogleLogin\Core\Logger::log( 'validateState: Failed to decode JSON payload: ' . $decodedJson, 'WARNING' );
			return null;
		}

		// Ensure required fields are present.
		$requiredFields = [ 'nonce', 'expiration', 'action', 'redirect_to', 'user_id', 'signature' ];
		foreach ( $requiredFields as $field ) {
			if ( ! isset( $payload[ $field ] ) ) {
				\GoogleLogin\Core\Logger::log( 'validateState: Missing state field: ' . $field, 'WARNING' );
				return null;
			}
		}

		// Check action match.
		if ( $payload['action'] !== $expectedAction ) {
			\GoogleLogin\Core\Logger::log( sprintf( 'validateState: Action mismatch. Expected: %s, Received: %s', $expectedAction, $payload['action'] ), 'WARNING' );
			return null;
		}

		// Check expiration.
		if ( time() > (int) $payload['expiration'] ) {
			\GoogleLogin\Core\Logger::log( sprintf( 'validateState: State expired. Current time: %d, Expiration: %d', time(), $payload['expiration'] ), 'WARNING' );
			return null;
		}

		// Check user session mapping to prevent session fixation/cross-session CSRF.
		if ( (int) $payload['user_id'] !== get_current_user_id() ) {
			\GoogleLogin\Core\Logger::log( sprintf( 'validateState: User ID mismatch. State payload ID: %d, Current logged-in ID: %d', $payload['user_id'], get_current_user_id() ), 'WARNING' );
			return null;
		}

		// Verify signature.
		$receivedSignature = $payload['signature'];
		unset( $payload['signature'] );

		$calculatedSignature = self::generateSignature( $payload );

		if ( ! hash_equals( $calculatedSignature, $receivedSignature ) ) {
			\GoogleLogin\Core\Logger::log( 'validateState: Signature mismatch.', 'WARNING' );
			return null;
		}

		return $payload;
	}

	/**
	 * Generate an HMAC-SHA256 signature for the state payload.
	 *
	 * @param array $payload State data array.
	 * @return string Hex encoded signature.
	 */
	private static function generateSignature( array $payload ): string {
		$dataString = sprintf(
			'%s|%d|%s|%s|%d',
			$payload['nonce'],
			(int) $payload['expiration'],
			$payload['action'],
			$payload['redirect_to'],
			(int) $payload['user_id']
		);

		// Use WordPress LOGGED_IN_SALT as the secret key. Fallback to default if undefined.
		$secretKey = defined( 'LOGGED_IN_SALT' ) ? LOGGED_IN_SALT : 'google-login-default-salt';

		return hash_hmac( 'sha256', $dataString, $secretKey );
	}

	/**
	 * Encodes data with base64url.
	 *
	 * @param string $data The string to encode.
	 * @return string
	 */
	private static function base64UrlEncode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Decodes data with base64url.
	 *
	 * @param string $data The encoded string.
	 * @return string|false The original string or false on failure.
	 */
	private static function base64UrlDecode( string $data ) {
		return base64_decode( strtr( $data, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $data ) % 4 ) % 4 ) );
	}
}



