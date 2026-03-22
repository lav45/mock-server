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

docker run -d \
  -v "$(pwd)":/app:ro \
  -e PORT=80 \
  -e LOG_LEVEL=error \
  --name test_webhook_catcher \
  mock-server:server test/Functional/Server/start > /dev/null

WEBHOOK_CATCHER_URL=http://$(getIp "test_webhook_catcher")

docker run -d \
  -v "$(pwd)":/app:ro \
  -v "$(pwd)"/test/Functional/mocks:/app/mocks:ro \
  -e PORT=80 \
  -e LOG_LEVEL=error \
  -e MOCKS_PATH=/app/mocks \
  -e DOMAIN=test.server.com \
  -e WEBHOOK_CATCHER_URL="$WEBHOOK_CATCHER_URL" \
  --name test_mock_server \
  mock-server:server > /dev/null

MOCK_SERVER_URL=http://$(getIp "test_mock_server")

while ! curl -s "$WEBHOOK_CATCHER_URL" > /dev/null; do sleep 1; done
while ! curl -s "$MOCK_SERVER_URL" > /dev/null; do sleep 1; done

DOCKER_ARG='-i'
if [ -z "$GITHUB_ACTIONS" ]; then
  DOCKER_ARG='-it'
fi

docker run --rm --init $DOCKER_ARG \
  -v "$(pwd)":/app:ro \
  -e MOCK_SERVER_URL="$MOCK_SERVER_URL" \
  -e WEBHOOK_CATCHER_URL="$WEBHOOK_CATCHER_URL" \
  --entrypoint composer \
  --name test_runner \
  mock-server:tool phpunit -- --do-not-cache-result test/Functional

docker stop test_mock_server test_webhook_catcher > /dev/null
docker rm test_mock_server test_webhook_catcher > /dev/null
