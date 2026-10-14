# syntax=docker/dockerfile:1
# Servidor MariaDB de ControlVet con un ESQUEMA y un USUARIO por microservicio.
# Los scripts SQL pertenecen a cada servicio (services/<svc>/database) y se
# copian aquí en el build: la imagen es reproducible y no necesita montajes.
FROM mariadb:11.4
ARG APP_VERSION=dev
LABEL org.opencontainers.image.title="controlvet-db" \
      org.opencontainers.image.version="${APP_VERSION}"

COPY infra/db/00-init-services.sh /docker-entrypoint-initdb.d/00-init-services.sh
COPY services/auth/database        /opt/controlvet-sql/auth
COPY services/pacientes/database   /opt/controlvet-sql/pacientes
COPY services/citas/database       /opt/controlvet-sql/citas
COPY services/clinica/database     /opt/controlvet-sql/clinica
COPY services/inventario/database  /opt/controlvet-sql/inventario
COPY services/documentos/database  /opt/controlvet-sql/documentos
