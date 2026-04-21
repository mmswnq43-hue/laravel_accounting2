FROM php:8.2-cli-alpine

# Install system dependencies required by ext-gd and other extensions
RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    zlib-dev \
    libzip-dev \
    oniguruma-dev \
    nodejs \
    npm \
    curl \
    bash \
    mysql-client

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_mysql \
        mbstring \
        zip \
        bcmath \
        opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy dependency files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev, no scripts yet)
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy the rest of the application
COPY . .

# Install Node dependencies and build assets
RUN npm ci && npm run build

# Run post-install composer scripts
RUN composer run-script post-autoload-dump || true

# Set permissions
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

CMD ["bash", "docker-start.sh"]
