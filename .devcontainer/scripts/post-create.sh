#!/usr/bin/env bash
# Copyright 2025 xNetVN Inc.
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

set -euo pipefail
IFS=$'\n\t'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR:-/tmp/composer-cache}"
SEED_DATA_ENABLED="${SEED_DATA_ENABLED:-false}"

log_info() {
    printf '[INFO] %s - %s\n' "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" "$1"
}

log_error() {
    printf '[ERROR] %s - %s\n' "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" "$1" >&2
}

require_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        log_error "Missing required command: $1"
        exit 1
    fi
}

ensure_directory() {
    if [[ ! -d "$1" ]]; then
        mkdir -p "$1"
    fi
}

main() {
    log_info "Starting post-create tasks."
    require_command "composer"

    ensure_directory "${COMPOSER_CACHE_DIR}"
    export COMPOSER_CACHE_DIR

    log_info "Installing PHP dependencies with Composer."
    (cd "${PROJECT_ROOT}" && composer install --no-interaction --prefer-dist)

    if [[ "${SEED_DATA_ENABLED}" == "true" ]]; then
        if [[ -f "${SCRIPT_DIR}/seed-data.sh" ]]; then
            log_info "Seeding development data."
            bash "${SCRIPT_DIR}/seed-data.sh"
        else
            log_info "Seed data script not found; skipping."
        fi
    else
        log_info "Seed data disabled."
    fi

    log_info "Post-create tasks completed."
}

main "$@"
