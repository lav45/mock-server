#!/usr/bin/env sh

set -e

RUNNING_PROCESS=$(
  docker ps --filter name='(test_webhook_catcher|test_mock_server|test_runner)' --format "{{.Names}}"
)

if [ "$RUNNING_PROCESS" ] ; then
  for PROCESS in $RUNNING_PROCESS ; do
    docker stop "$PROCESS" > /dev/null
    docker rm "$PROCESS" > /dev/null
  done
fi

getIp() {
  docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$1"
}

docker run -d \
  -v "$(pwd)"/vendor:/app/vendor \
  -v "$(pwd)"/test/Functional/Server:/app/test/Functional/Server \
  -e PORT=80 \
  -e LOG_LEVEL=error \
  --name test_webhook_catcher \
  --restart on-failure \
  mock-server:server test/Functional/Server/start > /dev/null

WEBHOOK_CATCHER_URL=http://$(getIp "test_webhook_catcher")

docker run -d \
  -v "$(pwd)"/vendor:/app/vendor \
  -v "$(pwd)"/test/Functional/mocks:/app/mocks \
  -e PORT=80 \
  -e LOG_LEVEL=error \
  -e MOCKS_PATH=/app/mocks \
  -e DOMAIN=test.server.com \
  -e WEBHOOK_CATCHER_URL="$WEBHOOK_CATCHER_URL" \
  -e FILE_WATCH_TIMEOUT=0 \
  --name test_mock_server \
  --restart on-failure \
  mock-server:server > /dev/null

MOCK_SERVER_URL=http://$(getIp "test_mock_server")

docker run --rm -i \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd)":/app \
  -e MOCK_SERVER_URL="$MOCK_SERVER_URL" \
  -e WEBHOOK_CATCHER_URL="$WEBHOOK_CATCHER_URL" \
  -e COMPOSER_HOME=/app/.cache/.composer \
  --entrypoint composer \
  --name test_runner \
  mock-server:tool "$1"

docker stop test_mock_server test_webhook_catcher > /dev/null
docker rm test_mock_server test_webhook_catcher > /dev/null
