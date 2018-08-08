FROM php:7.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        zip \
        unzip

RUN docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo_mysql \
    && pecl install -o -f redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get clean -y \
    && apt-get autoclean -y \
    && apt-get autoremove -y \
    && rm -fr /tmp/* \
    && rm -fr /vat/lib/{apt,dkpg,cache,log,tmp}/*
    
