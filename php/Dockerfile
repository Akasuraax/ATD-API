FROM php:8.2-fpm
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    cron \
    python3


RUN apt-get clean && rm -rf /var/lib/apt/list/*

RUN docker-php-ext-install pdo_pgsql mbstring gd fileinfo

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Add the Laravel Scheduler in cron
RUN echo "* * * * * php artisan schedule:run >> /var/log/cron.log 2>&1" >> /etc/crontab
RUN crontab /etc/crontab
RUN chmod 0644 /etc/crontab
#Create the cron log file
RUN touch /var/log/cron.log

COPY entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Définir le script d'initialisation comme point d'entrée
ENTRYPOINT ["entrypoint.sh"]
