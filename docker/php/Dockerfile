FROM php:8.5-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev libonig-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring bcmath intl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php/entrypoint.sh /usr/local/bin/itam-entrypoint
RUN chmod +x /usr/local/bin/itam-entrypoint
EXPOSE 8000
ENTRYPOINT ["itam-entrypoint"]
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
