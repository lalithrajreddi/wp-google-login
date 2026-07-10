<?php
/**
 * WordPress Core Integration.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Integrations;

use GoogleLogin\Core\Settings;
use GoogleLogin\Core\Logger;
use GoogleLogin\Repositories\UserRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class WordPressIntegration
 *
 * Handles standard WordPress logins, profile screens, custom avatars, and unlinking callbacks.
 */
class WordPressIntegration {

	/**
	 * User repository instance.
	 *
	 * @var UserRepository
	 */
	private UserRepository $userRepository;

	/**
	 * Constructor.
	 *
	 * @param UserRepository $userRepository User repository.
	 */
	public function __construct( UserRepository $userRepository ) {
		$this->userRepository = $userRepository;
	}

	/**
	 * Register integration hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Login form buttons.
		add_action( 'login_form', [ $this, 'renderLoginButton' ] );
		add_action( 'register_form', [ $this, 'renderLoginButton' ] );
		add_filter( 'login_message', [ $this, 'displayLoginErrors' ] );

		// User profile linking status.
		add_action( 'show_user_profile', [ $this, 'renderProfileLinkingSection' ] );
		add_action( 'edit_user_profile', [ $this, 'renderProfileLinkingSection' ] );

		// Handle unlink callback.
		add_action( 'admin_init', [ $this, 'handleUnlinkAction' ] );

		// Custom avatar filters.
		add_filter( 'get_avatar', [ $this, 'filterAvatar' ], 10, 5 );
		add_filter( 'get_avatar_url', [ $this, 'filterAvatarUrl' ], 10, 3 );
	}

	/**
	 * Render Google login button inside standard WP Forms.
	 *
	 * @return void
	 */
	public function renderLoginButton(): void {
		if ( ! Settings::isGoogleEnabled() ) {
			return;
		}

		// Enqueue styling for social buttons.
		wp_enqueue_style( 'google-login-frontend', plugins_url( 'assets/css/google-login.css', dirname( __DIR__ ) ), [], GOOGLE_LOGIN_VERSION );

		$loginUrl = add_query_arg(
			[
				'google_login_action' => 'login',
				'provider'       => 'google',
			],
			home_url( '/' )
		);

		include dirname( dirname( __DIR__ ) ) . '/templates/login-buttons.php';
	}

	/**
	 * Append custom login/registration error messages to the WordPress login screen.
	 *
	 * @param string $message Existing messages.
	 * @return string Modified message.
	 */
	public function displayLoginErrors( string $message ): string {
		if ( ! isset( $_GET['google_login_error'] ) ) {
			return $message;
		}

		$errorKey = sanitize_text_field( $_GET['google_login_error'] );
		$errorMsg = '';

		if ( 'registration_disabled' === $errorKey ) {
			$errorMsg = esc_html__( 'Account registration is disabled on this site.', 'google-login' );
		}

		if ( ! empty( $errorMsg ) ) {
			$message .= sprintf( '<div id="login_error"><strong>%s</strong></div>', $errorMsg );
		}

		return $message;
	}

	/**
	 * Render Google Login connection manager within WP Admin Profile editor.
	 *
	 * @param \WP_User $user The user being edited.
	 * @return void
	 */
	public function renderProfileLinkingSection( $user ): void {
		if ( ! Settings::isLinkingEnabled() ) {
			return;
		}

		$googleId    = get_user_meta( $user->ID, '_google_login_google_id', true );
		$googleEmail = get_user_meta( $user->ID, '_google_login_google_email', true );
		$isLinked    = ! empty( $googleId );

		// Generate nonced URLs.
		$linkUrl = wp_nonce_url(
			add_query_arg(
				[
					'google_login_action' => 'link',
					'provider'       => 'google',
					'redirect_to'    => urlencode( admin_url( 'profile.php' ) ),
				],
				home_url( '/' )
			),
			'google_login_link_account'
		);

		$unlinkUrl = wp_nonce_url(
			add_query_arg(
				[
					'google_login_action' => 'unlink',
					'provider'       => 'google',
				],
				admin_url( 'profile.php' )
			),
			'google_login_unlink_account'
		);

		// Style dashboard.
		wp_enqueue_style( 'google-login-frontend', plugins_url( 'assets/css/google-login.css', dirname( __DIR__ ) ), [], GOOGLE_LOGIN_VERSION );

		include dirname( dirname( __DIR__ ) ) . '/templates/profile-linking.php';
	}

