FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    && docker-php-ext-install \
    intl \
    mysqli \
    pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html