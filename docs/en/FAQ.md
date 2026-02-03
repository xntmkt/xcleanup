# Frequently Asked Questions

## 1. Can the tool delete the wrong files?
- Not if `allowed_paths` and `excluded_paths` are correct. Always run dry-run first.

## 2. Can it run via cron?
- Yes. See [INSTALL](INSTALL.md).

## 3. How do I enable notifications?
- Configure environment variables in `.env` and enable `notifications.*.enabled`.

## 4. Is Windows supported?
- The tool is optimized for Linux/Unix. Windows is not recommended.
