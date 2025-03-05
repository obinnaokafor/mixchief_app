FROM php:8.0-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    unzip \
    default-mysql-client

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . .

RUN composer config allow-plugins.symfony/flex true

RUN composer install --no-scripts

RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]