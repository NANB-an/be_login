# Use PHP 8.4 with Apache
FROM php:8.4-apache

# Install system dependencies & PHP extensions needed by Laravel
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev zip curl libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite (needed for Laravel routing)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install Composer (from official Composer image)
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies (production optimized)
RUN composer install --no-dev --optimize-autoloader

# Set proper permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port for Render (Render sets $PORT automatically)
EXPOSE 10000

# Configure Apache to serve Laravel from /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Make Apache use Render's PORT
RUN echo "Listen ${PORT}" >> /etc/apache2/ports.conf

# Start Apache
CMD ["apache2-foreground"]
