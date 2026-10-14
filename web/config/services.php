<?php
/**
 * Ruta: controlvet/web/config/services.php
 * Direcciones internas de los microservicios (red Docker "backend").
 * Se configuran por variables de entorno; los valores por defecto
 * corresponden a los nombres de servicio de compose.yaml.
 */

return [
    'auth'       => getenv('AUTH_URL') ?: 'http://auth',
    'pacientes'  => getenv('PACIENTES_URL') ?: 'http://pacientes',
    'citas'      => getenv('CITAS_URL') ?: 'http://citas',
    'clinica'    => getenv('CLINICA_URL') ?: 'http://clinica',
    'inventario' => getenv('INVENTARIO_URL') ?: 'http://inventario',
    'documentos' => getenv('DOCUMENTOS_URL') ?: 'http://documentos',
];
