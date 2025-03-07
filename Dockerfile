FROM php:8.2-apache
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y \
    libpng-dev \
    zip \
    unzip \
    curl \
    git \
    && docker-php-ext-install pdo pdo_mysql gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .
RUN mkdir -p storage bootstrap/cache
RUN composer install --no-dev --optimize-autoloader
RUN chmod -R 777 storage bootstrap/cache
EXPOSE 80
CMD ["apache2-foreground"]
