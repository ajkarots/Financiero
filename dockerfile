# Etapa 1: instalar dependencias con Composer
FROM composer:2 AS builder
WORKDIR /app

# Copiar archivos de Composer y descargar dependencias
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copiar el resto del código
COPY . .

# Etapa 2: entorno de producción PHP + Apache
FROM php:8.1-apache

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

# Comando por defecto\CMD ["apache2-foreground"]
