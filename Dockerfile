# --- Stage 1: Composer Dependency Builder ---
# Using specific Composer version for stability
FROM composer:2.8.1 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
# Install only production dependencies, optimized autoloader
RUN composer install --no-dev --optimize-autoloader

# --- Stage 2: Production Image (PHP + Apache) ---
# Using specific PHP version
FROM php:8.2.25-apache

# [SECURITY FIX] Force update of OpenSSL and system libraries to patch CVE-2025-15467
# We explicitly install libssl3 to ensure the cache is broken and the patch is applied
RUN apt-get update && \
    apt-get install -y --only-upgrade openssl libssl3 && \
    apt-get upgrade -y && \
    apt-get clean

# Install required PHP extensions and Redis via PECL
# autoconf, g++, and make are required for pecl to compile the extension
RUN apt-get update && apt-get install -y \
    autoconf \
    g++ \
    make \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get purge -y autoconf g++ make && apt-get autoremove -y && apt-get clean

WORKDIR /var/www/html

# Enable Apache's mod_rewrite for nice URLs
RUN a2enmod rewrite
# Set Apache's document root to /public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Copy vendor folder from the 'vendor' stage WITH the correct owner
COPY --chown=www-data:www-data --from=vendor /app/vendor/ ./vendor/
# Copy the rest of the application code WITH the correct owner
COPY --chown=www-data:www-data . .