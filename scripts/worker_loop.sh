#!/bin/sh
set -u

interval="${BITACORA_MAIL_WORKER_INTERVAL_SECONDS:-15}"
failures=0

case "$interval" in ''|*[!0-9]*|0) echo 'BITACORA_MAIL_WORKER_INTERVAL_SECONDS debe ser un entero positivo.' >&2; exit 1;; esac

while true; do
    started="$(date +%s)"
    if php scripts/process_bitacora_email_queue.php; then
        finished="$(date +%s)"
        printf '%s\n' "$finished" > /tmp/worker-last-success
        printf '{"component":"mail-worker","status":"ok","timestamp":%s,"duration_seconds":%s}\n' "$finished" "$((finished - started))"
        failures=0
    else
        finished="$(date +%s)"
        failures="$((failures + 1))"
        printf '{"component":"mail-worker","status":"error","timestamp":%s,"consecutive_failures":%s}\n' "$finished" "$failures" >&2
        if [ "$failures" -ge 3 ]; then
            exit 1
        fi
    fi
    sleep "$interval"
done
