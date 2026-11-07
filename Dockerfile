FROM php:8.2-apache

# Instal dependensi sistem dan library PostgreSQL
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Aktifkan Apache mod_rewrite
RUN a2enmod rewrite

# Ambil Composer resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Atur working directory
WORKDIR /var/www/html

# Salin seluruh kode proyek
COPY . .

# Arahkan Apache Document Root ke folder public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Berikan izin eksekusi skrip entrypoint
RUN chmod +x /var/www/html/docker/entrypoint.sh

# Pasang dependensi PHP produksi
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Berikan hak akses storage dan cache ke www-data
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80 10000

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]

