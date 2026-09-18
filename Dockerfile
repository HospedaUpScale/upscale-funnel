FROM php:8.3-fpm-alpine

# Extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql opcache

# Configuração OPcache para alta performance
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit=1255" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit_buffer_size=64M" >> /usr/local/etc/php/conf.d/opcache.ini

# PHP-FPM tuning para 50k+ acessos
RUN echo "pm = dynamic" > /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.start_servers = 10" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.max_spare_servers = 20" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.max_requests = 1000" >> /usr/local/etc/php-fpm.d/zz-performance.conf

# Instala Nginx
RUN apk add --no-cache nginx

# Copia configuração do Nginx
COPY deploy/nginx.conf /etc/nginx/http.d/default.conf

# Copia código do projeto
COPY . /var/www/html
WORKDIR /var/www/html

# Permissões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/api/logs 2>/dev/null || true
RUN mkdir -p /var/www/html/storage /var/www/html/api/logs \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/api/logs

# Script de inicialização
COPY deploy/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
