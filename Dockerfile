FROM alpine:3.17

RUN apk update --no-cache
RUN apk upgrade --no-cache --available

RUN apk add --no-cache  \
    php81 \
    php81-pcntl \
    php81-openssl \
    php81-intl \
    php81-fileinfo \
    php81-ctype \
    php81-curl \
    php81-dom \
    php81-iconv \
    php81-mbstring \
    php81-gmp \
    composer

RUN mkdir /app
WORKDIR /app

COPY composer.json /app
COPY composer.lock /app
RUN composer install --no-dev --optimize-autoloader --no-progress --prefer-dist --no-cache

EXPOSE 8080
ENTRYPOINT ["php"]
CMD ["mock-server.php"]

COPY mock-server.php /app
COPY src /app/src
