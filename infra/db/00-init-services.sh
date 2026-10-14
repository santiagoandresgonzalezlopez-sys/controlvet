#!/bin/bash
# Inicialización de la base de datos (solo se ejecuta con el volumen vacío).
#
# Aislamiento de persistencia: cada microservicio recibe su propio esquema
# cv_<servicio> y un usuario <servicio>_svc con privilegios EXCLUSIVAMENTE
# sobre ese esquema. Ningún servicio puede leer ni modificar tablas de otro.
# El usuario nagios_mon solo puede conectarse (USAGE) para el monitoreo.
set -euo pipefail

SERVICIOS=(auth pacientes citas clinica inventario documentos)

sql() { mariadb --protocol=socket -uroot -p"${MARIADB_ROOT_PASSWORD}" "$@"; }
esc() { printf "%s" "$1" | sed "s/'/''/g"; }

for svc in "${SERVICIOS[@]}"; do
    var="$(echo "${svc}" | tr '[:lower:]' '[:upper:]')_DB_PASSWORD"
    pass="${!var:-}"
    if [ -z "${pass}" ]; then
        echo "ERROR: falta la variable ${var}" >&2
        exit 1
    fi

    echo "[controlvet] Creando esquema cv_${svc} y usuario ${svc}_svc"
    sql <<SQL
CREATE DATABASE IF NOT EXISTS cv_${svc} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${svc}_svc'@'%' IDENTIFIED BY '$(esc "${pass}")';
GRANT SELECT, INSERT, UPDATE, DELETE ON cv_${svc}.* TO '${svc}_svc'@'%';
SQL

    for f in /opt/controlvet-sql/"${svc}"/*.sql; do
        echo "[controlvet]   aplicando $(basename "$f")"
        sql "cv_${svc}" < "$f"
    done
done

if [ -n "${MONITOR_DB_PASSWORD:-}" ]; then
    echo "[controlvet] Creando usuario de monitoreo nagios_mon (solo USAGE)"
    sql -e "CREATE USER IF NOT EXISTS 'nagios_mon'@'%' IDENTIFIED BY '$(esc "${MONITOR_DB_PASSWORD}")';"
fi

sql -e "FLUSH PRIVILEGES;"
echo "[controlvet] Inicialización completada"
