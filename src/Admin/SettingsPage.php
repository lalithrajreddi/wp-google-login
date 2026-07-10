<?php
/**
 * Admin Settings Page.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Admin;

use GoogleLogin\Core\Settings;
use GoogleLogin\Core\Logger;
use GoogleLogin\OAuth\GoogleProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsPage
 *
 * Renders the admin options panel and registers WordPress settings API groups.
 */
class SettingsPage {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'addAdminMenu' ] );
		add_action( 'admin_init', [ $this, 'registerPluginSettings' ] );
		add_action( 'admin_init', [ $this, 'handleClearLogsAction' ] );
	}

	/**
	 * Add a top-level menu item for Google Login.
	 *
	 * @return void
	 */
	public function addAdminMenu(): void {
		add_menu_page(
			esc_html__( 'Google Login Settings', 'google-login' ),
			'Google Login',
			'manage_options',
			'google-login',
			[ $this, 'renderSettingsPage' ],
			'dashicons-shield',
			80
		);
	}

	/**
	 * Register settings option group via WordPress Settings API.
	 *
	 * @return void
	 */
	public function registerPluginSettings(): void {
		register_setting(
			'google_login_settings_group',
			Settings::OPTION_NAME,
			[
				'sanitize_callback' => [ Settings::class, 'sanitizeSettings' ],
			]
		);
	}

	/**
	 * Handle request to clear the debug log file.
	 *
	 * @return void
	 */
	public function handleClearLogsAction(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['google_login_clear_logs_btn'] ) ) {
			if ( ! isset( $_POST['google_login_clear_logs_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['google_login_clear_logs_nonce'] ), 'google_login_clear_logs' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'google-login' ), esc_html__( 'Access Denied', 'google-login' ), [ 'response' => 403 ] );
			}

			Logger::clear();

			wp_safe_redirect( add_query_arg( [ 'page' => 'google-login', 'google_login_action' => 'logs_cleared' ], admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Render Settings Page markup.
	 *
	 * @return void
	 */
	public function renderSettingsPage(): void {
		// Enqueue admin styles.
		wp_enqueue_style( 'google-login-admin', plugins_url( 'assets/css/google-login-admin.css', dirname( __DIR__ ) ), [], GOOGLE_LOGIN_VERSION );

		// Enqueue copy to clipboard JS.
		wp_enqueue_script( 'google-login-admin-js', plugins_url( 'assets/js/google-login-admin.js', dirname( __DIR__ ) ), [], GOOGLE_LOGIN_VERSION, true );

		$options     = Settings::getAll();
		$redirectUri = GoogleProvider::getCallbackUrl();

		// Fetch logs if debug mode is active.
		$logContent = '';
		$logPath    = Logger::getLogFilePath();
		if ( $logPath && file_exists( $logPath ) ) {
			// Limit view to last 50KB of log to prevent memory exhaustion.
			$fileSize = filesize( $logPath );
			if ( $fileSize > 50000 ) {
				$logContent = esc_html__( '... Log file too large. Showing recent logs ...', 'google-login' ) . "\n\n";
				$fp = fopen( $logPath, 'r' );
				fseek( $fp, -50000, SEEK_END );
				$logContent .= fread( $fp, 50000 );
				fclose( $fp );
			} else {
				$logContent = file_get_contents( $logPath );
			}
		}

		include dirname( dirname( __DIR__ ) ) . '/templates/admin-settings.php';
	}
}



