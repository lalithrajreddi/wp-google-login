<?php
/**
 * Plugin Name: Google Login
 * Plugin URI: https://lalithrajreddi.github.io
 * Description: A professional, secure, and extensible Google authentication system for WordPress and WooCommerce.
 * Version: 1.1.0
 * Author: Lalith Raj Reddi
 * Author URI: https://lalithrajreddi.github.io
 * Text Domain: google-login
 * Domain Path: /languages/
 * License: GPLv3
 * Requires PHP: 8.1
 * Requires at least: 6.0
 *
 * @package GoogleLogin
 */

defined( 'ABSPATH' ) || exit;

// Define plugin version constant.
define( 'GOOGLE_LOGIN_VERSION', '1.1.0' );

// Verify PHP version requirement (PHP 8.1+).
if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				sprintf(
					/* translators: 1: Current PHP version, 2: Required PHP version */
					esc_html__( 'Google Login requires PHP version %2$s or higher to run. Your server is currently running PHP version %1$s.', 'google-login' ),
					esc_html( PHP_VERSION ),
					'8.1'
				)
			);
		}
	);
	return;
}

// Load Composer Autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// Fallback notice if composer dependencies have not been installed.
	add_action(
		'admin_notices',
		function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Google Login dependencies are missing. Please run "composer install" inside the plugin directory.', 'google-login' )
			);
		}
	);
	return;
}

/**
 * Register plugin activation hooks.
 */
register_activation_hook(
	__FILE__,
	function () {
		// Flush rewrite rules on activation to ensure REST API endpoints work.
		flush_rewrite_rules();

		// Set default settings if not already set.
		if ( ! get_option( 'google_login_settings' ) ) {
			update_option(
				'google_login_settings',
				[
					'google_enabled'       => 0,
					'google_client_id'      => '',
					'google_client_secret'  => '',
					'allow_registration'   => 1,
					'allow_linking'        => 1,
					'default_role'         => 'subscriber',
					'login_redirect_type'  => 'default',
					'custom_redirect_url'  => '',
					'debug_enabled'        => 0,
				]
			);
		}
	}
);

/**
 * Register plugin deactivation hooks.
 */
register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);

// Boot the plugin.
add_action(
	'plugins_loaded',
	function () {
		\GoogleLogin\Core\Plugin::getInstance()->run();
	}
);



