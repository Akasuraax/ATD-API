#!/bin/bash
set -e

# Vérifier si les dépendances ont déjà été installées
if [ ! -f /var/www/html/.initialized ]; then
    # Installer les dépendances Laravel via Composer
    composer install --working-dir=/var/www/html

    # Effectuer les migrations et le seeding de la base de données
    php artisan migrate
    php artisan db:seed --class=DatabaseSeeder 
    php artisan db:seed --class=AnnexeSeeder 
    php artisan db:seed --class=ProblemSeeder 
    php artisan db:seed --class=ProductSeeder 
    php artisan db:seed --class=TypeSeeder 
    php artisan db:seed --class=DonationSeeder 
    php artisan db:seed --class=ScheduleSeeder 
    php artisan db:seed --class=VehicleSeeder 
    php artisan db:seed --class=ActivitySeeder  

    touch /var/www/html/.initialized
fi

composer update --working-dir=/var/www/html

# Démarrer le serveur PHP intégré
php artisan serve --host=0.0.0.0 --port=8000 