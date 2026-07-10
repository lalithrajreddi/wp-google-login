# WP Google Login

A professional, high-performance, and secure Google Authentication plugin for WordPress and WooCommerce. Engineered with a modular and extensible architecture, Google Login makes it simple to enable one-click social registration, login, and profile linking on your WordPress site.

## ✨ Features

- **Google OAuth 2.0 Integration**: Safe and compliant social login and sign-up.
- **WooCommerce Compatibility**: Fully integrated with WooCommerce login, registration, checkout, and My Account pages.
- **Account Linking**: Allows logged-in users to link or unlink their Google profiles securely from their dashboard.
- **Stateless CSRF Protection**: Uses cryptographic HMAC signatures bound to user sessions, eliminating server-side session issues.
- **Safe Auto-linking**: Automatically connects returning Google users with existing WordPress profiles using verified email detection.
- **Avatar Syncing**: Intercepts `get_avatar` to sync and load Google profile images cleanly.
- **Obfuscated Debug Logger**: Detailed logs for checking API status, with automatic token/secret redaction.

## 🚀 How to Install (For Regular Users)

If you just want to use this plugin on your WordPress site, follow these steps:

1. Go to the **[Releases](https://github.com/lalithrajreddi/google-login/releases)** page of this repository.
2. Download the latest `wp-social.zip` file (do **not** download the "Source Code" zip).
3. Log in to your WordPress Admin Dashboard.
4. Go to **Plugins > Add New** and click **Upload Plugin**.
5. Upload the `.zip` file you just downloaded and click **Install Now**.
6. Click **Activate Plugin**.

> **Note:** For step-by-step configuration of the Google API keys and plugin settings, please refer to our detailed **[Installation Guide](INSTALL.md)**.

## 💻 Developer Setup (For Contributors)

If you are a developer looking to contribute to the source code or build upon this plugin:

1. Clone this repository directly into your `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/lalithrajreddi/google-login.git wp-social
   ```
2. Navigate to the folder and install dependencies via Composer:
   ```bash
   cd wp-social
   composer install
   ```
3. See **[DEVELOPMENT.md](DEVELOPMENT.md)** for further instructions on extending Google Login with other social providers and running unit tests.

## 📜 License

This project is licensed under the GPLv3 License. See [LICENSE](LICENSE) for details.
