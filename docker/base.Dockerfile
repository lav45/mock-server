FROM alpine:3.20 AS build

RUN apk add --no-cache php83-pear php83-openssl php83-dev gcc musl-dev make
RUN pecl install inotify

FROM alpine:3.20

RUN apk upgrade --no-cache --available
RUN apk add --no-cache  \
    php83 \
    php83-pcntl \
    php83-openssl \
    php83-intl \
    php83-fileinfo \
    php83-ctype \
    php83-dom \
    php83-iconv \
    php83-mbstring \
    php83-tokenizer \
    php83-gmp \
    php83-opcache

RUN ln -s /etc/php83 /etc/php

RUN echo 'opcache.enable=on' >> /etc/php/conf.d/00_opcache.ini
RUN echo 'opcache.enable_cli=on' >> /etc/php/conf.d/00_opcache.ini
RUN echo 'opcache.jit_buffer_size=-1' >> /etc/php/conf.d/00_opcache.ini
RUN echo 'opcache.jit=1255' >> /etc/php/conf.d/00_opcache.ini

RUN sed -i 's|memory_limit = .*|memory_limit = -1|' /etc/php/php.ini

COPY --from=build /usr/lib/php83/modules/inotify.so /usr/lib/php83/modules
RUN echo 'extension=inotify.so' > /etc/php/conf.d/00_inotify.ini
RUN echo 'fs.inotify.max_user_instances=8192' >> /etc/sysctl.conf
RUN echo 'fs.inotify.max_user_watches=524288' >> /etc/sysctl.conf