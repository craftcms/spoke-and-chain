# use a multi-stage build for dependencies
FROM composer as vendor
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --ignore-platform-reqs --no-interaction --prefer-dist

FROM craftcms/nginx:8.0 as web

# TODO: this should be in our base image
USER root
RUN apk add --no-cache mysql-client mariadb-connector-c
USER www-data

COPY .docker/default.conf /etc/nginx/conf.d/default.config
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor/ ./vendor/

FROM craftcms/cli:8.0 as console

# TODO: this should be in our base image
USER root
RUN apk add --no-cache mysql-client mariadb-connector-c
USER www-data

COPY --chown=www-data:www-data --from=vendor /app/vendor/ ./vendor/
COPY --chown=www-data:www-data . .
