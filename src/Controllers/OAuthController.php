<?php
/**
 * OAuth Controller.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Controllers;

use GoogleLogin\Core\Settings;
use GoogleLogin\Core\Logger;
use GoogleLogin\OAuth\StateManager;
use GoogleLogin\OAuth\GoogleProvider;
use GoogleLogin\Repositories\UserRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class OAuthController
 *
 * Handles routing and flow execution for OAuth redirects and callbacks.
 */
class OAuthController {

	/**
	 * User repository instance.
	 *
	 * @var UserRepository
	 */
	private UserRepository $userRepository;

	/**
	 * Constructor.
	 *
	 * @param UserRepository $userRepository User repository dependency.
	 */
	public function __construct( UserRepository $userRepository ) {
		$this->userRepository = $userRepository;
	}

	/**
	 * Register actions and endpoints.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'handleRedirectTrigger' ] );
		add_action( 'init', [ $this, 'handleFrontendCallback' ] );
		add_action( 'rest_api_init', [ $this, 'registerRestRoutes' ] );
	}

	/**
	 * Register callback REST API routes.
	 *
	 * Route: /wp-json/google-login/v1/callback/<provider>
	 *
	 * @return void
	 */
	public function registerRestRoutes(): void {
		register_rest_route(
			'google-login/v1',
			'/callback/(?P<provider>[a-zA-Z0-9_-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'handleCallback' ],
				'permission_callback' => '__return_true', // Public endpoint.
			]
		);
	}

	/**
	 * Listen for initiating social auth requests via standard query variables.
	 *
	 * Checks query parameters: google_login_action (login/link) and provider (google).
	 *
	 * @return void
	 */
	public function handleRedirectTrigger(): void {
		if ( ! isset( $_GET['google_login_action'] ) || ! isset( $_GET['provider'] ) ) {
			return;
		}

		$action   = sanitize_text_field( $_GET['google_login_action'] );
		$providerKey = sanitize_text_field( $_GET['provider'] );

		if ( ! in_array( $action, [ 'login', 'link' ], true ) || 'google' !== $providerKey ) {
			return;
		}

		// Instantiate Google provider.
		$provider = new GoogleProvider();

		if ( ! $provider->isEnabled() ) {
			wp_die( esc_html__( 'Social authentication provider is not configured or disabled.', 'google-login' ), esc_html__( 'Configuration Error', 'google-login' ), [ 'response' => 403 ] );
		}

		// Security: Nonce verification for account linking since user is already logged in.
		if ( 'link' === $action ) {
			if ( ! is_user_logged_in() ) {
				wp_die( esc_html__( 'You must be logged in to link your social account.', 'google-login' ), esc_html__( 'Access Denied', 'google-login' ), [ 'response' => 403 ] );
			}
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'google_login_link_account' ) ) {
				wp_die( esc_html__( 'Security check failed. Invalid link nonce.', 'google-login' ), esc_html__( 'Access Denied', 'google-login' ), [ 'response' => 403 ] );
			}
		}

		// Resolve safe redirection URL.
		$redirectTo = '';
		if ( ! empty( $_GET['redirect_to'] ) ) {
			$redirectTo = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
		} else {
			$redirectTo = $this->getDefaultRedirectUrl( $action );
		}

		// Generate secure state.
		$state = StateManager::generateState( $action, $redirectTo );

		// Retrieve authorize URL.
		$authUrl = $provider->getAuthorizationUrl( $state );

		// Redirect to provider.
		wp_redirect( $authUrl );
		exit;
	}

	/**
	 * Process callback from Google OAuth.
	 *
	 * Handler for WP REST route.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return void
	 */
	public function handleCallback( WP_REST_Request $request ): void {
		$providerKey = $request->get_param( 'provider' );
		$code        = $request->get_param( 'code' );
		$state       = $request->get_param( 'state' );
		$error       = $request->get_param( 'error' );

		Logger::log( sprintf( 'Processing callback for provider [%s] with code and state.', $providerKey ), 'INFO' );

		// Handle error response from Google.
		if ( ! empty( $error ) ) {
			Logger::log( 'Callback error parameter received: ' . $error, 'ERROR' );
			wp_die( sprintf( esc_html__( 'Authorization failed from provider: %s', 'google-login' ), esc_html( $error ) ), esc_html__( 'Authorization Error', 'google-login' ), [ 'response' => 400 ] );
		}

		if ( 'google' !== $providerKey ) {
			wp_die( esc_html__( 'Unsupported authentication provider.', 'google-login' ), esc_html__( 'Provider Error', 'google-login' ), [ 'response' => 404 ] );
		}

		if ( empty( $code ) || empty( $state ) ) {
			wp_die( esc_html__( 'Missing authorization code or verification state.', 'google-login' ), esc_html__( 'Security Check Failed', 'google-login' ), [ 'response' => 400 ] );
		}

		// Pre-decode state to identify the expected action.
		$paddedState = strtr( $state, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $state ) % 4 ) % 4 );
		$decodedState = base64_decode( $paddedState );
		$payloadData = $decodedState ? json_decode( $decodedState, true ) : null;

		if ( is_array( $payloadData ) && isset( $payloadData['action'] ) && 'link' === $payloadData['action'] ) {
			if ( 0 === get_current_user_id() ) {
				$bounceUrl = add_query_arg(
					[
						'google_login_callback_flow' => 'link',
						'code'                  => $code,
						'state'                 => $state,
					],
					$payloadData['redirect_to']
				);
				Logger::log( 'Redirecting link action to frontend page to restore cookies: ' . $bounceUrl, 'INFO' );
				wp_safe_redirect( $bounceUrl );
				exit;
			}
		}

		// Validate state parameter to retrieve payload.
		$payload = $this->getAndValidateState( $state );
		if ( ! $payload ) {
			Logger::log( 'State validation failed.', 'ERROR' );
			wp_die( esc_html__( 'Security check failed. State mismatch or expired.', 'google-login' ), esc_html__( 'Security Check Failed', 'google-login' ), [ 'response' => 403 ] );
		}

		$action     = $payload['action'];
		$redirectTo = $payload['redirect_to'];

		// Initialize provider.
		$provider = new GoogleProvider();

		try {
			// Exchange code for tokens.
			$tokens = $provider->exchangeCodeForTokens( $code );

			// Fetch user profile.
			$profile = $provider->getUserProfile( $tokens );

			if ( 'link' === $action ) {
				$this->executeLinking( $profile, $redirectTo );
			} else {
				$this->executeLogin( $profile, $redirectTo );
			}
		} catch ( \Exception $e ) {
			Logger::log( 'OAuth execution error: ' . $e->getMessage(), 'ERROR' );
			wp_die( sprintf( esc_html__( 'Authentication Error: %s', 'google-login' ), esc_html( $e->getMessage() ) ), esc_html__( 'Authentication Error', 'google-login' ), [ 'response' => 500 ] );
		}
	}

	/**
	 * Handle the same-site frontend callback for linking accounts.
	 *
	 * This handles requests bounced from the REST API callback to restore session cookies.
	 *
	 * @return void
	 */
	public function handleFrontendCallback(): void {
		if ( ! isset( $_GET['google_login_callback_flow'] ) || 'link' !== $_GET['google_login_callback_flow'] ) {
			return;
		}

		$code  = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
		$state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';

		if ( empty( $code ) || empty( $state ) ) {
			return;
		}

		Logger::log( 'Processing frontend same-site callback for account linking.', 'INFO' );

		// Validate state parameter.
		$payload = $this->getAndValidateState( $state );
		if ( ! $payload ) {
			Logger::log( 'Frontend state validation failed.', 'ERROR' );
			wp_die( esc_html__( 'Security check failed. State mismatch or expired.', 'google-login' ), esc_html__( 'Security Check Failed', 'google-login' ), [ 'response' => 403 ] );
		}

		$action     = $payload['action'];
		$redirectTo = $payload['redirect_to'];

		if ( 'link' !== $action ) {
			wp_die( esc_html__( 'Invalid action for frontend callback.', 'google-login' ), esc_html__( 'Invalid Action', 'google-login' ), [ 'response' => 400 ] );
		}

		// Initialize provider.
		$provider = new GoogleProvider();

		try {
			// Exchange code for tokens.
			$tokens = $provider->exchangeCodeForTokens( $code );

			// Fetch user profile.
			$profile = $provider->getUserProfile( $tokens );

			$this->executeLinking( $profile, $redirectTo );
		} catch ( \Exception $e ) {
			Logger::log( 'Frontend OAuth execution error: ' . $e->getMessage(), 'ERROR' );
			wp_die( sprintf( esc_html__( 'Authentication Error: %s', 'google-login' ), esc_html( $e->getMessage() ) ), esc_html__( 'Authentication Error', 'google-login' ), [ 'response' => 500 ] );
		}
	}

	/**
	 * Decode and validate signed state payload.
	 *
	 * @param string $state Raw state.
	 * @return array|null Validated state payload.
	 */
	private function getAndValidateState( string $state ): ?array {
		Logger::log( 'getAndValidateState: Validating state: ' . $state, 'INFO' );

		// Pre-decode state to identify the expected action. Use proper padding.
		$paddedState = strtr( $state, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $state ) % 4 ) % 4 );
		$decodedState = base64_decode( $paddedState );
		if ( ! $decodedState ) {
			Logger::log( 'getAndValidateState: Pre-decode base64 failed.', 'WARNING' );
			return null;
		}

		$json = json_decode( $decodedState, true );
		if ( ! is_array( $json ) || empty( $json['action'] ) ) {
			Logger::log( 'getAndValidateState: Pre-decode JSON failed. Decoded state: ' . $decodedState, 'WARNING' );
			return null;
		}

		$action = sanitize_text_field( $json['action'] );
		Logger::log( 'getAndValidateState: Identified action: ' . $action, 'INFO' );

		$result = StateManager::validateState( $state, $action );
		if ( ! $result ) {
			Logger::log( 'getAndValidateState: StateManager::validateState returned null.', 'WARNING' );
		}
		return $result;
	}

	/**
	 * Perform linking action of social account to the logged-in user.
	 *
	 * @param \GoogleLogin\OAuth\UserProfile $profile    Social profile DTO.
	 * @param string                     $redirectTo Success redirect.
	 * @return void
	 */
	private function executeLinking( $profile, string $redirectTo ): void {
		$currentUserId = get_current_user_id();

		if ( ! $currentUserId ) {
			wp_die( esc_html__( 'You must be logged in to link accounts.', 'google-login' ), esc_html__( 'Access Denied', 'google-login' ), [ 'response' => 403 ] );
		}

		// Verify Google account isn't already linked to someone else.
		$existingLinkedUser = $this->userRepository->findUserByGoogleId( $profile->id );
		if ( $existingLinkedUser && $existingLinkedUser->ID !== $currentUserId ) {
			wp_die( esc_html__( 'This Google account is already linked to another WordPress account.', 'google-login' ), esc_html__( 'Linking Error', 'google-login' ), [ 'response' => 400 ] );
		}

		// Link account.
		$this->userRepository->linkUser( $currentUserId, $profile->id, $profile->email );

		// Sync avatar if available.
		if ( ! empty( $profile->avatarUrl ) ) {
			$this->userRepository->updateProfileImage( $currentUserId, $profile->avatarUrl );
		}

		Logger::log( sprintf( 'Successfully linked Google ID [%s] to WP user ID [%d]', $profile->id, $currentUserId ), 'INFO' );

		// Redirect with success flag.
		$successUrl = add_query_arg( 'google_login_success', 'linked', $redirectTo );
		wp_safe_redirect( $successUrl );
		exit;
	}

	/**
	 * Perform social login/registration flow.
	 *
	 * @param \GoogleLogin\OAuth\UserProfile $profile    Social profile DTO.
	 * @param string                     $redirectTo Target redirect URL.
	 * @return void
	 */
	private function executeLogin( $profile, string $redirectTo ): void {
		// 1. Attempt lookup by linked social ID.
		$user = $this->userRepository->findUserByGoogleId( $profile->id );

		// 2. Attempt lookup by matching email (Auto-linking).
		if ( ! $user ) {
			$user = $this->userRepository->findUserByEmail( $profile->email );
			if ( $user ) {
				// Link user automatically.
				$this->userRepository->linkUser( $user->ID, $profile->id, $profile->email );
				Logger::log( sprintf( 'Auto-linked Google ID [%s] to existing email account [%s] (ID: %d)', $profile->id, $profile->email, $user->ID ), 'INFO' );
			}
		}

		// 3. Register user if not found and registration is enabled.
		if ( ! $user ) {
			if ( ! Settings::isRegistrationEnabled() ) {
				Logger::log( sprintf( 'Login failed: Account registration is disabled. Google Email: %s', $profile->email ), 'WARNING' );
				// Redirect to WP login screen with error.
				$errorUrl = add_query_arg( 'google_login_error', 'registration_disabled', wp_login_url() );
				wp_safe_redirect( $errorUrl );
				exit;
			}

			// Create new user. Resolve role (default to settings role, or 'customer' if registering via WooCommerce flow).
			$role = Settings::getDefaultRole();
			if ( class_exists( 'WooCommerce' ) ) {
				$myAccountUrl = wc_get_page_permalink( 'myaccount' );
				$checkoutUrl  = wc_get_page_permalink( 'checkout' );
				if (
					! empty( $redirectTo ) && (
						str_contains( $redirectTo, $myAccountUrl ) ||
						str_contains( $redirectTo, $checkoutUrl ) ||
						str_contains( strtolower( $redirectTo ), 'my-account' ) ||
						str_contains( strtolower( $redirectTo ), 'checkout' )
					)
				) {
					$role = 'customer';
				}
			}
			$userId = $this->userRepository->createUser( $profile, $role );

			if ( is_wp_error( $userId ) ) {
				Logger::log( 'User registration failed (WP_Error): ' . $userId->get_error_message(), 'ERROR' );
				wp_die( esc_html__( 'Failed to create a new user account.', 'google-login' ), esc_html__( 'Registration Error', 'google-login' ), [ 'response' => 500 ] );
			}

			// Link newly created user.
			$this->userRepository->linkUser( $userId, $profile->id, $profile->email );
			$user = get_user_by( 'id', $userId );

			Logger::log( sprintf( 'Registered and linked new user ID [%d] for Google Email [%s]', $userId, $profile->email ), 'INFO' );
		}

		// Sync avatar image.
		if ( $user && ! empty( $profile->avatarUrl ) ) {
			$this->userRepository->updateProfileImage( $user->ID, $profile->avatarUrl );
		}

		// Log user in.
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );
		do_action( 'wp_login', $user->user_login, $user );

		Logger::log( sprintf( 'Logged in user ID [%d]. Redirecting to: %s', $user->ID, $redirectTo ), 'INFO' );

		wp_safe_redirect( $redirectTo );
		exit;
	}

	/**
	 * Get the default redirect URL based on config and action.
	 *
	 * @param string $action The current action ('login' or 'link').
	 * @return string
	 */
	private function getDefaultRedirectUrl( string $action ): string {
		if ( 'link' === $action ) {
			// Redirect back to profile page.
			return is_admin() ? admin_url( 'profile.php' ) : home_url( '/' );
		}

		// Resolve redirect settings.
		$type = Settings::get( 'login_redirect_type', 'default' );

		if ( 'custom' === $type && ! empty( Settings::get( 'custom_redirect_url' ) ) ) {
			return Settings::get( 'custom_redirect_url' );
		}

		if ( 'referer' === $type && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			// Check that referer belongs to this site.
			if ( wp_safe_redirect( $referer ) !== false ) {
				return $referer;
			}
		}

		// WooCommerce account check.
		if ( class_exists( 'WooCommerce' ) ) {
			$myAccountUrl = wc_get_page_permalink( 'myaccount' );
			if ( $myAccountUrl ) {
				return $myAccountUrl;
			}
		}

		return admin_url();
	}
}



