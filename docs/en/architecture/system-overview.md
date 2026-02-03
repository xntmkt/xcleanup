# Architecture Overview

## Components
- CLI Entrypoint: `bin/cleanup`
- Application Layer: `src/Application/*`
- Domain Exceptions: `src/Domain/Exception/*`

## Data flow
1. Load configuration.
2. Read disk usage.
3. Scan and filter files.
4. Build a plan.
5. Confirm.
6. Execute and log.
7. Notify.
