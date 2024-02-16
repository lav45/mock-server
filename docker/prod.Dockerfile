ARG REGISTRY
FROM ${REGISTRY}mock-server-base:latest

RUN mkdir /app

COPY mock-server.php /app
COPY vendor /app/vendor
COPY src /app/src

WORKDIR /app
ENTRYPOINT ["php"]
CMD ["mock-server.php"]
