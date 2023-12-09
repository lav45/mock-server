FROM mock-server-base

RUN mkdir /app

COPY mock-server.php /app
COPY vendor /app/vendor
COPY src /app/src

WORKDIR /app

EXPOSE 8080
ENTRYPOINT ["php"]
CMD ["mock-server.php"]
