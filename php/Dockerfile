FROM php:latest

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    cron \
    python3 \
 && apt-get clean && rm -rf /var/lib/apt/lists/* \

RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd fileinfo curl openssl
WORKDIR /atd-api

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . atd-api

RUN composer install

RUN php artisan migrate --seed

ENTRYPOINT ["php", "artisan", "serve", "--host=127.0.0.1"]
