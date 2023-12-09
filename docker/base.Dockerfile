FROM alpine:3.19

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
    php83-gmp \
    php83-posix

RUN ln -s /usr/bin/php83 /usr/bin/php
RUN ln -s /etc/php83 /etc/php
