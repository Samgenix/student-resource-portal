# Dockerfile
FROM php:8.2-apache

# Install PDO MySQL (and mysqli, handy for tools)
RUN docker-php-ext-install pdo_mysql mysqli

# (optional) enable Apache modules you might want later
RUN a2enmod rewrite

# Default upload_max_filesize (2M) / post_max_size (8M) are below the app's
# own 10MB limit, so a large-but-valid file was getting rejected by PHP
# itself before upload.php's validation ever ran. Raise both past 10MB.
RUN { \
      echo 'upload_max_filesize=12M'; \
      echo 'post_max_size=13M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini
