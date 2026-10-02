# Laravel 12 requires PHP ^8.2; 8.3 is mature and comfortably in range.
# Note: no mysql-client: Alpine's is MariaDB's and cannot reach MySQL 8 over
# TLS. The entrypoint waits via PDO instead, which is what Laravel uses.
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
      libzip-dev icu-dev oniguruma-dev freetype-dev libjpeg-turbo-dev libpng-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath mbstring gd zip intl opcache pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
