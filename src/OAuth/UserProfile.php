<?php
/**
 * User Profile DTO.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\OAuth;

defined( 'ABSPATH' ) || exit;

/**
 * Class UserProfile
 *
 * Data Transfer Object representing a standardized user profile from a social provider.
 */
class UserProfile {

	/**
	 * Constructor.
	 *
	 * Uses PHP 8.1 constructor property promotion with readonly properties for immutable data.
	 *
	 * @param string      $id          Social provider's unique user identifier.
	 * @param string      $email       User's email address.
	 * @param string      $firstName   User's first name.
	 * @param string      $lastName    User's last name.
	 * @param string      $displayName User's display or full name.
	 * @param string|null $avatarUrl   URL to the user's social profile picture.
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $email,
		public readonly string $firstName,
		public readonly string $lastName,
		public readonly string $displayName,
		public readonly ?string $avatarUrl = null
	) {}
}



