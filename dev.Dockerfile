FROM mock-server

RUN apk add --no-cache git php82-phar php82-zip
RUN wget https://getcomposer.org/installer -O - | php -- --with-openssl --install-dir=/usr/local/bin --filename=composer