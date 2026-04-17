#!/usr/bin/env sh

set -e

RUNNING_PROCESS=$(
  docker ps --filter name='(test_webhook_catcher|test_mock_server|test_runner)' --format "{{.Names}}"
)

if [ "$RUNNING_PROCESS" ] ; then
  for PROCESS in $RUNNING_PROCESS ; do
    docker kill "$PROCESS" > /dev/null 2>&1
    docker rm "$PROCESS" > /dev/null 2>&1
  done
fi

getIp() {
  docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$1"
}

docker run --rm -d \
  -e HTTP_PORT=80 \
  -e LOG_LEVEL=error \
  -e MAX_REQUESTS=0 \
  -e AUTO_CREATE_SESSIONS=0 \
  --name test_webhook_catcher \
  ghcr.io/tarampampam/webhook-tester:2 > /dev/null

WEBHOOK_CATCHER_URL=http://$(getIp "test_webhook_catcher")

while ! curl -s "$WEBHOOK_CATCHER_URL/ready" > /dev/null; do sleep 1; done

WEBHOOK_CATCHER_SESSION_ID=$(
  curl -s -H "Content-Type: application/json" -X POST -d '{"status_code": 200}' "$WEBHOOK_CATCHER_URL/api/session" | \
    docker run --rm -i mock-server:tool php -r "echo json_decode(file_get_contents('php://stdin'), true)['uuid'];"
)

docker run --rm -d \
  -v "$(pwd)":/app:ro \
  -v "$(pwd)"/test/Functional/mocks:/app/mocks:ro \
  -e PORT=80 \
  -e LOG_LEVEL=error \
  -e MOCKS_PATH=/app/mocks \
  -e DOMAIN=test.server.com \
  -e WEBHOOK_STORAGE_URL="$WEBHOOK_CATCHER_URL/$WEBHOOK_CATCHER_SESSION_ID" \
  --name test_mock_server \
  mock-server:server > /dev/null

docker logs -f test_mock_server &

MOCK_SERVER_URL=http://$(getIp "test_mock_server")

while ! curl -s "$MOCK_SERVER_URL" > /dev/null; do sleep 1; done

DOCKER_ARG='-i'
if [ -z "$GITHUB_ACTIONS" ]; then
  DOCKER_ARG='-it'
fi

docker run --rm --init $DOCKER_ARG \
  -v "$(pwd)":/app:ro \
  -e MOCK_SERVER_URL="$MOCK_SERVER_URL" \
  -e WEBHOOK_CATCHER_URL="$WEBHOOK_CATCHER_URL" \
  -e WEBHOOK_CATCHER_SESSION_ID="$WEBHOOK_CATCHER_SESSION_ID" \
  --entrypoint composer \
  --name test_runner \
  mock-server:tool phpunit -- --do-not-cache-result "${1:-test/Functional}" || true

docker stop test_mock_server > /dev/null
docker stop test_webhook_catcher > /dev/null
