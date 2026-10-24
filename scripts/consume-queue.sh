
#!/usr/bin/env bash

set -u

cd "$(dirname "$0")/.." || exit 1

SLEEP_SECONDS=2
EMPTY_GRACE_SECONDS=10

echo "Starting OpenPulse queue worker."
echo "Sleep between jobs: ${SLEEP_SECONDS}s"
echo "Empty queue grace period: ${EMPTY_GRACE_SECONDS}s"
echo "Press Ctrl+C to stop."

empty_since=""

while true; do
    # Process one job. The application reports when no job is available.
    output=$(php bin/openpulse queue:work 2>&1)
    status=$?

    printf '%s\n' "$output"

    if [ "$status" -ne 0 ]; then
        echo "Worker exited with status ${status}. Stopping."
        exit "$status"
    fi

    if printf '%s\n' "$output" | grep -q "No available jobs in queue"; then
        if [ -z "$empty_since" ]; then
            empty_since=$(date +%s)
        fi

        now=$(date +%s)
        elapsed=$((now - empty_since))

        if [ "$elapsed" -ge "$EMPTY_GRACE_SECONDS" ]; then
            echo "Queue remained empty for ${EMPTY_GRACE_SECONDS}s. Stopping."
            exit 0
        fi

        sleep "$SLEEP_SECONDS"
        continue
    fi

    # A job was processed; reset the empty-queue grace period.
    empty_since=""
    sleep "$SLEEP_SECONDS"
done