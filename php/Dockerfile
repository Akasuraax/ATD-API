FROM php:8.2-fpm as development

#Installation de tous les outils dont nous avons besoin pour faire tourner notre server

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

#On allège l'image en supprimer les fichiers de cache et les listes de paquets disponibles dans /list (utilisées par APT)
RUN apt-get clean && rm -rf /var/lib/apt/list/*

#Installe les dépendances de php nécessaires pour notre projet

RUN docker-php-ext-install pdo_pgsql mbstring gd fileinfo

#Installe composer pour créer notre projet laravel/les pdf etc...
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

#Ajoute le laravel scheduler dans le cron
RUN echo "* * * * * php artisan schedule:run >> /var/log/cron.log 2>&1" >> /etc/crontab
RUN crontab /etc/crontab
RUN chmod 0644 /etc/crontab

#Créer le fichier de log du cron
RUN touch /var/log/cron.log

# Ce fichier me permet d'effectuer toutes les commandes nécessaires à notre api en fonction de si c'est le premier lancement ou non.
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
