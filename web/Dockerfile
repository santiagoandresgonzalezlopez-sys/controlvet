# syntax=docker/dockerfile:1
# Imagen del frontend web (MVVM original). Contexto de build: raíz del repositorio.
# No contiene credenciales de base de datos: solo consume las APIs internas.
FROM php:8.2-apache
ARG APP_VERSION=dev
LABEL org.opencontainers.image.title="controlvet-web" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.description="ControlVet · frontend web (vistas y ViewModels)"

RUN docker-php-ext-install -j"$(nproc)" opcache \
 && a2enmod headers
COPY infra/php/controlvet.ini "$PHP_INI_DIR/conf.d/zz-controlvet.ini"
COPY infra/apache/web.conf /etc/apache2/sites-available/000-default.conf
COPY web/ /var/www/html/
RUN rm -f /var/www/html/Dockerfile

ENV APP_VERSION=${APP_VERSION} SERVICE_NAME=web
EXPOSE 80
# Salud propia del proceso web (la salud agregada de dependencias está en /health.php).
HEALTHCHECK --interval=15s --timeout=3s --start-period=15s --retries=3 \
  CMD curl -fsS -o /dev/null "http://localhost/index.php?route=login" || exit 1
