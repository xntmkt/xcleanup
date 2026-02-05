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

log_info() {
    printf '[INFO] %s - %s\n' "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" "$1"
}

require_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        printf '[ERROR] %s - Missing required command: %s\n' "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" "$1" >&2
        exit 1
    fi
}

main() {
    log_info "Starting post-start tasks."
    require_command "php"

    log_info "PHP version: $(php -v | head -n 1)"
    log_info "Project root: ${PROJECT_ROOT}"

    log_info "Post-start tasks completed."
}

main "$@"
