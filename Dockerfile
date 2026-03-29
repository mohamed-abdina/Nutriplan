FROM php:8.1-apache

RUN apt-get update \
  && apt-get install -y --no-install-recommends libzip-dev unzip git curl ca-certificates \
  && docker-php-ext-install pdo pdo_mysql mysqli zip \
  && a2enmod rewrite headers \
  && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
  && a2enconf servername \
  && rm -rf /var/lib/apt/lists/* \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Set up writable directories for the application
RUN mkdir -p /var/www/html/logs /var/www/html/uploads/avatars && \
    chown -R www-data:www-data /var/www/html/logs /var/www/html/uploads && \
    chmod -R 755 /var/www/html/logs /var/www/html/uploads && \
    chmod -R 777 /var/www/html/logs /var/www/html/uploads

# Copy and set up entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
