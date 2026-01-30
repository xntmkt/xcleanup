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

DEMO_TMP="/tmp/xcleanup_demo"
DEMO_LOG="/var/log/xcleanup_demo"
DEMO_DOWNLOADS="/home/vscode/Downloads/cleanupd-demo"

log() {
    echo "[seed-data] $*"
}

log "Creating sample data directories."
mkdir -p "$DEMO_TMP" "$DEMO_DOWNLOADS"
sudo mkdir -p "$DEMO_LOG"
sudo chown -R vscode:vscode "$DEMO_LOG"

log "Creating sample files with realistic ages."
mkdir -p "$DEMO_TMP/old-dir" "$DEMO_TMP/empty-dir-old" "$DEMO_TMP/nested/empty"

printf '%s\n' "cleanup demo" > "$DEMO_TMP/old-dir/old-data.txt"
printf '%s\n' "cleanup demo" > "$DEMO_TMP/new-data.txt"
printf '%s\n' "cleanup demo" > "$DEMO_LOG/app.log"
printf '%s\n' "cleanup demo" > "$DEMO_DOWNLOADS/report.txt"

if command -v fallocate >/dev/null 2>&1; then
    fallocate -l 8M "$DEMO_TMP/old-large.bin"
else
    dd if=/dev/zero of="$DEMO_TMP/old-large.bin" bs=1M count=8 status=none
fi

touch -d "4 days ago" "$DEMO_TMP/old-large.bin"

touch -d "3 days ago" "$DEMO_TMP/old-dir/old-data.txt"
touch -d "2 days ago" "$DEMO_LOG/app.log"
touch -d "2 days ago" "$DEMO_DOWNLOADS/report.txt"

touch "$DEMO_TMP/new-data.txt"

log "Sample data seeded."