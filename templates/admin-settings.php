<?php
/**
 * Admin Settings Template.
 *
 * @package GoogleLogin
 */

defined( 'ABSPATH' ) || exit;

// Show save success notifications.
if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
	add_settings_error( 'google_login_messages', 'google_login_message', esc_html__( 'Settings Saved', 'google-login' ), 'updated' );
}

if ( isset( $_GET['google_login_action'] ) && 'logs_cleared' === $_GET['google_login_action'] ) {
	add_settings_error( 'google_login_messages', 'google_login_message', esc_html__( 'Debug logs cleared successfully.', 'google-login' ), 'updated' );
}

settings_errors( 'google_login_messages' );
?>

<div class="wrap google-login-admin-wrapper">
	<header class="google-login-header">
		<h1 class="google-login-title">
			<span class="dashicons dashicons-shield google-login-logo-icon"></span>
			<?php esc_html_e( 'Google Login Settings', 'google-login' ); ?>
		</h1>
		<p class="google-login-tagline"><?php esc_html_e( 'Configure secure Google authentication and profile integration for your WordPress site.', 'google-login' ); ?></p>
	</header>

	<h2 class="nav-tab-wrapper google-login-nav-tabs">
		<a href="#tab-google" class="nav-tab nav-tab-active" data-tab="google"><?php esc_html_e( 'Google Configuration', 'google-login' ); ?></a>
		<a href="#tab-settings" class="nav-tab" data-tab="settings"><?php esc_html_e( 'Global Settings', 'google-login' ); ?></a>
		<a href="#tab-logs" class="nav-tab" data-tab="logs"><?php esc_html_e( 'Debug Logs', 'google-login' ); ?></a>
	</h2>

	<form method="post" action="options.php" class="google-login-settings-form">
		<?php settings_fields( 'google_login_settings_group' ); ?>

		<!-- Tab: Google -->
		<div id="tab-google" class="google-login-tab-content active">
			<div class="google-login-card">
				<h3 class="card-title"><?php esc_html_e( 'Google API settings', 'google-login' ); ?></h3>
				<p class="card-desc"><?php esc_html_e( 'To enable Login with Google, create a Google Developer Project, configure your OAuth Consent Screen, and obtain Client Credentials.', 'google-login' ); ?></p>

				<table class="form-table google-login-table">
					<tr>
						<th scope="row"><label for="google_enabled"><?php esc_html_e( 'Enable Google Login', 'google-login' ); ?></label></th>
						<td>
							<label class="google-login-switch">
								<input type="checkbox" id="google_enabled" name="google_login_settings[google_enabled]" value="1" <?php checked( $options['google_enabled'], 1 ); ?>>
								<span class="slider round"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Authorized Redirect URI', 'google-login' ); ?></label></th>
						<td>
							<div class="google-login-copy-uri-container">
								<input type="text" id="google_login_redirect_uri" class="regular-text" value="<?php echo esc_url( $redirectUri ); ?>" readonly>
								<button type="button" class="button google-login-copy-btn" onclick="googleLoginCopyToClipboard()"><?php esc_html_e( 'Copy URI', 'google-login' ); ?></button>
							</div>
							<p class="description"><?php esc_html_e( 'Copy this callback URL and paste it into the "Authorized redirect URIs" section under your Google Cloud console credentials.', 'google-login' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="google_client_id"><?php esc_html_e( 'Client ID', 'google-login' ); ?></label></th>
						<td>
							<input type="text" id="google_client_id" name="google_login_settings[google_client_id]" class="large-text" value="<?php echo esc_attr( $options['google_client_id'] ); ?>" placeholder="e.g. 12345678-abcd.apps.googleusercontent.com">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="google_client_secret"><?php esc_html_e( 'Client Secret', 'google-login' ); ?></label></th>
						<td>
							<input type="password" id="google_client_secret" name="google_login_settings[google_client_secret]" class="large-text" value="<?php echo esc_attr( $options['google_client_secret'] ); ?>" placeholder="••••••••••••••••••••••••••••••••">
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Tab: Settings -->
		<div id="tab-settings" class="google-login-tab-content">
			<div class="google-login-card">
				<h3 class="card-title"><?php esc_html_e( 'Authentication Options', 'google-login' ); ?></h3>

				<table class="form-table google-login-table">
					<tr>
						<th scope="row"><label for="allow_registration"><?php esc_html_e( 'Allow Registration', 'google-login' ); ?></label></th>
						<td>
							<label class="google-login-switch">
								<input type="checkbox" id="allow_registration" name="google_login_settings[allow_registration]" value="1" <?php checked( $options['allow_registration'], 1 ); ?>>
								<span class="slider round"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Automatically create a WordPress account for new visitors logging in via Google.', 'google-login' ); ?>
								<?php 
								$wp_reg_enabled = get_option( 'users_can_register' );
								$wc_reg_enabled = class_exists( 'WooCommerce' ) && ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) || 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) );
								
								if ( ! $wp_reg_enabled && ! $wc_reg_enabled ) : ?>
									<span class="google-login-warning-text"><?php esc_html_e( 'Note: WordPress registration is globally disabled in Settings -> General.', 'google-login' ); ?></span>
								<?php elseif ( ! $wp_reg_enabled && $wc_reg_enabled ) : ?>
									<span class="google-login-warning-text" style="color: #007017; font-weight: 500;"><?php esc_html_e( 'Note: WordPress registration is disabled, but WooCommerce customer registration is enabled. Google Login will allow new users based on WooCommerce settings.', 'google-login' ); ?></span>
								<?php endif; ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="allow_linking"><?php esc_html_e( 'Allow Profile Linking', 'google-login' ); ?></label></th>
						<td>
							<label class="google-login-switch">
								<input type="checkbox" id="allow_linking" name="google_login_settings[allow_linking]" value="1" <?php checked( $options['allow_linking'], 1 ); ?>>
								<span class="slider round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Allow logged-in users to connect or disconnect their social accounts from their profile dashboard.', 'google-login' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="default_role"><?php esc_html_e( 'Default New User Role', 'google-login' ); ?></label></th>
						<td>
							<select id="default_role" name="google_login_settings[default_role]">
								<?php wp_dropdown_roles( $options['default_role'] ); ?>
							</select>
							<p class="description"><?php esc_html_e( 'The role assigned to users registered via Google.', 'google-login' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="login_redirect_type"><?php esc_html_e( 'Login Redirection Behavior', 'google-login' ); ?></label></th>
						<td>
							<select id="login_redirect_type" name="google_login_settings[login_redirect_type]">
								<option value="default" <?php selected( $options['login_redirect_type'], 'default' ); ?>><?php esc_html_e( 'Default (My Account / Dashboard)', 'google-login' ); ?></option>
								<option value="referer" <?php selected( $options['login_redirect_type'], 'referer' ); ?>><?php esc_html_e( 'Previous Page (Referer)', 'google-login' ); ?></option>
								<option value="custom" <?php selected( $options['login_redirect_type'], 'custom' ); ?>><?php esc_html_e( 'Custom URL', 'google-login' ); ?></option>
							</select>
						</td>
					</tr>
					<tr id="custom-redirect-row" style="<?php echo ( 'custom' === $options['login_redirect_type'] ) ? '' : 'display:none;'; ?>">
						<th scope="row"><label for="custom_redirect_url"><?php esc_html_e( 'Custom Redirect URL', 'google-login' ); ?></label></th>
						<td>
							<input type="url" id="custom_redirect_url" name="google_login_settings[custom_redirect_url]" class="regular-text" value="<?php echo esc_url( $options['custom_redirect_url'] ); ?>" placeholder="<?php echo esc_url( home_url( '/welcome' ) ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="debug_enabled"><?php esc_html_e( 'Enable Debug Logs', 'google-login' ); ?></label></th>
						<td>
							<label class="google-login-switch">
								<input type="checkbox" id="debug_enabled" name="google_login_settings[debug_enabled]" value="1" <?php checked( $options['debug_enabled'], 1 ); ?>>
								<span class="slider round"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Record detailed steps of OAuth exchanges to troubleshoot connection errors. Logs are fully sanitized to protect secrets.', 'google-login' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="google-login-submit-bar">
			<?php submit_button( esc_html__( 'Save Options', 'google-login' ), 'primary', 'submit', false ); ?>
		</div>
	</form>

	<!-- Tab: Logs -->
	<div id="tab-logs" class="google-login-tab-content">
		<div class="google-login-card">
			<div class="google-login-logs-header">
				<div>
					<h3 class="card-title"><?php esc_html_e( 'OAuth Debug and Logs', 'google-login' ); ?></h3>
					<p class="card-desc"><?php esc_html_e( 'View real-time, token-sanitized log events for checking Google API responses.', 'google-login' ); ?></p>
				</div>
				<div class="google-login-logs-actions">
					<form method="post" action="" style="display:inline;">
						<?php wp_nonce_field( 'google_login_clear_logs', 'google_login_clear_logs_nonce' ); ?>
						<button type="submit" name="google_login_clear_logs_btn" class="button google-login-danger-btn" onclick="return confirm('<?php echo esc_js( esc_html__( 'Are you sure you want to clear all logs?', 'google-login' ) ); ?>')">
							<?php esc_html_e( 'Clear Log File', 'google-login' ); ?>
						</button>
					</form>
				</div>
			</div>

			<div class="google-login-log-viewer">
				<textarea readonly class="large-text code" rows="15" placeholder="<?php esc_html_e( 'No log entries found. Make sure Debug Mode is enabled and users are logging in.', 'google-login' ); ?>"><?php echo esc_textarea( $logContent ); ?></textarea>
			</div>
		</div>
	</div>
</div>



