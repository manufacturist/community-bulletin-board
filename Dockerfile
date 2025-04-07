FROM php:8.4.5-fpm-alpine
LABEL authors="https://github.com/manufacturist"

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    apk add --no-cache \
    gettext-dev \
    libintl \
    php84-pdo \
    php84-gettext \
    mariadb-client # Internal debugging purposes

RUN docker-php-ext-install pdo pdo_mysql gettext

WORKDIR /var/www

COPY . /var/www/

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "/var/www/public"]
