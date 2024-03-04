FROM mock-server-base

RUN apk add --no-cache \
    php83-phar \
    php83-curl \
    php83-zip \
    php83-xml \
    php83-xmlwriter \
    php83-tokenizer \
    php83-xdebug

RUN wget https://getcomposer.org/download/latest-stable/composer.phar -O /usr/local/bin/composer && chmod +x /usr/local/bin/composer
RUN wget https://github.com/maglnet/ComposerRequireChecker/releases/latest/download/composer-require-checker.phar -O /usr/local/bin/composer-require-checker && chmod +x /usr/local/bin/composer-require-checker
RUN wget https://github.com/composer-unused/composer-unused/releases/latest/download/composer-unused.phar -O /usr/local/bin/composer-unused && chmod +x /usr/local/bin/composer-unused

WORKDIR /app
ENTRYPOINT ["php"]
CMD ["mock-server.php"]