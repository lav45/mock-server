ARG REGISTRY
FROM ${REGISTRY}mock-server-base:latest

RUN apk add --no-cache \
    php84-phar \
    php84-curl \
    php84-zip \
    php84-xml \
    php84-xmlwriter \
    php84-pecl-pcov \
    php84-posix

RUN wget https://getcomposer.org/installer -O - | php -d opcache.enable_cli=off -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

ENTRYPOINT ["php", "-d", "opcache.enable_cli=off"]

CMD ["/app/bin/mock-server"]