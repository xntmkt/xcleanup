# DR-001: Initial Design

## Context
We needed a simple CLI tool to clean files on a schedule.

## Decision
- Use Symfony Console for the CLI.
- Separate Application/Domain layers.
- Use Monolog for logging.

## Consequences
- Easy to extend notifiers.
- Requires clear operational documentation.
