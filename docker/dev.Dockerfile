FROM mock-server-base

RUN apk add --no-cache git php83-phar php83-zip php83-curl
RUN wget https://getcomposer.org/installer -O - | php -- --with-openssl --install-dir=/usr/local/bin --filename=composer