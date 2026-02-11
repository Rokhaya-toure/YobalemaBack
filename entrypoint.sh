#!/bin/sh
set -e

# Lancer les migrations
echo "Running Doctrine migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || true

# Démarrer Apache en avant-plan
echo "Starting Apache..."
apache2-foreground