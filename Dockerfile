# Dockerfile
FROM php:8.2-apache

# Install PDO MySQL (and mysqli, handy for tools)
RUN docker-php-ext-install pdo_mysql mysqli

# (optional) enable Apache modules you might want later
RUN a2enmod rewrite
