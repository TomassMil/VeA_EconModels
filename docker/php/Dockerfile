FROM php:8.3-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git unzip zip curl \
    libonig-dev libxml2-dev libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql intl \
    && rm -rf /var/lib/apt/lists/*

# Install Composer (for artisan, not installs)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP production settings. Default: prod
COPY docker/php/conf.d/opcache-prod.ini /usr/local/etc/php/conf.d/opcache.ini

# Work directory
WORKDIR /var/www/html

# Copy entrypoint script
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy app code
COPY ./src ./

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
