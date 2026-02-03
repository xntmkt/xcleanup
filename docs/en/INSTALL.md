# Installation

## System requirements
- PHP 8.2 or later
- Composer 2.x
- Read/write access to the log directory (`logging.directory`)

## Install dependencies
1. Install Composer dependencies:
   - `composer install`
2. Verify the CLI command:
   - `php bin/cleanup --help`

## Configuration
1. Copy the example configuration:
   - `cp config/config.php.example config/config.php`
2. Customize paths:
   - `paths.allowed_paths`
   - `paths.excluded_paths`
3. Configure `.env` (if used):
   - See `.env.example`

## Dry-run verification
- `php bin/cleanup --dry-run`

## Schedule execution
Cron example (daily at 2 AM):
```
0 2 * * * /usr/bin/php /opt/xcleanup/bin/cleanup --config=/etc/xcleanup/config.php
```

## Uninstall
- Remove the project directory and logs (if required) per the data retention policy.
