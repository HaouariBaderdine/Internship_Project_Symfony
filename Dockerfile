FROM php:8.1-fpm

# Installation de Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    mv composer.phar /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer && \
    php -r "unlink('composer-setup.php');"

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y libicu-dev zip unzip
RUN docker-php-ext-install pdo pdo_mysql intl

COPY . .

CMD ["php-fpm"]
