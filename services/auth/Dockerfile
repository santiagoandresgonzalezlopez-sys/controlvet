# syntax=docker/dockerfile:1
# Imagen del microservicio auth. Contexto de build: raíz del repositorio.

# --- Etapa 1: dependencias (Composer) -------------------------------------
FROM composer:2.8 AS deps
WORKDIR /src
COPY libs/kernel /src/libs/kernel
COPY services/auth/composer.json services/auth/composer.lock /src/services/auth/
WORKDIR /src/services/auth
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader
COPY services/auth /src/services/auth
RUN composer dump-autoload --no-dev --classmap-authoritative

# --- Etapa 2: ejecución (Apache + PHP 8.2) --------------------------------
FROM php:8.2-apache AS runtime
ARG APP_VERSION=dev
LABEL org.opencontainers.image.title="controlvet-auth" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.description="ControlVet · microservicio auth (usuarios, roles, JWT)"

RUN docker-php-ext-install -j"$(nproc)" pdo_mysql opcache \
 && a2enmod headers \
 && rm -rf /var/www/html
COPY infra/php/controlvet.ini "$PHP_INI_DIR/conf.d/zz-controlvet.ini"
COPY infra/apache/service.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/app
COPY --from=deps /src/services/auth/vendor ./vendor
COPY --from=deps /src/services/auth/public ./public
COPY --from=deps /src/services/auth/controller ./controller
COPY --from=deps /src/services/auth/validador ./validador
COPY --from=deps /src/services/auth/microservicio ./microservicio

ENV APP_VERSION=${APP_VERSION} SERVICE_NAME=auth
EXPOSE 80
HEALTHCHECK --interval=15s --timeout=3s --start-period=20s --retries=3 \
  CMD curl -fsS http://localhost/health || exit 1
