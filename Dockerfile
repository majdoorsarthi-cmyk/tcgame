FROM php:8.2-apache

# Enable Apache Mod Rewrite
RUN a2enmod rewrite

# Install MySQLi extension for PHP
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy repository code to Apache document root
COPY . /var/www/html/

# Expose port 80
EXPOSE 80
