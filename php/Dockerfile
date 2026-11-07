FROM php:8.3-apache

ENV TZ=Europe/Moscow \
    DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        libicu-dev \
        libxml2-dev \
        libonig-dev \
        libxslt1-dev \
        unzip \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip mbstring intl xsl exif opcache \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    curl -fsSL -o /tmp/ioncube.tar.gz https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz; \
    mkdir -p /tmp/ioncube; \
    tar -xzf /tmp/ioncube.tar.gz -C /tmp/ioncube; \
    cp /tmp/ioncube/ioncube/ioncube_loader_lin_8.3.so "$(php -r 'echo ini_get("extension_dir");')/"; \
    printf 'zend_extension=%s/ioncube_loader_lin_8.3.so\n' "$(php -r 'echo ini_get("extension_dir");')" > /usr/local/etc/php/conf.d/00-ioncube.ini; \
    rm -rf /tmp/ioncube /tmp/ioncube.tar.gz

COPY php.ini /usr/local/etc/php/conf.d/zz-bitrix.ini
COPY apache.conf /etc/apache2/conf-available/zz-bitrix.conf
RUN a2enconf zz-bitrix

WORKDIR /var/www/html
