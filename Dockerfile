FROM php:8.4-fpm

ARG UID=10001
ARG GID=10001

RUN groupadd -g ${GID} app-betterlocation
RUN useradd -u ${UID} -g ${GID} -m app-betterlocation

# Setup environment
RUN apt update
COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN install-php-extensions json curl mbstring exif pdo_mysql simplexml dom zip

# Setup application
WORKDIR /app
COPY . .
RUN composer install

RUN chown -R app-betterlocation:app-betterlocation /app/temp

USER app-betterlocation

# Start PHP webserver
# CMD ["php-fpm"]

# Start Discord bot application
# CMD ["php", "/app/src/discord.cli.php"]
