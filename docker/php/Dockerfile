FROM php:8.3-fpm

# System dependencies (incl. Python3 priekš QuantStats atskaišu ģenerēšanas)
RUN apt-get update && apt-get install -y \
    git unzip zip curl \
    libonig-dev libxml2-dev libicu-dev \
    libpq-dev \
    python3 python3-pip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_pgsql intl bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pip3 install --break-system-packages --no-cache-dir \
        quantstats psycopg2-binary pandas numpy \
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

# Copy QuantStats Python skriptu (mounted ārpus /var/www/html)
COPY ./scripts /var/www/scripts

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]