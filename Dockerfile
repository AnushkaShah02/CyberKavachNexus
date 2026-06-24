FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip zip \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev

 RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# 🔥 CRITICAL: point Apache to public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite

RUN composer install --no-dev --optimize-autoloader

EXPOSE 80