#!/usr/bin/env sh

set -e

RUNNING_PROCESS=$(
  docker ps --filter name='(test_mock_server|test_runner)' --format "{{.Names}}"
)

if [ "$RUNNING_PROCESS" ] ; then
  for PROCESS in $RUNNING_PROCESS ; do
    docker kill "$PROCESS" > /dev/null 2>&1
    docker rm "$PROCESS" > /dev/null 2>&1
  done
fi

docker run -d --rm \
  -v "$(pwd)":/app:ro \
  -v "$(pwd)"/test/benchmark:/app/mocks:ro \
  -e PORT=80 \
  --name test_mock_server \
  "${1:-mock-server:server}" > /dev/null

getIp() {
  docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$1"
}

DOCKER_ARG='-i'
if [ -z "$GITHUB_ACTIONS" ]; then
  DOCKER_ARG='-it'
fi

CORES=$(nproc)
MAX_FD=$(ulimit -n)
IP=$(getIp "test_mock_server")
URL="http://${IP}"

THREADS=$CORES
CONNECTIONS=$((CORES * 100))
DURATION="30s"

SAFE_FD_LIMIT=$((MAX_FD - 100))
if [ "$CONNECTIONS" -gt "$SAFE_FD_LIMIT" ]; then
  CONNECTIONS=$SAFE_FD_LIMIT
fi

if [ "$CONNECTIONS" -lt "$THREADS" ]; then
    CONNECTIONS=$THREADS
fi

while ! curl -s "$URL" > /dev/null; do sleep 1; done

docker run --rm --init $DOCKER_ARG --name test_runner mock-server:tool \
  hey -c "$CONNECTIONS" -z "$DURATION" "$URL"

docker stop test_mock_server > /dev/null