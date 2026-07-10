# Installation Guide

Follow these steps to install and configure the Google Login plugin.

## Prerequisites

- PHP 8.1 or higher
- WordPress 6.0 or higher
- Composer (for dependency autoloading)
- WooCommerce (optional, for ecommerce integration)

## Step 1: Install Plugin

1. Upload the `google-login` plugin folder to your WordPress plugins directory (typically `wp-content/plugins/`).
2. Open your terminal, navigate to the plugin folder, and run:
   ```bash
   composer install --no-dev
   ```
   *Note: This generates the PSR-4 autoloader files.*
3. Go to the **Plugins** page in your WordPress Admin Dashboard and activate **Google Login**.

## Step 2: Configure Google Cloud Project

To enable Google sign-in, you must create client credentials in the Google Cloud Console:

1. Open the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project or select an existing one.
3. Search for **OAuth consent screen** in the search bar:
   - Choose **External** user type and fill out your application's basic info.
   - Add the scopes `openid`, `../auth/userinfo.email`, and `../auth/userinfo.profile`.
4. Go to **Credentials**:
   - Click **Create Credentials** -> **OAuth client ID**.
   - Select **Web application** as the application type.
   - Under **Authorized JavaScript origins**, add your website URL (e.g., `https://example.com`).
   - Under **Authorized redirect URIs**, copy and paste the redirect URI shown in your **Google Login Settings** panel (typically `https://example.com/wp-json/google-login/v1/callback/google`).
5. Copy the generated **Client ID** and **Client Secret**.

## Step 3: Configure Google Login Settings

1. In your WordPress Admin Dashboard, navigate to the new top-level **Google Login** menu.
2. In the **Google Configuration** tab:
   - Check **Enable Google Login**.
   - Enter your **Client ID**.
   - Enter your **Client Secret**.
3. Under the **Global Settings** tab:
   - Choose whether to allow automatic registration and profile linking.
   - Choose the default user role and login redirect behavior.
   - Enable **Debug Logs** if you wish to record authentication events.
4. Click **Save Options**.

Your site is now ready for Google Social Sign-In!


