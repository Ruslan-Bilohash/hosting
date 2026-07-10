# BILOHASH Hosting CMS — demo / self-host container (PHP 8.2 + Apache)
FROM php:8.2-apache-bookworm

RUN a2enmod rewrite headers \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p data public_html data/logs \
    && chown -R www-data:www-data data public_html \
    && chmod 750 data

EXPOSE 80