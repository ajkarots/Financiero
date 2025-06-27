# Etapa 1: instalar dependencias con Composer
FROM composer:2 AS builder
WORKDIR /app

# Forzar a Apache a buscar index.html como índice
RUN echo "<IfModule mod_dir.c>\n  DirectoryIndex index.html index.php\n</IfModule>" \
    > /etc/apache2/conf-available/serve-index.conf \
  && a2enconf serve-index


# Copiar archivos de Composer y descargar dependencias
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copiar el resto del código
COPY . .

# Comando por defecto\CMD ["apache2-foreground"]
FROM php:8.2-apache

# Instalar extensiones necesarias
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev zip unzip libonig-dev \
    && docker-php-ext-install pdo_mysql mysqli zip mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copiar la aplicación desde el builder\COPY --from=builder /app /var/www/html

# Ajustar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

WORKDIR /var/www/html

# Exponer el puerto 80 para Apache\EXPOSE 80



# Copia los archivos de tu proyecto al contenedor
COPY . /var/www/html/

# Habilita módulos necesarios (opcional)
RUN docker-php-ext-install mysqli

EXPOSE 80