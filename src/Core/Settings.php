<?php
/**
 * Plugin Settings Manager.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * Manages configuration options, sanitization, and default values.
 */
class Settings {

	/**
	 * Settings database option key name.
	 */
	public const OPTION_NAME = 'google_login_settings';

	/**
	 * Get all settings options.
	 *
	 * @return array
	 */
	public static function getAll(): array {
		$defaults = self::getDefaults();
		$options  = get_option( self::OPTION_NAME, [] );

		return array_merge( $defaults, is_array( $options ) ? $options : [] );
	}

	/**
	 * Get a specific settings value by key.
	 *
	 * @param string $key     Setting key name.
	 * @param mixed  $default Optional custom fallback default.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$options = self::getAll();
		return $options[ $key ] ?? ( $default ?? ( self::getDefaults()[ $key ] ?? null ) );
	}

	/**
	 * Update a specific setting value.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value New value.
	 * @return bool
	 */
	public static function update( string $key, mixed $value ): bool {
		$options         = self::getAll();
		$options[ $key ] = $value;
		return update_option( self::OPTION_NAME, self::sanitizeSettings( $options ) );
	}

	/**
	 * Verify if Google Login is enabled.
	 *
	 * @return bool
	 */
	public static function isGoogleEnabled(): bool {
		return (bool) self::get( 'google_enabled', false ) &&
			! empty( self::get( 'google_client_id' ) ) &&
			! empty( self::get( 'google_client_secret' ) );
	}

	/**
	 * Check if registration/account creation is allowed via social login.
	 *
	 * @return bool
	 */
	public static function isRegistrationEnabled(): bool {
		return (bool) self::get( 'allow_registration', true ) && get_option( 'users_can_register' );
	}

	/**
	 * Check if account linking is enabled for logged-in accounts.
	 *
	 * @return bool
	 */
	public static function isLinkingEnabled(): bool {
		return (bool) self::get( 'allow_linking', true );
	}

	/**
	 * Verify if debug mode is active.
	 *
	 * @return bool
	 */
	public static function isDebugEnabled(): bool {
		return (bool) self::get( 'debug_enabled', false );
	}

	/**
	 * Get default WordPress user role for newly registered social accounts.
	 *
	 * @return string
	 */
	public static function getDefaultRole(): string {
		$role = self::get( 'default_role', 'subscriber' );
		return self::isValidRole( $role ) ? $role : 'subscriber';
	}

	/**
	 * Validate and sanitize all settings values.
	 *
	 * @param array $input Raw inputs array.
	 * @return array Sanitized settings array.
	 */
	public static function sanitizeSettings( array $input ): array {
		$sanitized = [];

		$sanitized['google_enabled']      = ! empty( $input['google_enabled'] ) ? 1 : 0;
		$sanitized['google_client_id']     = isset( $input['google_client_id'] ) ? sanitize_text_field( trim( $input['google_client_id'] ) ) : '';
		$sanitized['google_client_secret'] = isset( $input['google_client_secret'] ) ? sanitize_text_field( trim( $input['google_client_secret'] ) ) : '';

		$sanitized['allow_registration'] = ! empty( $input['allow_registration'] ) ? 1 : 0;
		$sanitized['allow_linking']      = ! empty( $input['allow_linking'] ) ? 1 : 0;
		$sanitized['debug_enabled']      = ! empty( $input['debug_enabled'] ) ? 1 : 0;

		$role = isset( $input['default_role'] ) ? sanitize_text_field( $input['default_role'] ) : 'subscriber';
		$sanitized['default_role'] = self::isValidRole( $role ) ? $role : 'subscriber';

		$redirectType = isset( $input['login_redirect_type'] ) ? sanitize_text_field( $input['login_redirect_type'] ) : 'default';
		if ( ! in_array( $redirectType, [ 'default', 'referer', 'custom' ], true ) ) {
			$redirectType = 'default';
		}
		$sanitized['login_redirect_type'] = $redirectType;

		$customUrl = isset( $input['custom_redirect_url'] ) ? esc_url_raw( trim( $input['custom_redirect_url'] ) ) : '';
		$sanitized['custom_redirect_url'] = $customUrl;

		return $sanitized;
	}

	/**
	 * Define default configuration values.
	 *
	 * @return array
	 */
	private static function getDefaults(): array {
		return [
			'google_enabled'       => 0,
			'google_client_id'      => '',
			'google_client_secret'  => '',
			'allow_registration'   => 1,
			'allow_linking'        => 1,
			'default_role'         => 'subscriber',
			'login_redirect_type'  => 'default',
			'custom_redirect_url'  => '',
			'debug_enabled'        => 0,
		];
	}

	/**
	 * Validate that a string matches an existing WordPress user role.
	 *
	 * @param string $role User role key.
	 * @return bool
	 */
	private static function isValidRole( string $role ): bool {
		$wpRoles = wp_roles();
		return $wpRoles && is_array( $wpRoles->roles ) && array_key_exists( $role, $wpRoles->roles );
	}
}



