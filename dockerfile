# Stage 1: Builder (Composer dependencies)
FROM php:8.2-cli AS builder

# Installer les dépendances système pour Symfony + Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier les fichiers composer pour profiter du cache Docker
COPY composer.json composer.lock ./

# Installer les dépendances PHP (sans exécuter les scripts auto-scripts)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copier le reste du code source Symfony
COPY . .

# Exécuter les scripts auto-scripts (cache:clear, etc)
RUN composer run-script auto-scripts --no-dev

# ------------------------------
# Stage 2: Runtime
FROM php:8.2-apache

# Copier le build depuis le builder
COPY --from=builder /app /var/www/html

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Exposer le port 80
EXPOSE 80

# Point d’entrée Apache
CMD ["apache2-foreground"]