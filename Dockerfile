# use a multi-stage build for dependencies
FROM composer as composer
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --ignore-platform-reqs --no-interaction --prefer-dist

FROM craftcms/nginx:8.0 as web

USER root
RUN apk add --no-cache mysql-client mariadb-connector-c
COPY .docker/default.conf /etc/nginx/conf.d/default.conf
USER www-data

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=composer /app/vendor/ ./vendor/

FROM craftcms/cli:8.0 as console

USER root
RUN apk add --no-cache mysql-client mariadb-connector-c
USER www-data

COPY --chown=www-data:www-data --from=composer /app/vendor/ ./vendor/
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --chown=www-data:www-data . .
