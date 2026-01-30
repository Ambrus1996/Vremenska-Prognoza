# --- Stage 1: Composer Dependency Builder ---
# [CHANGED] Use exact version 2.8.1 instead of just "2"
FROM composer:2.8.1 as vendor

WORKDIR /app
COPY composer.json composer.lock ./
# Install only production dependencies, optimized autoloader
RUN composer install --no-dev --optimize-autoloader

# --- Stage 2: Production Image (PHP + Apache) ---
# [CHANGED] Use exact version 8.2.25-apache instead of "8.2-apache"
FROM php:8.2.25-apache

# [SECURITY FIX] Update Linux packages to patch OpenSSL CVE-2025-15467
# This is CRITICAL for passing Trivy security scan
RUN apt-get update && apt-get upgrade -y && apt-get clean

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

# Enable Apache's mod_rewrite for nice URLs (e.g., /city/London)
RUN a2enmod rewrite
# Set Apache's document root to the /public directory, which is more secure
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Copy vendor folder from the 'vendor' stage WITH the correct owner
COPY --chown=www-data:www-data --from=vendor /app/vendor/ ./vendor/
# Copy the rest of the application code WITH the correct owner
COPY --chown=www-data:www-data . .