#!/bin/sh
set -e

# Vérifier que le port Render est défini
PORT=${PORT:-10000}

# Lancer les migrations
echo "Running Doctrine migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || true

# Configurer Apache pour écouter le port Render
echo "Listen ${PORT}" >> /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Démarrer Apache en avant-plan
echo "Starting Apache on port ${PORT}..."
apache2-foreground