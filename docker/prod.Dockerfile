ARG REGISTRY
FROM ${REGISTRY}mock-server-base:latest

RUN mkdir /app

COPY mock-server.php /app
COPY vendor /app/vendor
COPY src /app/src

ARG DEBUG
WORKDIR /app
ENTRYPOINT ["php", "-d", "zend.assertions=${DEBUG:-0}"]
CMD ["mock-server.php"]
