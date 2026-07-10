<?php
/**
 * WooCommerce Integration.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Integrations;

use GoogleLogin\Core\Settings;
use GoogleLogin\Core\Logger;
use GoogleLogin\Repositories\UserRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class WooCommerceIntegration
 *
 * Connects Google Login to WooCommerce forms and account dashboards.
 */
class WooCommerceIntegration {

	/**
	 * User repository.
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
	 * Register WooCommerce hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Inject buttons on forms.
		add_action( 'woocommerce_login_form', [ $this, 'renderLoginButton' ] );
		add_action( 'woocommerce_register_form', [ $this, 'renderLoginButton' ] );
		add_action( 'woocommerce_checkout_login_form_end', [ $this, 'renderLoginButton' ] );

		// User dashboard linking options.
		add_action( 'woocommerce_account_dashboard', [ $this, 'renderAccountLinkingSection' ] );

		// Intercept frontend unlinking requests.
		add_action( 'template_redirect', [ $this, 'handleFrontendUnlinkAction' ] );
	}

	/**
	 * Render standard Google login button in WooCommerce login forms.
	 *
	 * @return void
	 */
	public function renderLoginButton(): void {
		if ( ! Settings::isGoogleEnabled() ) {
			return;
		}

		// Enqueue styling.
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
	 * Render connection options inside WooCommerce My Account Dashboard.
	 *
	 * @return void
	 */
	public function renderAccountLinkingSection(): void {
		if ( ! Settings::isLinkingEnabled() ) {
			return;
		}

		$userId      = get_current_user_id();
		$googleId    = get_user_meta( $userId, '_google_login_google_id', true );
		$googleEmail = get_user_meta( $userId, '_google_login_google_email', true );
		$isLinked    = ! empty( $googleId );

		$myAccountUrl = wc_get_page_permalink( 'myaccount' );

		// Generate nonced URLs.
		$linkUrl = wp_nonce_url(
			add_query_arg(
				[
					'google_login_action' => 'link',
					'provider'       => 'google',
					'redirect_to'    => urlencode( $myAccountUrl ),
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
					'redirect_to'    => urlencode( $myAccountUrl ),
				],
				$myAccountUrl
			),
			'google_login_unlink_account'
		);

		wp_enqueue_style( 'google-login-frontend', plugins_url( 'assets/css/google-login.css', dirname( __DIR__ ) ), [], GOOGLE_LOGIN_VERSION );

		// Render success/error alerts if present.
		if ( isset( $_GET['google_login_success'] ) ) {
			$successKey = sanitize_text_field( $_GET['google_login_success'] );
			if ( 'linked' === $successKey ) {
				echo '<div class="woocommerce-message">' . esc_html__( 'Google account linked successfully.', 'google-login' ) . '</div>';
			} elseif ( 'unlinked' === $successKey ) {
				echo '<div class="woocommerce-message">' . esc_html__( 'Google account unlinked successfully.', 'google-login' ) . '</div>';
			}
		}

		include dirname( dirname( __DIR__ ) ) . '/templates/my-account-linking.php';
	}

	/**
	 * Intercept unlinking requests originating from frontend pages (e.g. WooCommerce My Account).
	 *
	 * @return void
	 */
	public function handleFrontendUnlinkAction(): void {
		if ( is_admin() || ! is_user_logged_in() ) {
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

		Logger::log( sprintf( 'Unlinked Google Account for user ID [%d] via WooCommerce dashboard.', $userId ), 'INFO' );

		$redirectTo = wc_get_page_permalink( 'myaccount' );
		if ( ! empty( $_GET['redirect_to'] ) ) {
			$redirectTo = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
		}

		wp_safe_redirect( add_query_arg( 'google_login_success', 'unlinked', $redirectTo ) );
		exit;
	}
}



