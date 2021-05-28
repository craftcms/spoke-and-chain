FROM composer as composer
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --ignore-platform-reqs --no-interaction --prefer-dist

FROM craftcms/nginx:8.0
COPY --chown=www-data:www-data --from=composer /app/vendor/ /app/vendor/
COPY --chown=www-data:www-data . .
