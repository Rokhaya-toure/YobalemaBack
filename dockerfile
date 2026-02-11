# =========================
# Stage 1: Builder
# =========================
# Stage 1: Builder
FROM php:8.2-cli AS builder

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl pdo pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier les fichiers composer
COPY composer.json composer.lock ./

# Installer les dépendances PHP (prod)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copier le code source
COPY . .

# Nettoyer et générer le cache prod
RUN php bin/console cache:clear --env=prod --no-debug


# =========================
# Stage 2: Runtime
# =========================
FROM php:8.2-apache

# Activer mod_rewrite
RUN a2enmod rewrite

# 👉 IMPORTANT : DocumentRoot Symfony = /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Copier l'app buildée
COPY --from=builder /app /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]