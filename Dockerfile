# --- Faza 1: Composer (Dependency Builder) ---
# Koristimo zvaničnu Composer sliku da instaliramo zavisnosti
FROM composer:2 as vendor

WORKDIR /app
# Kopiramo samo fajlove potrebne za instalaciju zavisnosti
COPY composer.json composer.lock ./
# Instaliramo samo produkcijske zavisnosti, optimizovano
RUN composer install --no-dev --optimize-autoloader


# --- Faza 2: Produkcijska Slika (PHP + Apache) ---
FROM php:8.2-apache

# Instaliramo potrebne PHP ekstenzije
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

# Kopiramo zavisnosti (vendor folder) iz prve faze
COPY --from=vendor /app/vendor/ /var/www/html/vendor/
# Kopiramo ostatak aplikacije
COPY . /var/www/html

# Postavljamo Apache da gleda u 'public' folder
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Omogućavamo mod_rewrite za lepe URL-ove (framework-friendly)
RUN a2enmod rewrite

# Postavljamo ispravne dozvole
RUN chown -R www-data:www-data /var/www/html