	/**
	 * Intercept unlinking requests in wp-admin.
	 *
	 * @return void
	 */
	public function handleUnlinkAction(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! isset( $_GET['google_login_action'] ) || 'unlink' !== $_GET['google_login_action'] || ! isset( $_GET['provider'] ) || 'google' !== $_GET['provider'] ) {
			return;
		}

		// Verify security nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'google_login_unlink_account' ) ) {
			wp_die( esc_html__( 'Security check failed. Invalid unlink nonce.', 'google-login' ), esc_html__( 'Access Denied', 'google-login' ), [ 'response' => 403 ] );
		}

		$userId = get_current_user_id();
		$this->userRepository->unlinkUser( $userId );

		Logger::log( sprintf( 'Unlinked Google Account for user ID [%d] via profile dashboard.', $userId ), 'INFO' );

		wp_safe_redirect( add_query_arg( 'google_login_success', 'unlinked', admin_url( 'profile.php' ) ) );
		exit;
	}

	/**
	 * Intercept standard get_avatar and use social profile photo if configured.
	 *
	 * @param string $avatar      HTML image tag.
	 * @param mixed  $idOrEmail   User ID, email, or object.
	 * @param int    $size        Image size.
	 * @param string $default     Fallback image.
	 * @param string $alt         Alt text.
	 * @return string Modified HTML image tag.
	 */
	public function filterAvatar( string $avatar, mixed $idOrEmail, int $size, string $default, string $alt ): string {
		$userId = 0;

		if ( is_numeric( $idOrEmail ) ) {
			$userId = (int) $idOrEmail;
		} elseif ( is_object( $idOrEmail ) && ! empty( $idOrEmail->user_id ) ) {
			$userId = (int) $idOrEmail->user_id;
		} elseif ( is_string( $idOrEmail ) && strpos( $idOrEmail, '@' ) !== false ) {
			$user = get_user_by( 'email', $idOrEmail );
			if ( $user ) {
				$userId = $user->ID;
			}
		}

		if ( $userId ) {
			$socialAvatar = get_user_meta( $userId, '_google_login_social_avatar', true );
			if ( ! empty( $socialAvatar ) ) {
				return sprintf(
					'<img alt="%s" src="%s" class="avatar avatar-%d photo google-login-avatar" height="%d" width="%d" />',
					esc_attr( $alt ),
					esc_url( $socialAvatar ),
					(int) $size,
					(int) $size,
					(int) $size
				);
			}
		}

		return $avatar;
	}

	/**
	 * Intercept get_avatar_url and return social profile photo URL.
	 *
	 * @param string $url       Avatar URL.
	 * @param mixed  $idOrEmail User ID, email, or object.
	 * @param array  $args      Avatar arguments.
	 * @return string Modified URL.
	 */
	public function filterAvatarUrl( string $url, mixed $idOrEmail, array $args ): string {
		$userId = 0;

		if ( is_numeric( $idOrEmail ) ) {
			$userId = (int) $idOrEmail;
		} elseif ( is_object( $idOrEmail ) && ! empty( $idOrEmail->user_id ) ) {
			$userId = (int) $idOrEmail->user_id;
		} elseif ( is_string( $idOrEmail ) && strpos( $idOrEmail, '@' ) !== false ) {
			$user = get_user_by( 'email', $idOrEmail );
			if ( $user ) {
				$userId = $user->ID;
			}
		}

		if ( $userId ) {
			$socialAvatar = get_user_meta( $userId, '_google_login_social_avatar', true );
			if ( ! empty( $socialAvatar ) ) {
				return esc_url_raw( $socialAvatar );
			}
		}

		return $url;
	}
}



