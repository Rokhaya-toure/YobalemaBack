#!/bin/sh
set -e

# Lancer les migrations
echo "Running Doctrine migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || true

# Debug: afficher les routes
echo "Listing Symfony routes..."
php bin/console debug:router | grep api

# Configurer Apache pour le bon port
echo "Configuring Apache to listen on port $PORT..."
sed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT:-10000}/" /etc/apache2/sites-available/000-default.conf

# Démarrer Apache
echo "Starting Apache on port $PORT..."
apache2-foreground