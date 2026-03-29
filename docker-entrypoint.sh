#!/bin/bash
set -e

# Fix permissions for logs and uploads directories
# These need to be writable by the www-data user (Apache)
mkdir -p /var/www/html/logs /var/www/html/uploads/avatars
chown -R www-data:www-data /var/www/html/logs /var/www/html/uploads
chmod -R 777 /var/www/html/logs /var/www/html/uploads

# Execute the main command
exec "$@"
