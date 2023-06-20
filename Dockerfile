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
    php82-gmp

RUN ln -s /usr/bin/php82 /usr/bin/php
RUN ln -s /etc/php82 /etc/php

RUN mkdir /app
WORKDIR /app

# composer
RUN apk add --no-cache git php82-phar php82-zip
RUN wget https://getcomposer.org/installer -O - | php -- --with-openssl --install-dir=/usr/local/bin --filename=composer

COPY composer.json /app
COPY composer.lock /app
RUN composer install --no-dev --optimize-autoloader --no-progress --prefer-dist --no-cache

EXPOSE 8080
ENTRYPOINT ["php"]
CMD ["mock-server.php"]

COPY mock-server.php /app
COPY src /app/src
