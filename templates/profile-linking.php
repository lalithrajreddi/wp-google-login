<?php
/**
 * User Profile Social Linking Admin Template.
 *
 * @package GoogleLogin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="google-login-profile-linking-section">
	<h2><?php esc_html_e( 'Google Login Connections', 'google-login' ); ?></h2>
	<table class="form-table google-login-table">
		<tr>
			<th>
				<label><?php esc_html_e( 'Google Account', 'google-login' ); ?></label>
			</th>
			<td>
				<?php if ( $isLinked ) : ?>
					<div class="google-login-connection-status connected">
						<span class="google-login-status-badge success">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php printf( esc_html__( 'Connected (%s)', 'google-login' ), esc_html( $googleEmail ) ); ?>
						</span>
						<a href="<?php echo esc_url( $unlinkUrl ); ?>" class="button google-login-unlink-btn" onclick="return confirm('<?php echo esc_js( esc_html__( 'Are you sure you want to disconnect your Google account?', 'google-login' ) ); ?>')">
							<?php esc_html_e( 'Disconnect', 'google-login' ); ?>
						</a>
					</div>
				<?php else : ?>
					<div class="google-login-connection-status disconnected">
						<span class="google-login-status-badge warning">
							<span class="dashicons dashicons-warning"></span>
							<?php esc_html_e( 'Not Connected', 'google-login' ); ?>
						</span>
						<a href="<?php echo esc_url( $linkUrl ); ?>" class="button google-login-link-btn button-primary">
							<?php esc_html_e( 'Connect Google Account', 'google-login' ); ?>
						</a>
					</div>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Connect your account to login instantly with Google next time.', 'google-login' ); ?></p>
			</td>
		</tr>
	</table>
</div>



