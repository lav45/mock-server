ARG REGISTRY
FROM ${REGISTRY}mock-server-base:latest

RUN apk add --no-cache \
    php84-phar \
    php84-curl \
    php84-zip \
    php84-xml \
    php84-xmlwriter \
    php84-xdebug \
    php84-posix

RUN wget https://getcomposer.org/installer -O - | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
ENTRYPOINT ["php", "-d", "opcache.enable_cli=off", "-d", "zend_extension=xdebug.so"]
CMD ["/app/bin/mock-server"]