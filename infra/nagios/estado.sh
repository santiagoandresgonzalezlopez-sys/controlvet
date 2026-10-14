#!/bin/sh
# Resumen legible del estado actual de Nagios (hosts y servicios de ControlVet).
# Uso: docker exec controlvet-nagios sh /opt/nagios/etc/controlvet-estado.sh
awk '
  /\{$/ { tipo = $1; host = ""; svc = "-"; est = ""; out = "" }
  /^\thost_name=/            { host = substr($0, index($0, "=") + 1) }
  /^\tservice_description=/  { svc = substr($0, index($0, "=") + 1) }
  /^\tcurrent_state=/        { est = substr($0, index($0, "=") + 1) }
  /^\tplugin_output=/        { out = substr($0, index($0, "=") + 1) }
  /^\t\}/ {
    if ((tipo == "hoststatus" || tipo == "servicestatus") && host != "localhost" && host != "") {
      split("OK WARNING CRITICAL UNKNOWN", n, " ")
      if (tipo == "hoststatus") { split("UP DOWN UNREACHABLE", n, " ") }
      printf "%-11s %-26s %-9s %s\n", host, svc, n[est + 1], substr(out, 1, 90)
    }
  }' /opt/nagios/var/status.dat
