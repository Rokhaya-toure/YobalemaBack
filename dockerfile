# =========================
# Stage 1: Builder
# =========================
FROM php:8.2-cli AS builder

# Installer les dépendances système et extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev libpq-dev \
    && docker-php-ext-install intl pdo pdo_pgsql zip

# Installer composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier les fichiers composer et installer les dépendances
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copier tout le code source
COPY . .

# Clear cache prod
RUN php bin/console cache:clear --env=prod --no-debug

# Installer les assets publics (Swagger UI, JS/CSS)
RUN php bin/console assets:install --symlink --relative public

# Exporter Swagger JSON pour prod
RUN php bin/console api:swagger:export public/swagger.json || true

# =========================
# Stage 2: Runtime
# =========================
FROM php:8.2-apache

# Installer extensions PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# DocumentRoot Symfony = /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Copier l'application buildée depuis le builder
COPY --from=builder /app .

# Permissions Symfony
RUN chown -R www-data:www-data var public

# Définir le port attendu par Render
ENV PORT=10000
EXPOSE $PORT

# Script d'entrypoint pour lancer migrations + Apache
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Démarrage
CMD ["entrypoint.sh"]