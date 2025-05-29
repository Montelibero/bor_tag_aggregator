FROM php:8.2-cli-alpine

# Установим зависимости
RUN apk add --no-cache \
    libzip-dev \
    zlib-dev \
    libxml2-dev \
    curl-dev \
    git \
    unzip \
    pkgconfig

# Установим PHP расширения
RUN docker-php-ext-install bcmath curl soap zip

# Установим Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /usr/src/crawler

# Копируем проект и ставим зависимости
COPY . .
RUN composer install --no-dev --optimize-autoloader

# Команда запуска
CMD ["php", "get_tags.php"]
