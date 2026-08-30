FROM php:8.1-apache

# Install ekstensi MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Aktifkan mod_rewrite Apache
RUN a2enmod rewrite

# Izinkan .htaccess mengubah konfigurasi Apache (AllowOverride All)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf