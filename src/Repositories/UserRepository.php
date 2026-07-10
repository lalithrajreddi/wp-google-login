<?php
/**
 * User Repository.
 *
 * @package GoogleLogin
 */

namespace GoogleLogin\Repositories;

use GoogleLogin\OAuth\UserProfile;
use WP_User;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class UserRepository
 *
 * Encapsulates all WordPress user actions, including creation, retrieval, and metadata linking.
 */
class UserRepository {

	/**
	 * Find a WordPress user linked to the specified social provider identifier.
	 *
	 * @param string $googleId Google account identifier.
	 * @return WP_User|null The user object or null if not found.
	 */
	public function findUserByGoogleId( string $googleId ): ?WP_User {
		$users = get_users(
			[
				'meta_key'   => '_google_login_google_id',
				'meta_value' => $googleId,
				'number'     => 1,
				'fields'     => 'all',
			]
		);

		return ! empty( $users ) ? $users[0] : null;
	}

	/**
	 * Find a WordPress user by their email address.
	 *
	 * @param string $email Email address to search for.
	 * @return WP_User|null User object or null if not found.
	 */
	public function findUserByEmail( string $email ): ?WP_User {
		$user = get_user_by( 'email', $email );
		return $user instanceof WP_User ? $user : null;
	}

	/**
	 * Link a Google account ID to an existing WordPress user.
	 *
	 * @param int    $userId      WordPress user ID.
	 * @param string $googleId    Google account unique identifier.
	 * @param string $googleEmail Google account email.
	 * @return void
	 */
	public function linkUser( int $userId, string $googleId, string $googleEmail ): void {
		update_user_meta( $userId, '_google_login_google_id', $googleId );
		update_user_meta( $userId, '_google_login_google_email', $googleEmail );
	}

	/**
	 * Unlink the Google account from a WordPress user.
	 *
	 * @param int $userId WordPress user ID.
	 * @return void
	 */
	public function unlinkUser( int $userId ): void {
		delete_user_meta( $userId, '_google_login_google_id' );
		delete_user_meta( $userId, '_google_login_google_email' );
		delete_user_meta( $userId, '_google_login_social_avatar' );
	}

	/**
	 * Save the user's social profile image URL to their meta.
	 *
	 * @param int    $userId   WordPress user ID.
	 * @param string $imageUrl URL to the social avatar image.
	 * @return void
	 */
	public function updateProfileImage( int $userId, string $imageUrl ): void {
		update_user_meta( $userId, '_google_login_social_avatar', esc_url_raw( $imageUrl ) );
	}

	/**
	 * Create a new WordPress user based on their social profile data.
	 *
	 * @param UserProfile $profile Social profile DTO.
	 * @param string      $role    WordPress user role to assign.
	 * @return int|WP_Error User ID on success, WP_Error on failure.
	 */
	public function createUser( UserProfile $profile, string $role ): int|WP_Error {
		$username = $this->generateUniqueUsername( $profile );
		$password = wp_generate_password( 16, true );

		$userData = [
			'user_login'   => $username,
			'user_pass'    => $password,
			'user_email'   => sanitize_email( $profile->email ),
			'first_name'   => sanitize_text_field( $profile->firstName ),
			'last_name'    => sanitize_text_field( $profile->lastName ),
			'display_name' => sanitize_text_field( $profile->displayName ),
			'role'         => $role,
		];

		$userId = wp_insert_user( $userData );

		if ( is_wp_error( $userId ) ) {
			return $userId;
		}

		// Ensure password change is marked as unnecessary or not prompt-forced.
		update_user_meta( $userId, 'google_login_created_via_social', 1 );

		return $userId;
	}

	/**
	 * Generate a unique and sanitized WordPress username based on the social profile details.
	 *
	 * @param UserProfile $profile Social profile.
	 * @return string Unique username.
	 */
	private function generateUniqueUsername( UserProfile $profile ): string {
		$baseUsername = '';

		// Try combining first name and last name.
		if ( ! empty( $profile->firstName ) || ! empty( $profile->lastName ) ) {
			$baseUsername = $profile->firstName . $profile->lastName;
		}

		// Fallback to display name if still empty.
		if ( empty( $baseUsername ) ) {
			$baseUsername = $profile->displayName;
		}

		// Fallback to email prefix if still empty.
		if ( empty( $baseUsername ) ) {
			$parts        = explode( '@', $profile->email );
			$baseUsername = $parts[0] ?? '';
		}

		$baseUsername = sanitize_user( $baseUsername, true );

		// Last resort fallback.
		if ( empty( $baseUsername ) ) {
			$baseUsername = 'social_user';
		}

		$username = $baseUsername;
		$counter  = 1;

		// Keep appending a counter until the username is unique in WP database.
		while ( username_exists( $username ) ) {
			$username = $baseUsername . $counter;
			$counter++;
		}

		return $username;
	}
}



