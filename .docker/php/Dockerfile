FROM php:8.1-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends vim curl debconf subversion git apt-transport-https apt-utils \
    build-essential locales acl mailutils wget zip unzip \
    gnupg gnupg1 gnupg2

RUN apt-get update && apt-get install -y \
    libmcrypt-dev \
    zlib1g-dev \
    libicu-dev \
    libpng-dev \
    libzip-dev\
    g++

RUN apt install -y mariadb-client

RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-configure intl && docker-php-ext-install intl
RUN docker-php-ext-install gd
RUN docker-php-ext-install zip


COPY .docker/php/php.ini /usr/local/etc/php
#COPY .docker/php/php-fpm-pool.conf /etc/php/7.2.3/pool.d/www.conf

# RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
#    mv composer.phar /usr/local/bin/composer

RUN groupadd dev -g 999
RUN useradd dev -g dev -d /home/dev -m

RUN rm -rf /var/lib/apt/lists/*
RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen
#add cron
# php bin/console backup-manager:backup development s3 -c gzip --filename test/backup.sql
#COPY . /app

WORKDIR /app

#RUN composer install --prefer-source --no-interaction

EXPOSE 9000
CMD ["php-fpm"]
