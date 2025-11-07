# --- Stage 1: Composer Dependency Builder ---
# Use the official composer image to install dependencies
FROM composer:2 as vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Install only production dependencies, optimized autoloader
RUN composer install --no-dev --optimize-autoloader

# --- Stage 2: Production Image (PHP + Apache) ---
FROM php:8.2-apache
# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

# Enable Apache's mod_rewrite for nice URLs (e.g., /city/London)
RUN a2enmod rewrite
# [FIX] Set Apache's document root to the /public directory for better security
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# [FIX] Copy vendor folder from the 'vendor' stage WITH the correct owner (www-data)
# This avoids a slow, layer-busting 'RUN chown -R' at the end.
COPY --chown=www-data:www-data --from=vendor /app/vendor/ ./vendor/
# [FIX] Copy the rest of the application code WITH the correct owner
COPY --chown=www-data:www-data . .

# We no longer need the slow 'RUN chown -R' command.