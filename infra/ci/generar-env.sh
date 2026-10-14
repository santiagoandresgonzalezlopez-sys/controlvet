#!/bin/sh
# Genera un archivo de entorno con secretos aleatorios para un stack
# efímero de pruebas (CI). Uso: generar-env.sh <proyecto> <versión> <registro>
set -eu

rnd() { head -c 48 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c "${1:-24}"; }

cat <<EOF
CV_PROJECT=$1
CV_VERSION=$2
CV_REGISTRY=$3
APP_ENV=prod
WEB_PORT=
JWT_SECRET=$(rnd 48)
DB_ROOT_PASSWORD=$(rnd)
AUTH_DB_PASSWORD=$(rnd)
PACIENTES_DB_PASSWORD=$(rnd)
CITAS_DB_PASSWORD=$(rnd)
CLINICA_DB_PASSWORD=$(rnd)
INVENTARIO_DB_PASSWORD=$(rnd)
DOCUMENTOS_DB_PASSWORD=$(rnd)
MONITOR_DB_PASSWORD=$(rnd)
EOF
