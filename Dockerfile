FROM php:8.1-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite y mod_headers para Apache
RUN a2enmod rewrite headers

# Copiar solo la carpeta api al directorio web
COPY api/ /var/www/html/api/

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
