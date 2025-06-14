# Image officielle de PHP avec Apache
FROM php:8.2-apache

# Copie tout le code dans le dossier web d’Apache
COPY . /var/www/html/

RUN a2enmod rewrite

# Ouvrir le port 80
EXPOSE 80
