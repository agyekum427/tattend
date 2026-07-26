# Use official PHP image with Apache web server
FROM php:8.2-apache

# Install required PHP extensions for MySQL database communication
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache rewrite module (needed for clean routing if used)
RUN a2enmod rewrite

# Copy project files to the Apache web directory
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Grant proper permissions to web root
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (or your provider's expected port)
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]