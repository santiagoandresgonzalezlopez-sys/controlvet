# syntax=docker/dockerfile:1
# Nagios Core de ControlVet (fase MONITOR). La configuración se valida al
# construir la imagen (nagios -v): una configuración rota no llega a ejecutarse.
FROM manios/nagios:latest
COPY infra/nagios/controlvet /opt/nagios/etc/controlvet
COPY infra/nagios/plugins/check_cv_mysql /opt/Custom-Nagios-Plugins/check_cv_mysql
RUN chmod 0755 /opt/Custom-Nagios-Plugins/check_cv_mysql \
 && echo "cfg_dir=/opt/nagios/etc/controlvet" >> /opt/nagios/etc/nagios.cfg \
 && /opt/nagios/bin/nagios -v /opt/nagios/etc/nagios.cfg
