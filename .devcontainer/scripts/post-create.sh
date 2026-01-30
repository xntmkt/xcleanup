#!/usr/bin/env bash
# Copyright (c) 2026 xNetVN Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# Website: https://xnetvn.com/
# Contact: license@xnetvn.net

set -euo pipefail
IFS=$'\n\t'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
SEED_MARKER="$PROJECT_ROOT/.local/tmp/seed-data.done"

log() {
    echo "[post-create] $*"
}

log "Preparing devcontainer workspace."

sudo mkdir -p "$PROJECT_ROOT/vendor" /tmp/composer-cache
sudo chown -R vscode:vscode "$PROJECT_ROOT/vendor" /tmp/composer-cache

if [[ ! -f "$PROJECT_ROOT/config/config.php" ]]; then
    log "Creating config/config.php from example."
    cp "$PROJECT_ROOT/config/config.php.example" "$PROJECT_ROOT/config/config.php"
fi

sudo mkdir -p /var/log/xcleanup
sudo chown -R vscode:vscode /var/log/xcleanup

if [[ ! -f /var/log/xcleanup/state.json ]]; then
    log "Initializing state file."
    echo '{}' > /var/log/xcleanup/state.json
fi

if [[ -f "$PROJECT_ROOT/composer.json" ]]; then
    log "Installing Composer dependencies."
    composer install --no-interaction --prefer-dist
fi

mkdir -p "$(dirname "$SEED_MARKER")"
if [[ ! -f "$SEED_MARKER" ]]; then
    log "Seeding sample data."
    bash "$SCRIPT_DIR/seed-data.sh"
    date -u +"%Y-%m-%dT%H:%M:%SZ" > "$SEED_MARKER"
else
    log "Seed data already created."
fi

log "Post-create tasks completed."
