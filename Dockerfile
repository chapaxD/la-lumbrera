
# Etapa 1: Build de frontend
FROM node:18 AS build-frontend
WORKDIR /app
COPY package*.json ./
COPY src/ ./src/
# No se copia public/ porque no existe
RUN npm install && npm run build

# Etapa 2: PHP + Apache
FROM php:8.1-apache

# Instalar ca-certificates y extensiones necesarias
RUN apt-get update && apt-get install -y ca-certificates && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite y mod_headers para Apache
RUN a2enmod rewrite headers

# Copiar la API PHP
COPY api/ /var/www/html/api/

# Copiar el frontend compilado
COPY --from=build-frontend /app/dist/ /var/www/html/

# Configurar Apache para permitir .htaccess
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override

# Variables de entorno de BD (se definen en Render dashboard)
ENV DB_HOST=localhost
ENV DB_NAME=botanero_ventas
ENV DB_USER=root
ENV DB_PASS=

EXPOSE 80
