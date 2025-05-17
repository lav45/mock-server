ARG REGISTRY
FROM ${REGISTRY}mock-server-base:latest

RUN mkdir -p /app/mocks

COPY bin /app/bin
COPY vendor /app/vendor
COPY src /app/src
COPY migrates /app/migrates

ARG DEBUG
WORKDIR /app
ENTRYPOINT ["php", "-d", "zend.assertions=${DEBUG:-0}"]
CMD ["/app/bin/mock-server"]
