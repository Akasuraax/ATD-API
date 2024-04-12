FROM php:8.2-fpm
EXPOSE 8000

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

ENTRYPOINT ["sh", "-c", "composer install && php artisan migrate && php artisan db:seed && php artisan serve --host=0.0.0.0 --port=8000"]
