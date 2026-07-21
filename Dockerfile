FROM alpine:3.24 AS pecl

RUN <<CMD
    set -eux
    apk add --no-cache php85-dev php85-pear php85-openssl musl-dev autoconf make gcc libuv-dev

    pecl85 channel-update pecl.php.net
    CFLAGS="-Wno-attributes -Wno-format" pecl85 install uv-0.3.0
CMD

FROM alpine:3.24 AS base

COPY --from=pecl /usr/lib/php85/modules/uv.so /usr/lib/php85/modules/

RUN <<CMD
    set -eux
    apk upgrade --no-cache --available
    apk add --no-cache libuv watchexec php85 php85-openssl php85-intl php85-fileinfo php85-ctype php85-mbstring php85-gmp php85-pcntl php85-sockets php85-posix

    ln -s /usr/bin/php85 /bin/php

    echo 'memory_limit=-1' > /etc/php85/conf.d/00_main.ini
    echo 'extension=uv.so' > /etc/php85/conf.d/00_uv.ini

    mkdir /app
    chown -R 82:82 /app
    echo 'www-data:x:82:82::/app:/bin/sh' >> /etc/passwd
CMD

WORKDIR /app

FROM base AS tool

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache \
    php85-phar \
    php85-curl \
    php85-zip \
    php85-zlib \
    php85-xml \
    php85-xmlwriter \
    php85-pecl-pcov \
    php85-bcmath \
    php85-sodium \
    php85-dom \
    php85-iconv \
    php85-tokenizer \
    hey

ENV COMPOSER_HOME=/app/.cache/.composer
ENV COMPOSER_PROCESS_TIMEOUT=600

USER www-data

FROM tool AS build

COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock
COPY migrates /app/migrates

RUN composer install --prefer-dist --no-progress --no-dev --ansi

FROM base AS base-server

RUN <<CMD
  set -eux
  echo 'opcache.enable_cli=on' >> /etc/php85/conf.d/00_opcache.ini
  echo 'opcache.jit=tracing' >> /etc/php85/conf.d/00_opcache.ini
CMD

USER www-data

ENV LOG_LEVEL=info

CMD [ "sh", "-c", "vendor/bin/cluster --pid-file /tmp/cluster.pid --log ${LOG_LEVEL} bin/start" ]

FROM base-server AS server

COPY bin /app/bin
COPY etc /app/etc
COPY src /app/src
COPY schema /app/schema
COPY Extension /app/Extension
COPY migrates /app/migrates
COPY --from=build --chown=root:root /app/vendor /app/vendor
