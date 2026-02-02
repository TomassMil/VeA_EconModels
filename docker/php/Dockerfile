FROM php:8.3-fpm

# Instalē sistēmas atkarības un PHP paplašinājumus
RUN apt-get update && apt-get install -y \
    git unzip zip libpq-dev libonig-dev libxml2-dev curl \
    && docker-php-ext-install pdo pdo_mysql

# Instalē Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Darba direktorija konteinerā
WORKDIR /var/www/html

# Nokopē Laravel failus (ja tie jau ir build brīdī)
COPY ./src ./

# Palaid composer install, ja nepieciešams
RUN composer install --no-dev --optimize-autoloader || true

CMD ["php-fpm"]
