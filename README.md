# xcleanup

## Overview
xcleanup is a scheduled CLI tool that cleans files and directories on servers using safe configuration rules. It supports confirmation mode, dry-run, logging, and notifications after completion.

## Key features
- Scan by allowed and excluded paths.
- Filter by file age and deletion limits.
- Emergency mode when disk space is low.
- Dry-run to preview the cleanup plan.
- JSON or text logging.
- Email/Slack/Discord/Telegram notifications.

## Installation
1. Install PHP 8.2+ and Composer.
2. Install dependencies:
	 - `composer install`
3. Copy configuration:
	 - `cp config/config.php.example config/config.php`
4. (Optional) Set environment variables in `.env`.

## Usage
- Run with defaults:
	- `php bin/cleanup`
- Run with a custom config:
	- `php bin/cleanup --config=/etc/xcleanup/config.php`
- Dry-run:
	- `php bin/cleanup --dry-run`
- Skip confirmation:
	- `php bin/cleanup --quiet`

## Configuration
- Edit `config/config.php` for allowed/excluded paths and retention rules.
- Use `.env` for notifier credentials.

## Running tests
- `composer test`
- `composer stan`
- `composer lint`

## Contributing
See [CONTRIBUTING.md](CONTRIBUTING.md).

## License
Apache License 2.0. See [LICENSE](LICENSE).
