# PHP ve Apache içeren resmi imajı kullan
FROM php:8.2-apache

# Gerekli PHP eklentilerini yükle
RUN docker-php-ext-install pdo pdo_sqlite

# Proje dosyalarını kopyala
COPY . /var/www/html/

# Apache için çalışma dizini
WORKDIR /var/www/html/

# Apache modlarını etkinleştir
RUN a2enmod rewrite
