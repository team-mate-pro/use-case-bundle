FROM php:8.2-cli-alpine AS base

RUN apk --update add --no-cache \
    libpng \
    libzip \
    icu \
    && apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libzip-dev \
    icu-dev \
    && docker-php-ext-install zip intl \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Install Bash and Composer
RUN apk --update add --no-cache bash \
    && wget -O /usr/local/bin/composer https://getcomposer.org/composer-stable.phar \
    && chmod +x /usr/local/bin/composer

FROM base AS bash

WORKDIR /app

COPY . .

# Install Composer dependencies, including dev dependencies for testing
RUN composer install --no-ansi --no-interaction --no-progress --optimize-autoloader

# Add PHPUnit to PATH
ENV PATH="/app/vendor/bin:${PATH}"

ENTRYPOINT ["bash"]

FROM bash AS lib

COPY --from=bash /app /app

ENTRYPOINT ["composer"]
