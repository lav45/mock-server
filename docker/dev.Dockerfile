FROM mock-server-base:latest

RUN apk add --no-cache \
    php83-phar \
    php83-curl \
    php83-zip \
    php83-xml \
    php83-xmlwriter \
    php83-tokenizer \
    php83-xdebug

ADD https://api.github.com/repos/composer/composer/commits?per_page=1 latest-composer
RUN wget https://getcomposer.org/download/latest-stable/composer.phar -O /usr/local/bin/composer && chmod a+x /usr/local/bin/composer

ADD https://api.github.com/repos/maglnet/ComposerRequireChecker/commits?per_page=1 latest-composer-require-checker
RUN wget https://github.com/maglnet/ComposerRequireChecker/releases/latest/download/composer-require-checker.phar -O /usr/local/bin/composer-require-checker && chmod a+x /usr/local/bin/composer-require-checker

ADD https://api.github.com/repos/composer-unused/composer-unused/commits?per_page=1 latest-composer-unused
RUN wget https://github.com/composer-unused/composer-unused/releases/latest/download/composer-unused.phar -O /usr/local/bin/composer-unused && chmod a+x /usr/local/bin/composer-unused

ADD https://api.github.com/repos/qossmic/deptrac/commits?per_page=1 latest-deptrac
RUN wget https://github.com/qossmic/deptrac/releases/download/2.0.0/deptrac.phar -O /usr/local/bin/deptrac && chmod a+x /usr/local/bin/deptrac

ADD https://api.github.com/repos/PHP-CS-Fixer/PHP-CS-Fixer/commits?per_page=1 latest-php-cs-fixer
RUN wget https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/releases/download/v3.59.1/php-cs-fixer.phar -O /usr/local/bin/php-cs-fixer && chmod a+x /usr/local/bin/php-cs-fixer

WORKDIR /app
ENTRYPOINT ["php"]
CMD ["mock-server.php"]