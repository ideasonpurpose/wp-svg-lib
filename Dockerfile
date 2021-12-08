# https://hub.docker.com/_/php
# https://github.com/docker-library/docs/blob/master/php/README.md#supported-tags-and-respective-dockerfile-links
# FROM php:7.4-cli
FROM php:8.0.13-cli
RUN pecl install xdebug-3.1.1 \
    && docker-php-ext-enable xdebug \
    && echo 'xdebug.mode=coverage' > /usr/local/etc/php/conf.d/xdebug.ini
