# Use the official PHP image with Apache
FROM php:8.2-apache

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

# Configure Apache to use only one Listen directive
RUN sed -i '/^Listen 80$/d' /etc/apache2/ports.conf

# Set ServerName directive to prevent domain name warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Ensure correct permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Install application dependencies
RUN composer install

# Expose port 80 and start Apache
EXPOSE 80
CMD ["apache2-foreground"]
