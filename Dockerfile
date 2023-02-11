FROM php:8.1-cli-alpine

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN docker-php-ext-install pcntl
RUN wget https://getcomposer.org/installer -O - | php -- --install-dir=/usr/local/bin --filename=composer

ENV MOCKS_PATH=/app/mocks
ENV HOST=0.0.0.0
ENV PORT=8080

RUN mkdir /app
WORKDIR /app
COPY mock-server /app
COPY composer.json /app

RUN env COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-progress --prefer-dist
RUN rm -rf /var/cache/apk/* /tmp/* ~/.composer

COPY src /app/src

EXPOSE 8080
ENTRYPOINT ["/app/mock-server"]
