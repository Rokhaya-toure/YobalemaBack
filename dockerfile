# =========================
# Stage 1: Builder
# =========================
FROM php:8.2-cli AS builder

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl pdo pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .
RUN php bin/console cache:clear --env=prod --no-debug


# =========================
# Stage 2: Runtime
# =========================
FROM php:8.2-apache

# 🔥 INSTALLER LE DRIVER POSTGRES ICI AUSSI
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Activer rewrite
RUN a2enmod rewrite

# DocumentRoot Symfony
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY --from=builder /app .

# Permissions Symfony
RUN chown -R www-data:www-data var

EXPOSE 80
CMD ["apache2-foreground"]