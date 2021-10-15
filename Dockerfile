FROM php:7.4-fpm-alpine

RUN docker-php-ext-install pdo pdo_mysql sockets

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
