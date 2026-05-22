FROM php:8.3-fpm-alpine

# 1. Instalar dependencias del sistema, extensiones de PHP y NodeJS + NPM (Soporte Postgres incluido)
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    mysql-client \
    postgresql-dev \
    nodejs \
    npm

# 2. Instalar extensiones de PHP indispensables para Laravel (Drivers pgsql añadidos)
RUN docker-php-ext-install pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# 3. Instalar Composer de forma global
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 4. Copiar todos los archivos del proyecto al contenedor
COPY . .

# 5. Configurar variables necesarias para la compilación de Vite
ENV VITE_APP_NAME="Laravel"
ENV VITE_STRIPE_KEY="pk_test_51TWNJrD2FmSxlbdnZ45Lr7vYBhPE2tqSqdDUvceDJdil6LqcDAEEfWyHcrEBsCPX90zTB0Ox3Y84NrvNtH1gpKoA00vOicjsyJ"
ENV VITE_REVERB_APP_KEY="g0x8fwadh8e3hxducuby"
ENV VITE_REVERB_HOST="sistema-asientos-uqroo-svelte.onrender.com"
ENV VITE_REVERB_PORT="443"
ENV VITE_REVERB_SCHEME="https"
ENV NODE_ENV=production

# 6. Instalar dependencias de PHP y generar la optimización de clases
RUN composer install --no-interaction --optimize-autoloader

# 7. Instalar TODAS las dependencias de Node (incluyendo devDependencies de Vite) y compilar el frontend
RUN npm install --include=dev && npm run build

# 8. Ajustar permisos de almacenamiento y caché para que Laravel pueda escribir sin problemas
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Configuración del servidor Nginx
RUN printf 'server { \n\
    listen 80; \n\
    index index.php index.html; \n\
    root /var/www/html/public; \n\
    location / { try_files $uri $uri/ /index.php?$query_string; } \n\
    location ~ \.php$ { \n\
        try_files $uri =404; \n\
        fastcgi_split_path_info ^(.+\.php)(/.+)$; \n\
        fastcgi_pass 127.0.0.1:9000; \n\
        fastcgi_index index.php; \n\
        include fastcgi_params; \n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \n\
        fastcgi_param PATH_INFO $fastcgi_path_info; \n\
    } \n\
}' > /etc/nginx/http.d/default.conf

# 10. Configuración del administrador de procesos Supervisor con saltos de línea limpios
RUN printf '[supervisord]\n\
nodaemon=true\n\
user=root\n\
\n\
[program:nginx]\n\
command=nginx -g "daemon off;"\n\
\n\
[program:php-fpm]\n\
command=php-fpm\n' > /etc/supervisord.conf

EXPOSE 80

# 11. Limpia caché, optimiza, corre migraciones, INYECTA SEEDERS DE PRODUCCIÓN y enciende Supervisor
CMD php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php artisan db:seed --force && /usr/bin/supervisord -c /etc/supervisord.conf