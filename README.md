# Google Login

Google Login is a professional, high-performance, and secure Google Authentication plugin for WordPress and WooCommerce. Engineered with a modular and extensible architecture, Google Login makes it simple to enable one-click social registration, login, and profile linking on your WordPress site.

## Features

- **Google OAuth 2.0 Integration**: Safe and compliant social login and sign-up.
- **WooCommerce Compatibility**: Fully integrated with WooCommerce login, registration, checkout, and My Account pages.
- **Account Linking**: Allows logged-in users to link or unlink their Google profiles securely from their dashboard.
- **Stateless CSRF Protection**: Uses cryptographic HMAC signatures bound to user sessions, eliminating server-side session issues.
- **Safe Auto-linking**: Automatically connects returning Google users with existing WordPress profiles using verified email detection.
- **Avatar Syncing**: Intercepts get_avatar to sync and load Google profile images cleanly.
- **Obfuscated Debug Logger**: Detailed logs for checking API status, with automatic token/secret redaction.

## Installation

See [INSTALL.md](INSTALL.md) for step-by-step setup guides.

## Developer Setup

See [DEVELOPMENT.md](DEVELOPMENT.md) for instructions on extending Google Login with other social providers (Facebook, Apple, GitHub, etc.) and running unit tests.

## License

This project is licensed under the GPLv3 License. See [LICENSE](LICENSE) for details.


