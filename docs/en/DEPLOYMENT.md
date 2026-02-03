# Deployment

## Deployment model
- CLI tool scheduled via Cron/Systemd Timer.

## Standard procedure
1. Deploy code to `/opt/xcleanup` (or standard directory).
2. Configure `config.php` and `.env`.
3. Set permissions for the log directory.
4. Run a dry-run.
5. Enable the scheduled execution.

## Monitoring
- Monitor the `cleanup.log` file.
- Alert when disk free is below the threshold.
