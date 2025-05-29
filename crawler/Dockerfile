FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    libzip-dev \
    zlib-dev \
    libxml2-dev \
    curl-dev \
    git \
    unzip \
    pkgconfig

RUN docker-php-ext-install bcmath curl soap zip

WORKDIR /usr/src/crawler

COPY . .
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

CMD ["sh", "init.sh"]
