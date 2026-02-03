# xcleanup

## Overview
xcleanup is a scheduled CLI tool that cleans files and directories on servers using safe configuration rules. It supports confirmation mode, dry-run, logging, and notifications after completion.

## Objectives
- Free disk space based on configuration rules.
- Prevent accidental deletions via confirmation and deletion history.
- Provide full reports and notifications.

## Key features
- Scan by allowed and excluded paths.
- Filter by file age and deletion limits.
- Emergency mode when disk space is low.
- Dry-run to preview the cleanup plan.
- JSON or text logging.
- Email/Slack/Discord/Telegram notifications.

## Quick setup
1. Install PHP 8.2+ and Composer.
2. Install dependencies:
   - `composer install`
3. Copy configuration:
   - `cp config/config.php.example config/config.php`
4. (Optional) Set environment variables in `.env`.

## Basic usage
- Run with defaults:
  - `php bin/cleanup`
- Run with a custom config:
  - `php bin/cleanup --config=/etc/xcleanup/config.php`
- Dry-run:
  - `php bin/cleanup --dry-run`
- Skip confirmation:
  - `php bin/cleanup --quiet`

## Safety notes
- Always verify `allowed_paths` and `excluded_paths`.
- Prefer dry-run before actual execution.
- Avoid root privileges unless required.

## Related documentation
- [Installation](INSTALL.md)
- [Architecture](ARCHITECTURE.md)
- [Testing](TESTING.md)
- [Deployment](DEPLOYMENT.md)
- [Operations](guides/operation-guide.md)

## Contact
- Website: https://xnetvn.com/
- Email: license@xnetvn.net
