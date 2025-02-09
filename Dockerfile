FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install PHP extensions needed by Symfony
RUN apt-get update && apt-get install -y \
        libicu-dev \
        libzip-dev \
        wget \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set recommended PHP.ini settings
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Install composer
RUN wget https://getcomposer.org/composer.phar -O /usr/bin/composer
RUN chmod +x /usr/bin/composer

# Sets symfony web folder as document_root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

