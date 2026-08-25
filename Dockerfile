FROM php:8.3-cli-alpine

# Install system dependencies & Postgres PDO extension
RUN apk add --no-cache postgresql-dev libpng-dev libzip-dev zip unzip git \
    && docker-php-ext-install pdo pdo_pgsql bcmath

# Copy composer binary from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy application files
COPY . .

# Install PHP composer production dependencies
RUN composer install --no-dev --optimize-autoloader

EXPOSE 8000

# Run migrations and start Laravel server
CMD ["sh", "-c", "php artisan migrate --force --seed && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]

