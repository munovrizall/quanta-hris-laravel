# Menggunakan image resmi PHP sebagai base image
FROM php:8.3-fpm

# Install dependencies yang diperlukan
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    git \
    libicu-dev \
    libzip-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql intl zip \
    && docker-php-ext-enable intl zip

# Set working directory
WORKDIR /var/www

# Copy project Laravel ke dalam container
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies project Laravel
RUN composer install

# Set permission agar folder storage dan bootstrap cache bisa diakses
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
