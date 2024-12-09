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
RUN wget https://github.com/maglnet/ComposerRequireChecker/releases/latest/download/composer-require-checker.phar -O /usr/local/bin/composer-require-checker && chmod a+x /usr/local/bin/composer-require-checker
RUN wget https://github.com/composer-unused/composer-unused/releases/latest/download/composer-unused.phar -O /usr/local/bin/composer-unused && chmod a+x /usr/local/bin/composer-unused
RUN wget https://github.com/qossmic/deptrac/releases/download/2.0.2/deptrac.phar -O /usr/local/bin/deptrac && chmod a+x /usr/local/bin/deptrac
RUN wget https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/releases/download/v3.64.0/php-cs-fixer.phar -O /usr/local/bin/php-cs-fixer && chmod a+x /usr/local/bin/php-cs-fixer

WORKDIR /app
ENTRYPOINT ["php", "-d", "opcache.enable_cli=off", "-d", "zend_extension=xdebug.so"]
CMD ["mock-server.php"]