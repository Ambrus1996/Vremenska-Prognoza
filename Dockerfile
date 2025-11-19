# --- Stage 1: Composer Dependency Builder ---
# [CHANGED] Koristimo tacnu verziju 2.8.1 umesto samo "2"
FROM composer:2.8.1 as vendor

WORKDIR /app
COPY composer.json composer.lock ./
# Install only production dependencies, optimized autoloader
RUN composer install --no-dev --optimize-autoloader

# --- Stage 2: Production Image (PHP + Apache) ---
# [CHANGED] Koristimo tacnu verziju 8.2.25-apache umesto "8.2-apache"
FROM php:8.2.25-apache

# [PRO FIX] Update Linux packages to fix known CVEs
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