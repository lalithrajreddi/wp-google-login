<?php
/**
 * Test Bootstrap File.
 *
 * Mocks core WordPress functions so unit tests can run standalone.
 *
 * @package GoogleLogin
 */

// Define mock ABSPATH.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Require Autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Mock translation helpers.
if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

// Mock Option database.
$GLOBALS['wp_mock_options'] = [];

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, mixed $default = false ): mixed {
		return $GLOBALS['wp_mock_options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
		$GLOBALS['wp_mock_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $option ): bool {
		unset( $GLOBALS['wp_mock_options'][ $option ] );
		return true;
	}
}

// Mock sanitization functions.
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( sanitize_meta( '', $str, '' ) );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( string $email ): string {
		return trim( $email );
	}
}

if ( ! function_exists( 'sanitize_user' ) ) {
	function sanitize_user( string $username, bool $strict = false ): string {
		return preg_replace( '/[^a-zA-Z0-9_\-\.\@]/', '', $username );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return $url;
	}
}

if ( ! function_exists( 'sanitize_meta' ) ) {
	function sanitize_meta( string $meta_key, mixed $meta_value, string $object_type ): mixed {
		return is_string( $meta_value ) ? strip_tags( $meta_value ) : $meta_value;
	}
}

// Mock WordPress Roles API.
if ( ! function_exists( 'wp_roles' ) ) {
	function wp_roles(): object {
		$mock = new stdClass();
		$mock->roles = [
			'administrator' => [ 'name' => 'Administrator' ],
			'editor'        => [ 'name' => 'Editor' ],
			'subscriber'    => [ 'name' => 'Subscriber' ],
		];
		return $mock;
	}
}

// Mock Redirect & URL functions.
if ( ! function_exists( 'get_rest_url' ) ) {
	function get_rest_url( ?int $blog_id = null, string $path = '', string $scheme = 'rest' ): string {
		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( string $path = '' ): string {
		return 'https://example.com/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( int $length = 12, bool $special_chars = true, bool $extra_special_chars = false ): string {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		return substr( str_shuffle( $chars ), 0, $length );
	}
}



