FROM alpine:3.23 AS pecl

RUN <<CMD
    set -eux
    apk add --no-cache php84-dev php84-pear php84-openssl openssl-dev musl-dev autoconf make gcc libevent-dev

    pecl84 channel-update pecl.php.net
    pecl84 install inotify
    pecl84 install event
CMD

FROM alpine:3.23 AS base

COPY --from=pecl \
    /usr/lib/php84/modules/inotify.so \
    /usr/lib/php84/modules/event.so \
    /usr/lib/php84/modules/

RUN <<CMD
    set -eux
    apk upgrade --no-cache --available
    apk add --no-cache libevent php84 php84-openssl php84-intl php84-fileinfo php84-ctype php84-mbstring php84-gmp php84-pcntl php84-sockets php84-posix

    ln -s /usr/bin/php84 /bin/php

    echo 'memory_limit=-1' > /etc/php84/conf.d/00_main.ini
    echo 'extension=event.so' > /etc/php84/conf.d/01_event.ini
    echo 'extension=inotify.so' > /etc/php84/conf.d/00_inotify.ini
    echo 'fs.inotify.max_user_instances=8192' >> /etc/sysctl.conf
    echo 'fs.inotify.max_user_watches=524288' >> /etc/sysctl.conf

    mkdir /app
    chown -R 82:82 /app
    echo 'www-data:x:82:82::/app:/bin/sh' >> /etc/passwd
CMD

WORKDIR /app

FROM base AS tool

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache \
    php84-phar \
    php84-curl \
    php84-zip \
    php84-zlib \
    php84-xml \
    php84-xmlwriter \
    php84-pecl-pcov \
    php84-bcmath \
    php84-sodium \
    php84-dom \
    php84-iconv \
    php84-tokenizer \
    hey

ENV COMPOSER_HOME=/app/.cache/.composer
ENV COMPOSER_PROCESS_TIMEOUT=600

USER www-data

FROM tool AS build

COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock

RUN composer install --optimize-autoloader --prefer-dist --no-progress --no-dev --ansi

FROM base AS base-server

RUN <<CMD
  set -eux
  apk add --no-cache php84-opcache
  echo 'opcache.enable=on' >> /etc/php84/conf.d/00_opcache.ini
  echo 'opcache.enable_cli=on' >> /etc/php84/conf.d/00_opcache.ini
  echo 'opcache.jit_buffer_size=128M' >> /etc/php84/conf.d/00_opcache.ini
  echo 'opcache.jit=tracing' >> /etc/php84/conf.d/00_opcache.ini
CMD

USER www-data

ENV LOG_LEVEL=info

CMD [ "sh", "-c", "vendor/bin/cluster --pid-file /tmp/cluster.pid --log ${LOG_LEVEL} bin/start" ]

FROM base-server AS server

COPY bin /app/bin
COPY src /app/src
COPY --from=build --chown=root:root /app/vendor /app/vendor
