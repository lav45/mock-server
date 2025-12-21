#!/usr/bin/env sh

getIp() {
  docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $1
}

WEBHOOK_CATCHER_PID=$(
  docker run --rm -d \
    -v $(pwd)/vendor:/app/vendor \
    -v $(pwd)/test/Functional/Server:/app/test/Functional/Server \
    -e PORT=80 \
    -e LOG_LEVEL=error \
    mock-server:server test/Functional/Server/start
)

WEBHOOK_CATCHER_URL=http://$(getIp ${WEBHOOK_CATCHER_PID})

MOCK_SERVER_PID=$(
  docker run --rm -d \
    -v $(pwd)/test/Functional/mocks:/app/mocks \
    -e PORT=80 \
    -e LOG_LEVEL=error \
    -e MOCKS_PATH=/app/mocks \
    -e DOMAIN=test.server.com \
    -e WEBHOOK_CATCHER_URL=${WEBHOOK_CATCHER_URL} \
    -e FILE_WATCH_TIMEOUT=0 \
    mock-server:server bin/start
)

MOCK_SERVER_URL=http://$(getIp ${MOCK_SERVER_PID})

docker run --rm -i \
  -u $(id -u):$(id -g) \
  -v $(pwd):/app \
  -e MOCK_SERVER_URL=${MOCK_SERVER_URL} \
  -e WEBHOOK_CATCHER_URL=${WEBHOOK_CATCHER_URL} \
  -e COMPOSER_HOME=/app/.cache/.composer \
  --entrypoint composer \
  mock-server:tool $1

docker stop ${WEBHOOK_CATCHER_PID}
docker stop ${MOCK_SERVER_PID}
