# --- Etapa 1: Compilar Frontend (Svelte/Vite) ---
    FROM node:20-alpine AS frontend-builder
    WORKDIR /app
    COPY package*.json ./
    RUN npm ci
    COPY . .
    
    # Variables de entorno requeridas por Vite para compilar el build de producción
    ENV VITE_APP_NAME="Laravel"
    ENV VITE_STRIPE_KEY="pk_test_51TWNJrD2FmSxlbdnZ45Lr7vYBhPE2tqSqdDUvceDJdil6LqcDAEEfWyHcrEBsCPX90zTB0Ox3Y84NrvNtH1gpKoA00vOicjsyJ"
    ENV VITE_REVERB_APP_KEY="g0x8fwadh8e3hxducuby"
    ENV VITE_REVERB_HOST="sistema-asientos-uqroo-svelte.onrender.com"
    ENV VITE_REVERB_PORT="443"
    ENV VITE_REVERB_SCHEME="https"
    
    RUN npm run build
    
    # --- Etapa 2: Servidor de Producción (PHP + Nginx) ---
    FROM php:8.3-fpm-alpine
    
    # Instalar dependencias del sistema y extensiones PHP necesarias para Laravel
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
        mysql-client
    
    RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd
    
    # Instalar Composer
    COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
    
    WORKDIR /var/www/html
    
    # Copiar el código del proyecto
    COPY . .
    
    # Copiar el frontend ya compilado desde la etapa 1
    COPY --from=frontend-builder /app/public/build ./public/build
    
    # Instalar dependencias de PHP para producción
    RUN composer install --no-interaction --optimize-autoloader --no-dev
    
    # Configurar permisos correctos para Laravel
    RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
        && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
    
    # Crear archivos de configuración internos para Nginx y Supervisor
    RUN echo 'server { \
        listen 80; \
        index index.php index.html; \
        root /var/www/html/public; \
        location / { try_files $uri $uri/ /index.php?$query_string; } \
        location ~ \.php$ { \
            try_files $uri =404; \
            fastcgi_split_path_info ^(.+\.php)(/.+)$; \
            fastcgi_pass 127.0.0.1:9000; \
            fastcgi_index index.php; \
            include fastcgi_params; \
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
            fastcgi_param PATH_INFO $fastcgi_path_info; \
        } \
    }' > /etc/nginx/http.d/default.conf
    
    RUN echo '[supervisord] \n\
    nodaemon=true \n\
    user=root \n\
    [program:nginx] \n\
    command=nginx -g "daemon off;" \n\
    [program:php-fpm] \n\
    command=php-fpm' > /etc/supervisord.conf
    
    EXPOSE 80
    
    # Comando de arranque: Limpia caché, optimiza y enciende Supervisor
    CMD php artisan config:cache && php artisan route:cache && php artisan view:cache && /usr/bin/supervisord -c /etc/supervisord.conf