FROM composer:1.10 AS composer

COPY composer.json /app
COPY composer.lock /app

RUN composer install        \
    --ignore-platform-reqs  \
    --no-ansi               \
    --no-autoloader         \
    --no-interaction        \
    --no-scripts

COPY . /app/
RUN composer dump-autoload --optimize --classmap-authoritative

FROM php:7.4-fpm-alpine

RUN docker-php-ext-install pdo pdo_mysql sockets

WORKDIR /app
COPY . .
