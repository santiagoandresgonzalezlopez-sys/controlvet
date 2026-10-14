# syntax=docker/dockerfile:1
# Jenkins de ControlVet (fase RELEASE). Configuración como código (JCasC):
# usuario administrador, credenciales y el job del pipeline se crean al
# arrancar, sin pasos manuales en la interfaz.
FROM docker:27-cli AS dockercli

FROM jenkins/jenkins:lts-jdk17
USER root
RUN apt-get update \
 && apt-get install -y --no-install-recommends git curl jq zip \
 && rm -rf /var/lib/apt/lists/* \
 && git config --system --add safe.directory "*"
# Cliente Docker + plugins compose/buildx: Jenkins usa el motor Docker del
# host (socket montado) para construir imágenes y levantar entornos de prueba.
COPY --from=dockercli /usr/local/bin/docker /usr/local/bin/docker
COPY --from=dockercli /usr/local/libexec/docker/cli-plugins /usr/local/libexec/docker/cli-plugins

COPY infra/jenkins/plugins.txt /usr/share/jenkins/ref/plugins.txt
RUN jenkins-plugin-cli --plugin-file /usr/share/jenkins/ref/plugins.txt

COPY infra/jenkins/casc.yaml /var/jenkins_conf/casc.yaml
ENV CASC_JENKINS_CONFIG=/var/jenkins_conf/casc.yaml \
    JAVA_OPTS="-Djenkins.install.runSetupWizard=false -Dhudson.plugins.git.GitSCM.ALLOW_LOCAL_CHECKOUT=true"
USER jenkins
