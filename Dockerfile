FROM php:8.2-apache

# rewrite + FallbackResource: el login no depende de que el .zip traiga los .htaccess
RUN a2enmod rewrite \
    && docker-php-ext-install pdo pdo_mysql \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY docker/apache-fitpower.conf /etc/apache2/conf-available/fitpower.conf
COPY docker/entrypoint.sh /usr/local/bin/fitpower-entrypoint.sh
RUN a2enconf fitpower \
    && sed -i 's/\r$//' /usr/local/bin/fitpower-entrypoint.sh \
    && chmod +x /usr/local/bin/fitpower-entrypoint.sh

WORKDIR /var/www/html
ENTRYPOINT ["/usr/local/bin/fitpower-entrypoint.sh"]
