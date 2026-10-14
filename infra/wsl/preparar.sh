#!/bin/bash
# Preparación inicial de WSL2 (Ubuntu 24.04) para ControlVet. Ejecutar como root:
#   wsl -d Ubuntu-24.04 -u root -- bash /mnt/c/xampp/htdocs/controlvet/infra/wsl/preparar.sh
# Instala Ansible (controlador de la fase OPERATE) y delega en Ansible la
# instalación y configuración de Nagios (fase MONITOR).
set -euo pipefail

REPO="$(cd "$(dirname "$0")/../.." && pwd)"
export DEBIAN_FRONTEND=noninteractive

echo "[controlvet] Instalando Ansible y utilidades..."
apt-get update -qq
apt-get install -y -qq ansible unzip curl >/dev/null
ansible --version | head -1

# systemd (activo por defecto en Ubuntu 24.04 para WSL) arranca Nagios y Apache.
if ! grep -q '^systemd=true' /etc/wsl.conf 2>/dev/null; then
    printf '[boot]\nsystemd=true\n' >> /etc/wsl.conf
    echo "[controlvet] systemd activado en /etc/wsl.conf (requiere: wsl --terminate Ubuntu-24.04)"
fi

echo "[controlvet] Configurando Nagios con Ansible..."
cd "$REPO/infra/ansible"
ANSIBLE_FORCE_COLOR=0 ansible-playbook -i inventarios/local.yml playbooks/monitoreo.yml
echo "[controlvet] Listo. Nagios: http://localhost:8092/nagios4 (usuario nagiosadmin; clave en C:\\xampp\\controlvet-secretos\\nagios_admin)"
