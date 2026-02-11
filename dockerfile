# Stage 2: Runtime
FROM php:8.2-apache

# Activer mod_rewrite
RUN a2enmod rewrite

# Changer le DocumentRoot vers /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Mettre à jour la conf Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Copier l'app Symfony
COPY --from=builder /app /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]