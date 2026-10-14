// =====================================================================
// ControlVet · Pipeline declarativo (RELEASE) para Jenkins en Windows.
// Sin contenedores: los microservicios se ejecutan en XAMPP y la
// operación se automatiza con Ansible en WSL2.
//
//  Checkout → Validar → Dependencias (Composer) → Pruebas unitarias (PHPUnit)
//  → Empaquetado (artefacto versionado) → Staging + integración/regresión
//  → Release (promoción del artefacto) → Deploy a producción (Ansible)
//
// Cualquier etapa obligatoria que falle detiene la publicación.
// GitHub solo aporta el código (consulta por polling, sin GitHub Actions).
// =====================================================================

def COMPONENTES = ['libs/kernel', 'services/auth', 'services/pacientes', 'services/citas',
                   'services/clinica', 'services/inventario', 'services/documentos', 'tests/e2e']

// Ejecuta un playbook de Ansible en WSL (Ansible no corre nativo en Windows).
def ansible(String playbook, String extra) {
    // wsl.exe elimina las barras invertidas de los argumentos: se pasa la ruta con "/".
    def ws = powershell(returnStdout: true, script: "wsl -d ${env.CV_WSL_DISTRO} -u root -- wslpath -a '${env.WORKSPACE.replace('\\', '/')}'").trim()
    powershell """
        wsl -d ${env.CV_WSL_DISTRO} -u root -- bash -lc "cd '${ws}/infra/ansible' && ANSIBLE_FORCE_COLOR=0 ansible-playbook -i inventarios/local.yml playbooks/${playbook} ${extra}"
        if (\$LASTEXITCODE -ne 0) { throw 'ansible-playbook ${playbook} falló' }
    """
}

pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
        timeout(time: 45, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '30', artifactNumToKeepStr: '10'))
    }

    parameters {
        booleanParam(name: 'DESPLEGAR_PRODUCCION', defaultValue: true,
                     description: 'Promover y desplegar en producción (/cv) si staging pasa todas las pruebas.')
    }

    environment {
        PHP = 'C:\\xampp\\php\\php.exe'
        CV_WSL_DISTRO = 'Ubuntu-24.04'
        CV_ARTEFACTOS = 'C:\\controlvet\\artefactos'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
                script {
                    env.GIT_SHA = powershell(returnStdout: true, script: 'git rev-parse --short HEAD').trim()
                    env.CV_VERSION = "${readFile('VERSION').trim()}.${env.BUILD_NUMBER}"
                    currentBuild.displayName = "#${env.BUILD_NUMBER} · v${env.CV_VERSION}"
                    currentBuild.description = "commit ${env.GIT_SHA}"
                }
                powershell 'New-Item -ItemType Directory -Force reports, dist | Out-Null'
            }
        }

        stage('Validar') {
            steps {
                // Sintaxis PHP, composer.json válidos y control de secretos versionados.
                powershell '.\\infra\\ci\\validar.ps1 -Php $env:PHP -Reporte reports\\php-lint.txt'
            }
        }

        stage('Dependencias (Composer)') {
            steps {
                script {
                    for (c in COMPONENTES) {
                        powershell "composer install --no-progress --no-interaction --prefer-dist -d ${c}; if (\$LASTEXITCODE -ne 0) { throw 'composer install falló en ${c}' }"
                    }
                }
            }
        }

        stage('Pruebas unitarias (PHPUnit)') {
            steps {
                script {
                    for (c in COMPONENTES.findAll { it != 'tests/e2e' }) {
                        def nombre = c.tokenize('/').last()
                        powershell """
                            Push-Location ${c}
                            & \$env:PHP vendor/bin/phpunit --log-junit '${env.WORKSPACE}\\reports\\junit-unit-${nombre}.xml'
                            \$codigo = \$LASTEXITCODE
                            Pop-Location
                            if (\$codigo -ne 0) { throw 'Pruebas unitarias fallidas en ${c}' }
                        """
                    }
                }
            }
            post {
                always { junit testResults: 'reports/junit-unit-*.xml', allowEmptyResults: false }
            }
        }

        stage('Empaquetado (artefacto)') {
            steps {
                powershell '.\\infra\\ci\\empaquetar.ps1 -Version $env:CV_VERSION -Salida dist'
                // Se publica como CANDIDATO: aún no está promovido a producción.
                powershell '''
                    $destino = Join-Path $env:CV_ARTEFACTOS $env:CV_VERSION
                    New-Item -ItemType Directory -Force $destino | Out-Null
                    Copy-Item "dist\\controlvet-$env:CV_VERSION.zip*" $destino -Force
                    Set-Content (Join-Path $destino 'ESTADO') 'candidato' -Encoding ascii
                    "Candidato publicado en $destino"
                '''
            }
        }

        stage('Staging + integración y regresión') {
            steps {
                script { ansible('deploy.yml', "-e cv_entorno=staging -e cv_version=${env.CV_VERSION}") }
                powershell '''
                    .\\tests\\e2e\\ejecutar.ps1 -BaseUrl http://localhost/cv-staging -Raiz "C:\\controlvet\\releases\\staging\\$env:CV_VERSION" -Junit "$env:WORKSPACE\\reports\\junit-e2e.xml" |
                        Tee-Object reports\\e2e.txt
                    if ($LASTEXITCODE -ne 0) { throw 'Pruebas de integración/regresión fallidas en staging' }
                '''
            }
            post {
                always { junit testResults: 'reports/junit-e2e.xml', allowEmptyResults: true }
            }
        }

        stage('Release') {
            steps {
                powershell '''
                    $ErrorActionPreference = 'Stop'
                    $destino = Join-Path $env:CV_ARTEFACTOS $env:CV_VERSION
                    $base = $env:GIT_PREVIOUS_SUCCESSFUL_COMMIT
                    $cambios = if ($base -and (git cat-file -e $base 2>$null; $LASTEXITCODE -eq 0)) {
                        git log --no-merges --pretty='- %s (%an, %h)' "$base..HEAD"
                    } else { git log --no-merges --pretty='- %s (%an, %h)' -n 20 }
                    $sha = (Get-Content "dist\\controlvet-$env:CV_VERSION.zip.sha256").Split(' ')[0]
                    $notas = @(
                        "# ControlVet v$env:CV_VERSION", '',
                        "- Fecha: $((Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ'))",
                        "- Commit: $env:GIT_SHA", "- Build Jenkins: $env:BUILD_URL",
                        "- Artefacto: controlvet-$env:CV_VERSION.zip (SHA-256 $sha)", '',
                        '## Cambios incluidos') + $cambios + @('', '## Verificación',
                        '- Pruebas unitarias y de integración/regresión en staging: superadas')
                    $notas | Set-Content "dist\\RELEASE_NOTES-$env:CV_VERSION.md" -Encoding utf8
                    Copy-Item "dist\\RELEASE_NOTES-$env:CV_VERSION.md" $destino -Force
                    # Promoción: el candidato pasó staging y queda publicado.
                    Set-Content (Join-Path $destino 'ESTADO') 'publicado' -Encoding ascii
                    Add-Content (Join-Path $env:CV_ARTEFACTOS 'registro.log') "$((Get-Date).ToUniversalTime().ToString('s'))Z PUBLICADO $env:CV_VERSION commit=$env:GIT_SHA build=$env:BUILD_NUMBER sha256=$sha"
                '''
                archiveArtifacts artifacts: 'dist/*, reports/*.txt, reports/*.xml', fingerprint: true
            }
        }

        stage('Deploy a producción (Ansible)') {
            when { expression { params.DESPLEGAR_PRODUCCION } }
            steps {
                script { ansible('deploy.yml', "-e cv_entorno=produccion -e cv_version=${env.CV_VERSION}") }
            }
        }
    }

    post {
        success { echo "Versión ${env.CV_VERSION} publicada${params.DESPLEGAR_PRODUCCION ? ' y desplegada en producción' : ''}." }
        failure { echo 'Pipeline fallido: la versión NO se promovió ni se desplegó en producción. Revise la etapa en rojo.' }
    }
}
