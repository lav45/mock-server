#!/usr/bin/env sh

set -e

# `docker rm -f` kills and removes in one synchronous call: after `docker kill` a `--rm` container
# is already being removed, so a separate `docker rm` fails and its name stays taken for a moment.
RUNNING_PROCESS=$(
  docker ps -a --filter name='(test_webhook_catcher|test_mock_server|test_runner)' --format "{{.Names}}"
)

for PROCESS in $RUNNING_PROCESS ; do
  docker rm -f "$PROCESS" > /dev/null
done

getIp() {
  docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$1"
}

# Container IPs are not reachable from the host under rootless Docker,
# so every HTTP call to the test containers runs inside a container.
waitHttp() {
  docker run --rm mock-server:tool sh -c '
    for ATTEMPT in $(seq 30); do
      wget -q -S -O /dev/null "$1" 2>&1 | grep -q "HTTP/" && exit 0
      sleep 1
    done
    exit 1
  ' sh "$1"
}

cleanup() {
  docker rm -f test_mock_server test_webhook_catcher > /dev/null 2>&1 || true
}
trap cleanup EXIT

docker run --rm -d \
  -e HTTP_PORT=80 \
  -e LOG_LEVEL="${LOG_LEVEL:-error}" \
  -e MAX_REQUESTS=0 \
  -e AUTO_CREATE_SESSIONS=0 \
  --name test_webhook_catcher \
  ghcr.io/tarampampam/webhook-tester:2 > /dev/null

WEBHOOK_CATCHER_URL=http://$(getIp "test_webhook_catcher")

if ! waitHttp "$WEBHOOK_CATCHER_URL/ready"; then
  echo "Webhook catcher did not become ready in time" >&2
  exit 1
fi

WEBHOOK_CATCHER_SESSION_ID=$(
  docker run --rm mock-server:tool sh -c '
    wget -q -O - --header "Content-Type: application/json" --post-data "{\"status_code\": 200}" "$1/api/session" | \
      php -r "echo json_decode(file_get_contents(\"php://stdin\"), true, 512, JSON_THROW_ON_ERROR)[\"uuid\"];"
  ' sh "$WEBHOOK_CATCHER_URL"
)

docker run --rm -d \
  -v "$(pwd)":/app:ro \
  -v "$(pwd)"/test/Functional/mocks:/app/mocks:ro \
  -e PORT=80 \
  -e LOG_LEVEL="${LOG_LEVEL:-error}" \
  -e MOCKS_PATH=/app/mocks \
  -e CONFIG_PATH=/app/test/Functional/config.yaml \
  -e DOMAIN=test.server.com \
  -e WEBHOOK_STORAGE_URL="$WEBHOOK_CATCHER_URL/$WEBHOOK_CATCHER_SESSION_ID" \
  --name test_mock_server \
  mock-server:server > /dev/null

docker logs -f test_mock_server &

MOCK_SERVER_URL=http://$(getIp "test_mock_server")
MOCK_SERVER_TLS_URL=https://$(getIp "test_mock_server")

if ! waitHttp "$MOCK_SERVER_URL"; then
  echo "Mock server did not become ready in time" >&2
  exit 1
fi

DOCKER_ARG='-i'
if [ -t 0 ] && [ -t 1 ]; then
  DOCKER_ARG='-it'
fi

# Rootless Docker maps container root to the host user; any other uid lands on a subuid.
USER_ARG="$(id -u):$(id -g)"
if docker info --format '{{.SecurityOptions}}' | grep -q rootless; then
  USER_ARG='0:0'
fi

EXIT_CODE=0
docker run --rm --init $DOCKER_ARG \
  -u "$USER_ARG" \
  -v "$(pwd)":/app:ro \
  -e MOCK_SERVER_URL="$MOCK_SERVER_URL" \
  -e MOCK_SERVER_TLS_URL="$MOCK_SERVER_TLS_URL" \
  -e WEBHOOK_CATCHER_URL="$WEBHOOK_CATCHER_URL" \
  -e WEBHOOK_CATCHER_SESSION_ID="$WEBHOOK_CATCHER_SESSION_ID" \
  --entrypoint composer \
  --name test_runner \
  mock-server:tool phpunit -- --do-not-record-test-run-history "${1:-test/Functional}" || EXIT_CODE=$?

exit $EXIT_CODE
