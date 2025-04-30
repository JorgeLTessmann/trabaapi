# Usar a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instalar extensões necessárias
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite para o Apache
RUN a2enmod rewrite

# Copiar arquivos da aplicação
COPY api/ /var/www/html/

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html