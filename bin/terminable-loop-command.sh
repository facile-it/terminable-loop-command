#!/bin/sh

# Adds environment variable to set initial values
# SECONDS_TO_WAIT (initial seconds to waits)
# SECONDS_TO_WAIT_MAX (max seconds to waits)

# SIGNALS that `$@` sends back to this script
# to tells it have to wait more or have stop waiting

if [ -z "$1" ]; then
    echo "Missing argument: no command arguments to launch";
    exit 1;
fi

if [ -z ${SECONDS_TO_WAIT+x} ]; then SECONDS_TO_WAIT=1; fi;
if [ -z ${SECONDS_TO_WAIT_MAX+x} ]; then SECONDS_TO_WAIT_MAX=64; fi;

echo "Starting command: $@";

_term() {
  kill -TERM $CHILD 2>/dev/null
  wait $CHILD
}

trap _term TERM

while true; do
    $@ &
    CHILD=$!
    wait $CHILD

    STATUS=$?

    # if $STATUS === 0 => execute immediately
    if [ $STATUS -eq 0 ]; then
      SECONDS=$SECONDS_TO_WAIT
      continue
    fi;

    # if $STATUS === 1 => keep waiting but not increments number of seconds
    if [ $STATUS -eq 1 ]; then
      sleep $SECONDS
      continue;
    fi;

    # if $STATUS === 2 => keep waiting and increments number of seconds (increments based of alghoritm)
    if [ $STATUS -eq 2 ]; then
      sleep $SECONDS

      if [ $SECONDS -lt $SECONDS_TO_WAIT_MAX ]; then
        $SECONDS=$(($SECONDS*2))
      fi;

      continue;
    fi;

    if [ $STATUS -ne 0 ]; then
        echo "Command exited with status code $STATUS, loop interrupted";
        exit $STATUS;
    fi
done
