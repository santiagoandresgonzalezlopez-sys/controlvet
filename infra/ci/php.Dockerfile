# syntax=docker/dockerfile:1
# Agente efímero de Jenkins para las etapas PHP (lint, Composer, PHPUnit con
# cobertura). Misma versión de PHP que los contenedores de ejecución (8.2).
FROM composer:2.8 AS composer

FROM php:8.2-cli
RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip \
 && rm -rf /var/lib/apt/lists/* \
 && pecl install pcov && docker-php-ext-enable pcov
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer COMPOSER_NO_INTERACTION=1
