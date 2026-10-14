# syntax=docker/dockerfile:1
# Imagen de operaciones (fase OPERATE): controlador Ansible en Linux.
# Ansible no se ejecuta de forma nativa en Windows; este contenedor lo
# ejecuta en Alpine Linux y controla el motor Docker del equipo a través
# del socket montado. Se versiona junto con cada release para que el
# despliegue/rollback use exactamente los playbooks de esa versión.
FROM alpine:3.20
ARG APP_VERSION=dev
LABEL org.opencontainers.image.title="controlvet-ops" \
      org.opencontainers.image.version="${APP_VERSION}"

RUN apk add --no-cache ansible-core docker-cli docker-cli-compose bash curl gzip tzdata
ENV TZ=America/Bogota ANSIBLE_CONFIG=/ops/ansible/ansible.cfg OPS_IMAGE_VERSION=${APP_VERSION}

COPY infra/ansible /ops/ansible
COPY compose.yaml /ops/app/compose.yaml
RUN rm -f /ops/ansible/Dockerfile
WORKDIR /ops/ansible
