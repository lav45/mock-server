FROM alpine:3.22 AS pecl

RUN <<CMD
    set -e
    apk add --no-cache php84-dev php84-pear php84-openssl musl-dev autoconf make gcc libuv-dev

    pecl84 channel-update pecl.php.net
    pecl84 install inotify
    pecl84 install uv-0.3.0
CMD

FROM alpine:3.22 AS base

COPY --from=pecl /usr/lib/php84/modules/inotify.so /usr/lib/php84/modules/inotify.so
COPY --from=pecl /usr/lib/php84/modules/uv.so /usr/lib/php84/modules/uv.so

RUN <<CMD
    set -e
    apk upgrade --no-cache --available
    apk add --no-cache php84 php84-openssl php84-intl php84-fileinfo php84-ctype php84-dom php84-iconv php84-mbstring php84-tokenizer php84-gmp

    ln -s /etc/php84 /etc/php
    ln -s /usr/bin/php84 /bin/php

    echo 'memory_limit = -1' > /etc/php/conf.d/00_main.ini

    echo 'extension=inotify.so' > /etc/php/conf.d/00_inotify.ini
    echo 'fs.inotify.max_user_instances=8192' >> /etc/sysctl.conf
    echo 'fs.inotify.max_user_watches=524288' >> /etc/sysctl.conf

    apk add --no-cache libuv
    echo 'extension=uv.so' > /etc/php/conf.d/00_uv.ini
CMD

FROM base AS tool

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN <<CMD
    set -e
    apk add --no-cache php84-phar php84-curl php84-zip php84-zlib php84-xml php84-xmlwriter php84-pecl-pcov php84-posix

    echo 'zend.assertions=1' >> /etc/php/conf.d/00_main.ini
CMD

FROM tool AS build

WORKDIR /app

COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock

RUN composer install --optimize-autoloader --prefer-dist --no-progress --no-dev --ansi

FROM base AS server

RUN <<CMD
  set -e
  apk add --no-cache php84-opcache

  echo 'opcache.enable=on' >> /etc/php/conf.d/00_opcache.ini
  echo 'opcache.enable_cli=on' >> /etc/php/conf.d/00_opcache.ini
  echo 'opcache.jit_buffer_size=128M' >> /etc/php/conf.d/00_opcache.ini
  echo 'opcache.jit=tracing' >> /etc/php/conf.d/00_opcache.ini
CMD

COPY bin /app/bin
COPY migrates /app/migrates
COPY src /app/src
COPY --from=build /app/vendor /app/vendor

WORKDIR /app/bin

ARG DEBUG

ENTRYPOINT ["php", "-d", "zend.assertions=${DEBUG:-0}"]

CMD ["start"]
