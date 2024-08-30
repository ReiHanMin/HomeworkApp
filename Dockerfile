# Use the official PHP image with Apache
FROM php:8.2-apache

# Set environment variable for the port
ENV PORT 80

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Set the ServerName to localhost to suppress Apache warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Update Apache to listen on the environment variable PORT
RUN echo "Listen ${PORT}" >> /etc/apache2/ports.conf

# Expose port 80
EXPOSE 80

# Install application dependencies
RUN composer install --no-dev --optimize-autoloader

# Set file permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Start Apache in the foreground
CMD ["apache2-foreground"]
