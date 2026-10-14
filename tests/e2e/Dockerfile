# syntax=docker/dockerfile:1
# Ejecutor de las pruebas de integración/API/regresión. Se conecta a la red
# interna "backend" del entorno a probar. Contexto de build: tests/e2e.
FROM composer:2.8 AS deps
WORKDIR /e2e
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist

FROM php:8.2-cli
RUN docker-php-ext-install -j"$(nproc)" pdo_mysql
WORKDIR /e2e
COPY --from=deps /e2e/vendor ./vendor
COPY phpunit.xml ./
COPY src ./src
RUN mkdir -p /e2e/reports
ENTRYPOINT ["php", "vendor/bin/phpunit"]
CMD ["--log-junit", "/e2e/reports/junit-e2e.xml"]
