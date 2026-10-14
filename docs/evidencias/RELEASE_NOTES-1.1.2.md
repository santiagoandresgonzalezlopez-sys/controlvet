# ControlVet v1.1.2

- Fecha: 2026-10-14T17:31:53Z
- Commit: 2a34e55
- Build Jenkins: http://localhost:8091/job/controlvet-pipeline/2/

## Cambios incluidos
- fix(ci): ruta del Dockerfile del agente PHP y contratos API por servicio (Santiago, 2a34e55)
- fix(ops): clave de monitoreo legible solo por el grupo de Nagios (0640/0750) (Santiago, 91f8427)
- feat(monitor): configuración Nagios validada en build y credencial de monitoreo vía Ansible (Santiago, c91e4f9)
- feat: migración a microservicios autónomos con Docker, pruebas y CI/CD (Santiago, 3f10437)
- chore: línea base del monolito ControlVet v1.0.0 (MVVM PHP/XAMPP) (Santiago, cf2bbc5)

## Imágenes publicadas
- localhost:5000/controlvet/ops:1.1.2 244MB
- localhost:5000/controlvet/web:1.1.2 710MB
- localhost:5000/controlvet/auth:1.1.2 710MB
- localhost:5000/controlvet/inventario:1.1.2 710MB
- localhost:5000/controlvet/clinica:1.1.2 710MB
- localhost:5000/controlvet/citas:1.1.2 710MB
- localhost:5000/controlvet/pacientes:1.1.2 710MB
- localhost:5000/controlvet/documentos:1.1.2 710MB
- localhost:5000/controlvet/db:1.1.2 460MB

## Cobertura de pruebas unitarias
    reports/coverage-auth.txt
      Lines:   42.21% (65/154)
    reports/coverage-citas.txt
      Lines:   45.86% (72/157)
    reports/coverage-clinica.txt
      Lines:   44.03% (59/134)
    reports/coverage-documentos.txt
      Lines:   80.34% (143/178)
    reports/coverage-inventario.txt
      Lines:   44.44% (44/99)
    reports/coverage-kernel.txt
      Lines:   37.86% (106/280)
    reports/coverage-pacientes.txt
      Lines:   29.67% (54/182)
