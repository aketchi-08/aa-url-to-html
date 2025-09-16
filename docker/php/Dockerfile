FROM php:8.2-fpm

# 必要パッケージインストール
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

WORKDIR /var/www
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

RUN mkdir -p /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/testing \
    /var/www/storage/framework/views \
    && chown -R www-data:www-data /var/www/storage/framework \
    && chmod -R 775 /var/www/storage/framework

RUN mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/bootstrap/cache

CMD ["php-fpm"]
