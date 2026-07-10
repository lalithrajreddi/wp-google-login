<?php
/**
 * Main Application Bootstrapper.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Core;

use GoogleLogin\Admin\SettingsPage;
use GoogleLogin\Controllers\OAuthController;
use GoogleLogin\Integrations\WordPressIntegration;
use GoogleLogin\Integrations\WooCommerceIntegration;
use GoogleLogin\Repositories\UserRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Central coordinator (Singleton) that orchestrates settings, controllers, and integrations.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * User repository instance.
	 *
	 * @var UserRepository
	 */
	private UserRepository $userRepository;

	/**
	 * OAuth controller instance.
	 *
	 * @var OAuthController
	 */
	private OAuthController $oauthController;

	/**
	 * WordPress integration instance.
	 *
	 * @var WordPressIntegration
	 */
	private WordPressIntegration $wpIntegration;

	/**
	 * WooCommerce integration instance.
	 *
	 * @var WooCommerceIntegration
	 */
	private WooCommerceIntegration $wcIntegration;

	/**
	 * Admin settings page instance.
	 *
	 * @var SettingsPage
	 */
	private SettingsPage $settingsPage;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Private constructor to enforce Singleton pattern.
	 */
	private function __construct() {
		// Initialize the database repository.
		$this->userRepository = new UserRepository();

		// Instantiate controllers and inject the user repository.
		$this->oauthController = new OAuthController( $this->userRepository );

		// Instantiate integrations.
		$this->wpIntegration = new WordPressIntegration( $this->userRepository );
		$this->wcIntegration = new WooCommerceIntegration( $this->userRepository );

		// Instantiate administrative pages.
		$this->settingsPage = new SettingsPage();
	}

	/**
	 * Boot all plugin components.
	 *
	 * Hooked in main entrypoint on plugins_loaded.
	 *
	 * @return void
	 */
	public function run(): void {
		// Initialize admin pages.
		if ( is_admin() ) {
			$this->settingsPage->init();
		}

		// Initialize OAuth routing controller.
		$this->oauthController->init();

		// Initialize core WordPress hooks.
		$this->wpIntegration->init();

		// Initialize WooCommerce hooks if active.
		$this->wcIntegration->init();

		// Enable translations support.
		add_action( 'init', [ $this, 'loadTextdomain' ] );
	}

	/**
	 * Load translation textdomain for i18n support.
	 *
	 * @return void
	 */
	public function loadTextdomain(): void {
		load_plugin_textdomain(
			'google-login',
			false,
			dirname( dirname( __DIR__ ) ) . '/languages/'
		);
	}
}



