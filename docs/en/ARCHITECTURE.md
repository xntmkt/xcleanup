# System Architecture

## Overview
The project follows a simple layered model:
- `Application`: business workflow and infrastructure orchestration.
- `Domain`: core exceptions and rules.

## Primary Flow
1. `CleanupCommand` reads the configuration.
2. `DiskUsageReader` captures disk usage.
3. `CleanupPlanner` builds the cleanup plan.
4. User confirmation (if enabled).
5. `CleanupExecutor` performs deletions.
6. `CleanupReport` produces the report.
7. `NotifierComposite` delivers notifications.

## Key Components
- **ConfigLoader:** loads and validates configuration.
- **FilesystemScanner:** scans files/directories.
- **PathMatcher:** filters allow/exclude paths.
- **CleanupStateStore:** prevents repeated deletion within a window.
- **LoggerFactory:** normalizes logging.

## Extensibility
- Add a new notifier by implementing `NotifierInterface`.
- Extend filtering strategies by enhancing `CleanupPlanner`.