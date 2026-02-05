#!/usr/bin/env bash
# Copyright (c) 2026 xNetVN Inc.
# Website: https://xnetvn.com/
# License: See LICENSE file in repository root
# Contact: license@xnetvn.net
# Script Name: seed-data.sh
# Description: Create sample directories and files to simulate WordPress/Laravel cache/tmp data
# Usage: seed-data.sh [--dir <path>] [--total <num>] [--dry-run] [--yes] [--force]

set -euo pipefail
IFS=$'\n\t'

# Default configuration
TARGET_DIR="/home/vscode/web/demo.example.local/public_html"
TOTAL=${TOTAL:-10000}    # approximate total number of items (directories + files)
DRY_RUN=false
ASSUME_YES=false
FORCE=false
LOG_TAG="seed-data"

usage() {
    cat <<EOF
Usage: $0 [--dir <path>] [--total <num>] [--dry-run] [--yes] [--force]

Options:
  --dir     Target base directory (absolute path). Default: ${TARGET_DIR}
  --total   Approximate total number of items to create (default: ${TOTAL}).
  --dry-run Show actions only, do not modify filesystem
  --yes     Non-interactive: proceed without prompt
  --force   Overwrite existing demo tree if present (careful)
  -h|--help Show this help
EOF
}

# Parse args
while [[ ${#} -gt 0 ]]; do
    case "$1" in
        --dir)
            TARGET_DIR="$2"; shift 2;;
        --total)
            TOTAL="$2"; shift 2;;
        --dry-run)
            DRY_RUN=true; shift;;
        --yes)
            ASSUME_YES=true; shift;;
        --force)
            FORCE=true; shift;;
        -h|--help)
            usage; exit 0;;
        *)
            echo "Unknown argument: $1" >&2; usage; exit 1;;
    esac
done

