FROM mock-server-base

RUN apk add --no-cache \
    git \
    php83-phar \
    php83-curl \
    php83-zip \
    php83-xml \
    php83-xmlwriter \
    php83-tokenizer

RUN wget https://getcomposer.org/installer -O - | php -- --with-openssl --install-dir=/usr/local/bin --filename=composer