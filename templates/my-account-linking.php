<?php
/**
 * WooCommerce My Account Social Linking Template.
 *
 * @package GoogleLogin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="google-login-my-account-section">
	<h3 class="google-login-section-title"><?php esc_html_e( 'Social Accounts Connection', 'google-login' ); ?></h3>
	<p class="google-login-section-desc"><?php esc_html_e( 'Connect your account with Google for fast and secure one-click sign in.', 'google-login' ); ?></p>

	<div class="google-login-connection-card">
		<div class="google-login-card-icon-container">
			<svg class="google-login-google-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="36px" height="36px">
				<path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
				<path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
				<path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
				<path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.221,4.146-4.044,5.496l6.19,5.238C41.139,35.507,44,30.134,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
			</svg>
		</div>

		<div class="google-login-card-details">
			<h4 class="google-login-provider-title"><?php esc_html_e( 'Google', 'google-login' ); ?></h4>
			<?php if ( $isLinked ) : ?>
				<p class="google-login-provider-status text-success"><?php printf( esc_html__( 'Connected to %s', 'google-login' ), esc_html( $googleEmail ) ); ?></p>
			<?php else : ?>
				<p class="google-login-provider-status text-muted"><?php esc_html_e( 'Not connected', 'google-login' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="google-login-card-actions">
			<?php if ( $isLinked ) : ?>
				<a href="<?php echo esc_url( $unlinkUrl ); ?>" class="google-login-wc-btn disconnect" onclick="return confirm('<?php echo esc_js( esc_html__( 'Are you sure you want to disconnect Google login?', 'google-login' ) ); ?>')">
					<?php esc_html_e( 'Disconnect', 'google-login' ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( $linkUrl ); ?>" class="google-login-wc-btn connect">
					<?php esc_html_e( 'Connect', 'google-login' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>