# Safety checks
if [[ "${TARGET_DIR}" != /* ]]; then
    echo "ERROR: TARGET_DIR must be an absolute path." >&2; exit 2
fi

if [[ -e "${TARGET_DIR}" && ! -d "${TARGET_DIR}" ]]; then
    echo "ERROR: ${TARGET_DIR} exists and is not a directory." >&2; exit 3
fi

if [[ -d "${TARGET_DIR}" && ${FORCE} != true && ${ASSUME_YES} != true ]]; then
    if [[ -t 0 ]]; then
        read -r -p "Target directory ${TARGET_DIR} already exists. Continue and add data inside it? [y/N]: " answer
        case "$answer" in
            [yY][eE][sS]|[yY]) ;;
            *) echo "Aborted by user."; exit 4;;
        esac
    else
        echo "Target directory ${TARGET_DIR} already exists. Rerun with --yes to proceed non-interactively or --force to ignore." >&2
        exit 5
    fi
fi

# Dry-run echo helper
run() {
    if [[ "$DRY_RUN" == true ]]; then
        echo "DRY-RUN: $*"
    else
        # shellcheck disable=SC2086
        "$@"
    fi
}

log() {
    local msg="$*"
    echo "[${LOG_TAG}] ${msg}"
    # also send to syslog for auditing (won't fail script if logger missing)
    if command -v logger >/dev/null 2>&1; then
        logger -t "${LOG_TAG}" "${msg}"
    fi
}

main() {
    log "Starting seed-data (TARGET=${TARGET_DIR}, TOTAL=${TOTAL}, DRY_RUN=${DRY_RUN})"

    # Prepare base directories typical for WP+Laravel
    base_dirs=(
        "${TARGET_DIR}/wp-content/uploads"
        "${TARGET_DIR}/wp-content/cache"
        "${TARGET_DIR}/wp-content/plugins/wp-rocket/cache"
        "${TARGET_DIR}/wp-content/plugins/w3-total-cache/cache"
        "${TARGET_DIR}/wp-content/plugins/wp-super-cache/cache"
        "${TARGET_DIR}/wp-content/plugins/wp-fastest-cache/cache"
        "${TARGET_DIR}/wp-content/plugins/litespeed-cache/cache"
        "${TARGET_DIR}/wp-content/plugins/object-cache"
        "${TARGET_DIR}/storage/framework/cache/data"
        "${TARGET_DIR}/storage/framework/views"
        "${TARGET_DIR}/storage/logs"
        "${TARGET_DIR}/bootstrap/cache"
        "${TARGET_DIR}/cache"
        "${TARGET_DIR}/tmp"
        "${TARGET_DIR}/public"
        "${TARGET_DIR}/vendor"
    )

    for d in "${base_dirs[@]}"; do
        log "Creating base dir: ${d}"
        run mkdir -p "${d}"
    done

    # Compute how many directories and files to create
    dir_count=$((TOTAL / 2))
    file_count=$((TOTAL - dir_count))

    log "Will create approximately ${dir_count} directories and ${file_count} files in various cache/tmp locations."

    # Create directories under tmp and cache
    for i in $(seq 1 "$dir_count"); do
        # distribute across various parent dirs to mimic real structure
        case $((i % 6)) in
            0) parent="${TARGET_DIR}/wp-content/cache/plugin_cache";;
            1) parent="${TARGET_DIR}/storage/framework/cache/data";;
            2) parent="${TARGET_DIR}/wp-content/uploads";;
            3) parent="${TARGET_DIR}/tmp";;
            4) parent="${TARGET_DIR}/bootstrap/cache";;
            *) parent="${TARGET_DIR}/public/tmp";;
        esac
        dirpath="${parent}/dir_$(printf '%05d' "$i")"
        run mkdir -p "${dirpath}"
        # create some placeholder files inside to simulate per-cache items
        if [[ $((i % 3)) -eq 0 ]]; then
            run truncate -s 1K "${dirpath}/cache_$(printf '%05d' "$i").cache"
        fi
        if (( i % 1000 == 0 )); then
            log "Created ${i} directories so far..."
        fi
    done

    # Create files scattered across different cache locations
    for i in $(seq 1 "$file_count"); do
        case $((i % 7)) in
            0) fparent="${TARGET_DIR}/wp-content/cache/plugin_cache"; ext=".cache";;
            1) fparent="${TARGET_DIR}/storage/framework/views"; ext=".view";;
            2) fparent="${TARGET_DIR}/wp-content/uploads"; ext=".jpg";;
            3) fparent="${TARGET_DIR}/tmp"; ext=".tmp";;
            4) fparent="${TARGET_DIR}/storage/logs"; ext=".log";;
            5) fparent="${TARGET_DIR}/bootstrap/cache"; ext=".php";;
            *) fparent="${TARGET_DIR}/public"; ext=".html";;
        esac
        run mkdir -p "${fparent}"
        filename="item_$(printf '%05d' "$i")${ext}"
        filepath="${fparent}/${filename}"
        run truncate -s 2K "${filepath}"
        # occasionally add a tiny PHP/HTML payload to simulate files
        if [[ "${ext}" == ".php" || "${ext}" == ".html" ]]; then
            if [[ "$DRY_RUN" == true ]]; then
                echo "DRY-RUN: echo '<!-- sample ${filename} -->' > ${filepath}"
            else
                printf "<!-- sample %s -->\n" "$filename" >> "${filepath}" || true
            fi
        fi
        # set random modification time within last 30 days
        if [[ "$DRY_RUN" != true ]]; then
            # pick random days ago
            days=$((RANDOM % 30))
            touch -d "${days} days ago" "${filepath}" || true
        fi
        if (( i % 1000 == 0 )); then
            log "Created ${i} files so far..."
        fi
    done

    # Create some plugin-specific cache files and directories
    plugin_dirs=(
        "${TARGET_DIR}/wp-content/plugins/wp-rocket/cache"
        "${TARGET_DIR}/wp-content/plugins/w3-total-cache/cache"
        "${TARGET_DIR}/wp-content/plugins/wp-super-cache/cache"
        "${TARGET_DIR}/wp-content/plugins/wp-fastest-cache/cache"
        "${TARGET_DIR}/wp-content/plugins/litespeed-cache/cache"
    )

    for pd in "${plugin_dirs[@]}"; do
        run mkdir -p "${pd}"
        for j in $(seq 1 50); do
            run truncate -s 1K "${pd}/plugin_cache_${j}.cache"
        done
    done

    # create some Laravel-style storage files
    for j in $(seq 1 200); do
        run truncate -s 1K "${TARGET_DIR}/storage/framework/cache/data/cache_${j}.bin"
    done

    log "Seed data creation complete. Running summary..."
    if [[ "$DRY_RUN" == false ]]; then
        du -sh "${TARGET_DIR}" || true
    fi

    log "Done. If you used --dry-run, rerun without it to actually create files."
}

# Run main
main
