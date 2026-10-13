FROM php:8.2-apache

# Habilitar el módulo rewrite de Apache para que .htaccess funcione correctamente
RUN a2enmod rewrite

# Instalar la extensión pdo_mysql requerida por PHP
RUN docker-php-ext-install pdo_mysql

# Copiar los archivos del proyecto al directorio web de Apache
COPY . /var/www/html/

# Dar permisos adecuados
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80
EXPOSE 80