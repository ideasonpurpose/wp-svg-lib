FROM php:7.4-cli
RUN pecl install xdebug-2.9.8 \
    && docker-php-ext-enable xdebug
