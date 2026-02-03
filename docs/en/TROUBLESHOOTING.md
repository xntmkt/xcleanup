# Troubleshooting

## Common errors
- **Config file not found:** verify the `--config` path.
- **Unable to create log directory:** check write permissions.
- **No items to delete:** verify `allowed_paths` or `min_age_seconds`.

## Diagnostic checklist
1. Verify the active configuration.
2. Check log directory permissions.
3. Run `--dry-run` to inspect the plan.
4. Review JSON logs.

## Recovery
- Use the detailed report to identify deleted files.
- Restore from backup if needed.