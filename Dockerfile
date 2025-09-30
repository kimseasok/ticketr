# syntax=docker/dockerfile:1

FROM composer:2 AS composer
WORKDIR /var/www/html
# Install intl extension for Composer stage so dependency resolution succeeds
RUN apk add --no-cache icu-data-full icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev \
    && docker-php-ext-install intl \
    && apk del .build-deps
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-progress --prefer-dist --no-interaction
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-progress --prefer-dist --no-interaction

FROM node:20 AS node
WORKDIR /var/www/html
COPY package*.json ./
RUN npm install --no-progress
COPY --from=composer /var/www/html /var/www/html
RUN npm run build

FROM php:8.3-fpm AS app
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        zip \
        unzip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libicu-dev \
        libzip-dev \
        supervisor \
    && docker-php-ext-install pdo_mysql bcmath pcntl intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=composer /var/www/html /var/www/html
COPY --from=node /var/www/html/public/build /var/www/html/public/build

COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
