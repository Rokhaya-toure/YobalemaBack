# Stage 1: Builder (Composer dependencies)
FROM php:8.2-cli AS builder

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier les fichiers composer
COPY composer.json composer.lock ./

# Installer les dépendances PHP (production)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copier le reste du code source Symfony
COPY . .

# Préparer Symfony en production
RUN php bin/console cache:clear --env=prod --no-debug

# Stage 2: Runtime
FROM php:8.2-apache

COPY --from=builder /app /var/www/html
RUN a2enmod rewrite
EXPOSE 80
CMD ["apache2-foreground"]