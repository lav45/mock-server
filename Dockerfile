FROM alpine:3.18

RUN apk upgrade --no-cache --available
RUN apk add --no-cache  \
    php82 \
    php82-pcntl \
    php82-openssl \
    php82-intl \
    php82-fileinfo \
    php82-ctype \
    php82-curl \
    php82-dom \
    php82-iconv \
    php82-mbstring \
    php82-gmp \
    php82-posix

RUN ln -s /usr/bin/php82 /usr/bin/php
RUN ln -s /etc/php82 /etc/php

RUN mkdir /app
WORKDIR /app

COPY vendor /app/vendor
COPY mock-server.php /app
COPY src /app/src

EXPOSE 8080
ENTRYPOINT ["php"]
CMD ["mock-server.php"]
