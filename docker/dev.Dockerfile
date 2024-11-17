FROM mock-server-base:latest

RUN apk add --no-cache \
    php83-phar \
    php83-curl \
    php83-zip \
    php83-xml \
    php83-xmlwriter \
    php83-tokenizer \
    php83-xdebug \
    php83-posix

RUN wget https://getcomposer.org/installer -O - | php -- --install-dir=/usr/local/bin --filename=composer
RUN wget https://github.com/maglnet/ComposerRequireChecker/releases/latest/download/composer-require-checker.phar -O /usr/local/bin/composer-require-checker && chmod a+x /usr/local/bin/composer-require-checker
RUN wget https://github.com/composer-unused/composer-unused/releases/latest/download/composer-unused.phar -O /usr/local/bin/composer-unused && chmod a+x /usr/local/bin/composer-unused
RUN wget https://github.com/qossmic/deptrac/releases/download/2.0.2/deptrac.phar -O /usr/local/bin/deptrac && chmod a+x /usr/local/bin/deptrac
RUN wget https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/releases/download/v3.64.0/php-cs-fixer.phar -O /usr/local/bin/php-cs-fixer && chmod a+x /usr/local/bin/php-cs-fixer

WORKDIR /app
ENTRYPOINT ["php"]
CMD ["mock-server.php"]