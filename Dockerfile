FROM php:8.2-apache AS base

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install PHP extensions needed by Symfony
RUN apt-get update && apt-get install -y \
        libicu-dev \
        libzip-dev \
        git \
        wget \
        curl \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js and Yarn
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g yarn \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Sets symfony web folder as document_root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# ============================================================
# DEVELOPMENT TARGET
# ============================================================
FROM base AS development

# Install and configure Xdebug for development
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=VSCODE" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Use development PHP settings
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Development environment
ENV APP_ENV=dev
ENV APP_DEBUG=1

EXPOSE 80

# ============================================================
# PRODUCTION BUILDER (intermediate stage)
# ============================================================
FROM base AS builder

# Copy application files
COPY skeleton/ /var/www/html/

# Create minimal .env file for Symfony (required for composer install scripts)
RUN echo "APP_ENV=prod" > /var/www/html/.env

# Install PHP dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Build frontend assets (if using webpack encore)
RUN if [ -f "package.json" ]; then \
        yarn install --frozen-lockfile && \
        yarn build; \
    fi

# ============================================================
# PRODUCTION TARGET
# ============================================================
FROM base AS production

# Use production PHP settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configure OPcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/opcache.ini

# Copy built application from builder (includes .env created in builder stage)
COPY --from=builder /var/www/html /var/www/html

# Copy Apache config
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/var 2>/dev/null || true

# Production environment
ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1
