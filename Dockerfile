FROM alpine:3.23 AS pecl

RUN <<CMD
    set -e
    apk add --no-cache php85-dev php85-pear php85-openssl musl-dev autoconf make gcc libuv-dev

    pecl85 channel-update pecl.php.net
    CFLAGS="-Wno-attributes" pecl85 install inotify
    CFLAGS="-Wno-attributes -Wno-format" pecl85 install uv-0.3.0
CMD

FROM alpine:3.23 AS base

COPY --from=pecl /usr/lib/php85/modules/inotify.so /usr/lib/php85/modules/inotify.so
COPY --from=pecl /usr/lib/php85/modules/uv.so /usr/lib/php85/modules/uv.so

RUN <<CMD
    set -e
    apk upgrade --no-cache --available
    apk add --no-cache php85 libuv php85-openssl php85-intl php85-fileinfo php85-ctype php85-mbstring php85-gmp

    ln -s /etc/php85 /etc/php
    ln -s /usr/bin/php85 /bin/php

    echo 'memory_limit=-1' > /etc/php/conf.d/00_main.ini
    echo 'extension=uv.so' > /etc/php/conf.d/00_uv.ini
    echo 'extension=inotify.so' > /etc/php/conf.d/00_inotify.ini
    echo 'fs.inotify.max_user_instances=8192' >> /etc/sysctl.conf
    echo 'fs.inotify.max_user_watches=524288' >> /etc/sysctl.conf
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
    php85-posix \
    php85-bcmath \
    php85-sodium \
    php85-dom \
    php85-iconv \
    php85-tokenizer

FROM tool AS build

COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock

RUN composer install --optimize-autoloader --prefer-dist --no-progress --no-dev --ansi

FROM base AS server

RUN <<CMD
  set -e
  echo 'opcache.enable_cli=on' >> /etc/php/conf.d/00_opcache.ini
  echo 'opcache.jit=tracing' >> /etc/php/conf.d/00_opcache.ini
CMD

COPY bin /app/bin
COPY migrates /app/migrates
COPY --from=build /app/vendor /app/vendor
COPY src /app/src

ENTRYPOINT ["php"]

CMD ["bin/start"]
