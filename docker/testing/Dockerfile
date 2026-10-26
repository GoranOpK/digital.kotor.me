FROM php:8.3-cli-bookworm

ENV TZ=Europe/Belgrade \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

# Official php:8.3-cli already includes ctype, curl, dom, fileinfo, mbstring,
# openssl, tokenizer and xml. Imagick is intentionally absent.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
    && printf 'date.timezone=Europe/Belgrade\n' > /usr/local/etc/php/conf.d/timezone.ini \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
