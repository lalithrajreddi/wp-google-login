<?php
/**
 * Secure Debug Logger.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 *
 * Handles file-based logging for debug and error tracking, sanitizing sensitive data.
 */
class Logger {

	/**
	 * Write a message to the debug log file if debug mode is active.
	 *
	 * @param mixed  $message The message or payload to log.
	 * @param string $level   The log level (e.g., 'INFO', 'WARNING', 'ERROR').
	 * @return void
	 */
	public static function log( mixed $message, string $level = 'INFO' ): void {
		if ( ! Settings::isDebugEnabled() ) {
			return;
		}

		$logFilePath = self::getLogFilePath();
		if ( ! $logFilePath ) {
			return;
		}

		// Format output.
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = wp_json_encode( $message );
		}

		// Redact sensitive tokens and credentials.
		$sanitizedMessage = self::redactSensitiveData( (string) $message );
		$timestamp        = current_time( 'mysql' );
		$logEntry         = sprintf( "[%s] [%s]: %s\n", $timestamp, strtoupper( $level ), $sanitizedMessage );

		// Append to file.
		error_log( $logEntry, 3, $logFilePath ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * Get the absolute file path to the log file.
	 *
	 * Generates the folder and sets up protective .htaccess and index.php.
	 *
	 * @return string|null Absolute path to the log file, or null if write-protected.
	 */
	public static function getLogFilePath(): ?string {
		$uploadDir = wp_upload_dir();
		if ( ! empty( $uploadDir['error'] ) ) {
			return null;
		}

		$logDirectory = trailingslashit( $uploadDir['basedir'] ) . 'google-login-logs';

		// Create directory if not exists.
		if ( ! file_exists( $logDirectory ) ) {
			wp_mkdir_p( $logDirectory );
			self::secureDirectory( $logDirectory );
		}

		// Use a secure suffix stored in database option to prevent file enumeration.
		$logHash = get_option( 'google_login_log_hash' );
		if ( ! $logHash ) {
			$logHash = wp_generate_password( 12, false, false );
			update_option( 'google_login_log_hash', $logHash );
		}

		return $logDirectory . '/debug-' . $logHash . '.log';
	}

	/**
	 * Get the public URL of the log file (for admin download/view).
	 *
	 * @return string|null Public URL, or null on error.
	 */
	public static function getLogFileUrl(): ?string {
		$uploadDir = wp_upload_dir();
		if ( ! empty( $uploadDir['error'] ) ) {
			return null;
		}

		$logHash = get_option( 'google_login_log_hash' );
		if ( ! $logHash ) {
			return null;
		}

		return trailingslashit( $uploadDir['baseurl'] ) . 'google-login-logs/debug-' . $logHash . '.log';
	}

	/**
	 * Clear the debug log.
	 *
	 * @return bool True if cleared successfully, false otherwise.
	 */
	public static function clear(): bool {
		$path = self::getLogFilePath();
		if ( $path && file_exists( $path ) ) {
			return unlink( $path );
		}
		return false;
	}

	/**
	 * Restrict access to the log folder by creating .htaccess and index.php.
	 *
	 * @param string $directory Absolute path to directory.
	 * @return void
	 */
	private static function secureDirectory( string $directory ): void {
		$htaccessPath = $directory . '/.htaccess';
		if ( ! file_exists( $htaccessPath ) ) {
			$rules = "Order deny,allow\nDeny from all\n";
			error_log( $rules, 3, $htaccessPath ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		$indexPhpPath = $directory . '/index.php';
		if ( ! file_exists( $indexPhpPath ) ) {
			error_log( "<?php\n// Silence is golden.\n", 3, $indexPhpPath ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Redact sensitive fields (tokens, secrets) from the log output.
	 *
	 * @param string $text Raw message.
	 * @return string
	 */
	private static function redactSensitiveData( string $text ): string {
		// Redact standard client secrets.
		$text = preg_replace( '/(["\']?client_secret["\']?\s*[:=]\s*["\'])([^"\']+)(["\'])/i', '$1[REDACTED]$3', $text );

		// Redact access, refresh, and ID tokens.
		$text = preg_replace( '/(["\']?(access|refresh|id)_token["\']?\s*[:=]\s*["\'])([^"\']+)(["\'])/i', '$1[REDACTED]$3', $text );

		// Redact authorization headers.
		$text = preg_replace( '/(Authorization\s*:\s*Bearer\s+)([^\s"\']+)/i', '$1[REDACTED]', $text );

		// Redact any code parameter.
		$text = preg_replace( '/([\?&]code=)([^&\s"\']+)/i', '$1[REDACTED]', $text );

		return $text;
	}
}



