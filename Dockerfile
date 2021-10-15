FROM composer AS composer

COPY composer.json /app
COPY composer.lock /app

RUN composer install        \
    --ignore-platform-reqs  \
    --no-ansi               \
    --no-interaction        \
    --no-scripts

FROM php:7.4-fpm-alpine

RUN docker-php-ext-install pdo pdo_mysql sockets

WORKDIR /app
COPY . .
COPY --from=composer /app/vendor /app/vendor
