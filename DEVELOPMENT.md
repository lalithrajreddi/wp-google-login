# Developer Documentation

This document explains the architecture of the **Google Login** plugin, how to run unit tests, and how to extend the plugin to support additional social providers.

## Architecture Overview

Google Login follows SOLID design principles, modularity, and strict OOP practices.

- **`GoogleLogin\Contracts`**: Defines the shared contract `ProviderInterface` for login providers.
- **`GoogleLogin\Core`**: Coordinates settings, bootstrapping, and logs.
- **`GoogleLogin\OAuth`**: Manages state generation/verification (CSRF protection) and specific provider APIs.
- **`GoogleLogin\Repositories`**: Separates database concerns (lookup, updates, user creation) from controllers.
- **`GoogleLogin\Controllers`**: Standardizes callback endpoints and orchestrates the user login/linking flows.
- **`GoogleLogin\Integrations`**: Hooks into WordPress core features (`wp-login.php`, get_avatar filters) and WooCommerce forms.

## Extensibility: Adding a New Social Provider

Adding support for other social networks (e.g., Facebook, GitHub, Apple) is straightforward:

### Step 1: Implement `ProviderInterface`

Create a new provider class in `src/OAuth/` that implements `GoogleLogin\Contracts\ProviderInterface`. For example:

```php
namespace GoogleLogin\OAuth;

use GoogleLogin\Contracts\ProviderInterface;

class GitHubProvider implements ProviderInterface {
    public function getKey(): string { return 'github'; }
    public function getLabel(): string { return 'GitHub'; }
    public function isEnabled(): bool { /* ... */ }
    public function getAuthorizationUrl(string $state): string { /* ... */ }
    public function exchangeCodeForTokens(string $code): array { /* ... */ }
    public function getUserProfile(array $tokens): UserProfile { /* ... */ }
}
```

### Step 2: Update Settings Options

Add configuration keys (e.g. `github_enabled`, `github_client_id`, `github_client_secret`) inside:
- `src/Core/Settings.php` (update default array and sanitization logic)
- `src/Admin/SettingsPage.php` (add form fields in settings panel view)

### Step 3: Register in Controllers and Integrations

- Update `OAuthController::handleRedirectTrigger` and `handleCallback` to check and instantiate your new provider if its key matches.
- Render the new button inside `templates/login-buttons.php` and connection widgets.

---

## Running Unit Tests

To run the unit tests, install development dependencies and run the PHPUnit test suite:

1. Install Composer dev dependencies:
   ```bash
   composer install
   ```
2. Execute the tests:
   ```bash
   ./vendor/bin/phpunit
   ```
3. Test files are located in the `tests/` directory:
   - `tests/bootstrap.php` contains the WordPress mock definitions.
   - Core and provider logic are covered inside `tests/Core/` and `tests/OAuth/`.


