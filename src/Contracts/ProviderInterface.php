<?php
/**
 * Provider Interface.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Contracts;

use GoogleLogin\OAuth\UserProfile;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ProviderInterface
 *
 * Defines the contract that all social authentication providers must implement.
 */
interface ProviderInterface {

	/**
	 * Get the unique provider identifier key (e.g., 'google', 'facebook').
	 *
	 * @return string
	 */
	public function getKey(): string;

	/**
	 * Get the user-friendly label for the provider (e.g., 'Google', 'Facebook').
	 *
	 * @return string
	 */
	public function getLabel(): string;

	/**
	 * Check if the provider is currently enabled in settings.
	 *
	 * @return bool
	 */
	public function isEnabled(): bool;

	/**
	 * Generate the authorization redirect URL for starting the OAuth flow.
	 *
	 * @param string $state Secure verification state parameter.
	 * @return string
	 */
	public function getAuthorizationUrl( string $state ): string;

	/**
	 * Exchange the authorization code received from the callback for access and refresh tokens.
	 *
	 * @param string $code Authorization code from callback.
	 * @return array{access_token: string, refresh_token?: string, expires_in?: int, id_token?: string}
	 * @throws \Exception If the token exchange fails.
	 */
	public function exchangeCodeForTokens( string $code ): array;

	/**
	 * Fetch the user's profile details using the access token.
	 *
	 * @param array $tokens Array containing access token and metadata.
	 * @return UserProfile Standardized user profile DTO.
	 * @throws \Exception If retrieving the profile fails.
	 */
	public function getUserProfile( array $tokens ): UserProfile;
}



