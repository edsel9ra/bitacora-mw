#!/bin/sh
set -u

interval="${BITACORA_MAINTENANCE_INTERVAL_SECONDS:-3600}"
batch="${BITACORA_MAINTENANCE_BATCH_SIZE:-500}"
failures=0

case "$interval" in ''|*[!0-9]*|0) echo 'BITACORA_MAINTENANCE_INTERVAL_SECONDS debe ser un entero positivo.' >&2; exit 1;; esac
case "$batch" in ''|*[!0-9]*|0) echo 'BITACORA_MAINTENANCE_BATCH_SIZE debe ser un entero positivo.' >&2; exit 1;; esac

while true; do
    started="$(date +%s)"
    if php database/cleanup_bitacora_pdfs.php "$batch" && php database/cleanup_bitacora_drafts.php "$batch"; then
        finished="$(date +%s)"
        printf '%s\n' "$finished" > /tmp/maintenance-last-success
        printf '{"component":"maintenance","status":"ok","timestamp":%s,"duration_seconds":%s}\n' "$finished" "$((finished - started))"
        failures=0
    else
        finished="$(date +%s)"
        failures="$((failures + 1))"
        printf '{"component":"maintenance","status":"error","timestamp":%s,"consecutive_failures":%s}\n' "$finished" "$failures" >&2
        if [ "$failures" -ge 3 ]; then
            exit 1
        fi
    fi
    sleep "$interval"
done
