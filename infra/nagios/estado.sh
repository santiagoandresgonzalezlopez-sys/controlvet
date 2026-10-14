#!/bin/sh
# Resumen legible del estado de Nagios para los hosts y servicios de ControlVet.
# Uso: wsl -d Ubuntu-24.04 -u root -- sh /mnt/c/xampp/htdocs/controlvet/infra/nagios/estado.sh
awk '
  /\{$/                      { tipo = $1; host = ""; svc = "-"; est = ""; out = "" }
  /^\thost_name=/            { host = substr($0, index($0, "=") + 1) }
  /^\tservice_description=/  { svc = substr($0, index($0, "=") + 1) }
  /^\tcurrent_state=/        { est = substr($0, index($0, "=") + 1) }
  /^\tplugin_output=/        { out = substr($0, index($0, "=") + 1) }
  /^\t\}/ {
    if ((tipo == "hoststatus" || tipo == "servicestatus") && host != "localhost" && host != "") {
      split("OK WARNING CRITICAL UNKNOWN", n, " ")
      if (tipo == "hoststatus") { split("UP DOWN UNREACHABLE", n, " ") }
      printf "%-19s %-26s %-9s %s\n", host, svc, n[est + 1], substr(out, 1, 80)
    }
  }' /var/lib/nagios4/status.dat
