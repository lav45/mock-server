FROM alpine:3.22 AS build

RUN apk add --no-cache php84-pear php84-openssl php84-dev gcc musl-dev make autoconf
RUN pecl84 channel-update pecl.php.net && pecl84 install inotify

FROM alpine:3.22

RUN apk upgrade --no-cache --available
RUN apk add --no-cache  \
    php84 \
    php84-pcntl \
    php84-openssl \
    php84-intl \
    php84-fileinfo \
    php84-ctype \
    php84-dom \
    php84-iconv \
    php84-mbstring \
    php84-tokenizer \
    php84-gmp \
    php84-opcache

RUN ln -s /usr/bin/php84 /bin/php

RUN echo 'opcache.enable=on' >> /etc/php84/conf.d/00_opcache.ini
RUN echo 'opcache.enable_cli=off' >> /etc/php84/conf.d/00_opcache.ini
RUN echo 'opcache.jit_buffer_size=64M' >> /etc/php84/conf.d/00_opcache.ini
RUN echo 'opcache.jit=tracing' >> /etc/php84/conf.d/00_opcache.ini

RUN sed -i 's|memory_limit = .*|memory_limit = -1|' /etc/php84/php.ini

COPY --from=build /usr/lib/php84/modules/inotify.so /usr/lib/php84/modules
RUN echo 'extension=inotify.so' > /etc/php84/conf.d/10_inotify.ini
RUN echo 'fs.inotify.max_user_instances=8192' >> /etc/sysctl.conf
RUN echo 'fs.inotify.max_user_watches=524288' >> /etc/sysctl.conf