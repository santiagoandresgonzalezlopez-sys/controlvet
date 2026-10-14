// =====================================================================
// ControlVet · Pipeline declarativo (fase RELEASE, orquestado por Jenkins)
//
//  Checkout (GitHub/origen Git) → Validar → Dependencias (Composer) →
//  Pruebas unitarias (PHPUnit) → Build de imágenes (Docker) →
//  Integración y regresión (stack efímero) → Release (registro + notas) →
//  Deploy (Ansible + Docker Compose) con verificación y rollback automático.
//
// Cualquier etapa obligatoria que falle detiene la publicación.
// GitHub solo aporta el código: no se usan GitHub Actions.
// =====================================================================

def COMPONENTES = ['libs/kernel', 'services/auth', 'services/pacientes', 'services/citas',
                   'services/clinica', 'services/inventario', 'services/documentos']
def IMAGENES = ['db', 'auth', 'pacientes', 'citas', 'clinica', 'inventario', 'documentos', 'web']

pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
        timeout(time: 60, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '30', artifactNumToKeepStr: '10'))
    }

    parameters {
        booleanParam(name: 'DESPLEGAR', defaultValue: true,
                     description: 'Desplegar con Ansible en el entorno local cuando todas las etapas pasen.')
    }

    environment {
        CV_REGISTRY = "${env.CV_REGISTRY ?: 'localhost:5000'}"
        CI_PROJECT  = "cv-ci-${env.BUILD_NUMBER}"
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
                script {
                    env.GIT_SHA = sh(returnStdout: true, script: 'git rev-parse --short HEAD').trim()
                    env.CV_VERSION = "${readFile('VERSION').trim()}.${env.BUILD_NUMBER}"
                    currentBuild.displayName = "#${env.BUILD_NUMBER} · v${env.CV_VERSION}"
                    currentBuild.description = "commit ${env.GIT_SHA}"
                }
                sh 'mkdir -p reports dist'
            }
        }

        stage('Validar') {
            agent { dockerfile { filename 'php.Dockerfile'; dir 'infra/ci'; reuseNode true } }
            steps {
                sh '''
                    set -e
                    echo "== Sintaxis PHP (php -l) =="
                    find libs services web tests -name '*.php' -not -path '*/vendor/*' -print0 \
                      | xargs -0 -n1 php -l > reports/php-lint.txt
                    echo "$(wc -l < reports/php-lint.txt) archivos sin errores de sintaxis"

                    echo "== composer validate =="
                    for c in libs/kernel services/* tests/e2e; do composer validate --no-check-publish --no-interaction -d "$c"; done
                '''
                sh '''
                    set -e
                    echo "== Control de secretos versionados =="
                    if git ls-files | grep -E '(^|/)\\.env$'; then echo "ERROR: hay archivos .env versionados"; exit 1; fi
                    if git grep -nE 'BEGIN (RSA|OPENSSH|EC) PRIVATE KEY|JWT_SECRET=[A-Za-z0-9_-]{32,}' -- . ':!*.example'; then
                        echo "ERROR: posible secreto en el repositorio"; exit 1
                    fi
                    echo "Sin secretos detectados"
                '''
            }
        }

        stage('Dependencias (Composer)') {
            agent { dockerfile { filename 'php.Dockerfile'; dir 'infra/ci'; reuseNode true } }
            steps {
                script {
                    for (c in COMPONENTES) {
                        sh "composer install --no-progress --prefer-dist -d ${c}"
                    }
                }
            }
        }

        stage('Pruebas unitarias (PHPUnit)') {
            agent { dockerfile { filename 'php.Dockerfile'; dir 'infra/ci'; reuseNode true } }
            steps {
                script {
                    for (c in COMPONENTES) {
                        def nombre = c.tokenize('/').last()
                        sh """
                            cd ${c}
                            php vendor/bin/phpunit --log-junit ${env.WORKSPACE}/reports/junit-unit-${nombre}.xml \
                                --coverage-text=${env.WORKSPACE}/reports/coverage-${nombre}.txt \
                                --coverage-html ${env.WORKSPACE}/reports/coverage/${nombre}
                        """
                    }
                }
                sh 'for f in reports/coverage-*.txt; do echo "$f"; grep -m1 -E "^\\s*Lines:" "$f" || true; done | tee reports/coverage-resumen.txt'
            }
            post {
                always {
                    junit testResults: 'reports/junit-unit-*.xml', allowEmptyResults: false
                    publishHTML(target: [reportName: 'Cobertura PHPUnit', reportDir: 'reports/coverage',
                                         reportFiles: 'auth/index.html', keepAll: true, allowMissing: true, alwaysLinkToLastBuild: true])
                }
            }
        }

        stage('Build de imágenes (Docker)') {
            steps {
                sh '''
                    set -e
                    ./infra/ci/generar-env.sh "$CI_PROJECT" "$CV_VERSION" "$CV_REGISTRY" > ci.env
                    docker compose --env-file ci.env build --pull=false
                    docker build -t controlvet/e2e:$CV_VERSION tests/e2e
                    docker build -f infra/ansible/Dockerfile --build-arg APP_VERSION=$CV_VERSION \
                        -t $CV_REGISTRY/controlvet/ops:$CV_VERSION .
                    docker image ls --filter "reference=$CV_REGISTRY/controlvet/*:$CV_VERSION" \
                        --format '{{.Repository}}:{{.Tag}} {{.Size}}' | tee reports/imagenes.txt
                '''
            }
        }

        stage('Integración y regresión') {
            steps {
                sh '''
                    set -e
                    docker compose -p "$CI_PROJECT" --env-file ci.env up -d --no-build --wait --wait-timeout 180
                    # Sin traza (set +x): cargar ci.env no debe imprimir los secretos en el log.
                    set +x; set -a; . ./ci.env; set +a; set -x
                    docker run --name "$CI_PROJECT-e2e" --network "${CI_PROJECT}_backend" \
                        -e EXPECTED_VERSION="$CV_VERSION" \
                        -e AUTH_DB_PASSWORD -e PACIENTES_DB_PASSWORD -e CITAS_DB_PASSWORD \
                        -e CLINICA_DB_PASSWORD -e INVENTARIO_DB_PASSWORD -e DOCUMENTOS_DB_PASSWORD \
                        controlvet/e2e:$CV_VERSION --log-junit /e2e/reports/junit-e2e.xml --testdox \
                      | tee reports/e2e.txt
                '''
            }
            post {
                always {
                    sh '''
                        docker cp "$CI_PROJECT-e2e:/e2e/reports/junit-e2e.xml" reports/junit-e2e.xml || true
                        docker rm -f "$CI_PROJECT-e2e" || true
                        docker compose -p "$CI_PROJECT" --env-file ci.env logs --no-color > reports/ci-stack.log 2>&1 || true
                        docker compose -p "$CI_PROJECT" --env-file ci.env down -v --remove-orphans || true
                    '''
                    junit testResults: 'reports/junit-e2e.xml', allowEmptyResults: true
                }
            }
        }

        stage('Release') {
            steps {
                script {
                    for (img in IMAGENES + ['ops']) {
                        sh "docker push ${env.CV_REGISTRY}/controlvet/${img}:${env.CV_VERSION}"
                    }
                }
                sh '''
                    set -e
                    BASE="${GIT_PREVIOUS_SUCCESSFUL_COMMIT:-}"
                    {
                      echo "# ControlVet v$CV_VERSION"
                      echo
                      echo "- Fecha: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
                      echo "- Commit: $GIT_SHA"
                      echo "- Build Jenkins: $BUILD_URL"
                      echo
                      echo "## Cambios incluidos"
                      if [ -n "$BASE" ] && git cat-file -e "$BASE" 2>/dev/null; then
                        git log --no-merges --pretty='- %s (%an, %h)' "$BASE"..HEAD
                      else
                        git log --no-merges --pretty='- %s (%an, %h)' -n 20
                      fi
                      echo
                      echo "## Imágenes publicadas"
                      sed 's/^/- /' reports/imagenes.txt
                      echo
                      echo "## Cobertura de pruebas unitarias"
                      sed 's/^/    /' reports/coverage-resumen.txt
                    } > dist/RELEASE_NOTES-$CV_VERSION.md

                    printf '{"version":"%s","commit":"%s","build":%s,"registry":"%s","fecha":"%s"}\n' \
                        "$CV_VERSION" "$GIT_SHA" "$BUILD_NUMBER" "$CV_REGISTRY" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
                        > dist/release-$CV_VERSION.json
                    git archive --format=tar.gz --prefix=controlvet-$CV_VERSION/ -o dist/controlvet-$CV_VERSION-src.tar.gz HEAD
                '''
                archiveArtifacts artifacts: 'dist/*, reports/*.txt, reports/*.xml', fingerprint: true
            }
        }

        stage('Deploy (Ansible + Docker)') {
            when { expression { params.DESPLEGAR } }
            steps {
                sh '''
                    docker run --rm \
                        -v /var/run/docker.sock:/var/run/docker.sock \
                        -v controlvet_ops:/opt/controlvet \
                        $CV_REGISTRY/controlvet/ops:$CV_VERSION \
                        ansible-playbook playbooks/deploy.yml -e cv_version=$CV_VERSION -e cv_registry=$CV_REGISTRY
                '''
            }
        }
    }

    post {
        always {
            sh 'rm -f ci.env'
        }
        success {
            echo "Versión ${env.CV_VERSION} publicada${params.DESPLEGAR ? ' y desplegada' : ''}."
        }
        failure {
            echo 'Pipeline fallido: la versión NO se publicó/desplegó. Revisar la etapa en rojo y reports/.'
        }
    }
}
