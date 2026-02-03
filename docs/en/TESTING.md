# Testing

## Goals
- Ensure filtering and planning logic are correct.
- Avoid deleting critical files.
- Ensure notifiers work when enabled.

## Recommended test types
- Unit tests: `CleanupPlanner`, `PathMatcher`, `CleanupStateStore`.
- Integration tests: `CleanupExecutor` with a simulated filesystem.
- Smoke tests: run `bin/cleanup --dry-run`.

## Running tests (planned)
- `composer test`
- `composer stan`
- `composer lint`

## Notes
- Use a temporary directory under `/tmp` to avoid production impact.
- Always run dry-run before actual execution.
