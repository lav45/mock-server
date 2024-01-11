FROM mock-server-base

RUN apk add --no-cache \
    git \
    php83-phar \
    php83-curl \
    php83-zip \
    php83-xml \
    php83-xmlwriter \
    php83-tokenizer \
    php83-xdebug

RUN sed -i 's|;zend_extension=xdebug.so|zend_extension=xdebug.so|' /etc/php83/conf.d/50_xdebug.ini
RUN sed -i 's|;xdebug.mode=off|xdebug.mode=coverage|' /etc/php83/conf.d/50_xdebug.ini

RUN wget https://getcomposer.org/installer -O - | php -- --with-openssl --install-dir=/usr/local/bin --filename=composer