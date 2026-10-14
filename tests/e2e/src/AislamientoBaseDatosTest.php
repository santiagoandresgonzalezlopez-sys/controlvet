<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Integración con la base de datos: verifica que el usuario de cada
 * microservicio accede a SU esquema y que el servidor le niega el acceso al
 * esquema de cualquier otro servicio (aislamiento real, no solo por carpetas).
 */
final class AislamientoBaseDatosTest extends TestCase
{
    private const TABLAS = [
        'auth' => 'usuarios',
        'pacientes' => 'mascotas',
        'citas' => 'citas',
        'clinica' => 'historias_clinicas',
        'inventario' => 'productos',
        'documentos' => 'documentos',
    ];

    private static function conectar(string $servicio): PDO
    {
        $pass = getenv(strtoupper($servicio) . '_DB_PASSWORD');
        if (!$pass) {
            self::markTestSkipped('Credenciales de base de datos no disponibles en este entorno.');
        }
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_PORT') ?: '3307');
        return new PDO($dsn, "{$servicio}_svc", $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public static function servicios(): array
    {
        return array_map(fn($s) => [$s], array_keys(self::TABLAS));
    }

    #[DataProvider('servicios')]
    public function testServicioLeeSoloSuEsquema(string $servicio): void
    {
        $pdo = self::conectar($servicio);

        $propio = (int)$pdo->query("SELECT COUNT(*) FROM cv_{$servicio}." . self::TABLAS[$servicio])->fetchColumn();
        self::assertGreaterThanOrEqual(0, $propio);

        foreach (self::TABLAS as $otro => $tabla) {
            if ($otro === $servicio) {
                continue;
            }
            try {
                $pdo->query("SELECT COUNT(*) FROM cv_{$otro}.{$tabla}");
                self::fail("{$servicio}_svc pudo leer cv_{$otro}.{$tabla}");
            } catch (PDOException $e) {
                self::assertContains((int)($e->errorInfo[1] ?? 0), [1142, 1044], $e->getMessage());
            }
        }
    }

    public function testUsuariosDeServicioNoPuedenAlterarEsquema(): void
    {
        $this->expectException(PDOException::class);
        self::conectar('inventario')->exec('DROP TABLE cv_inventario.productos');
    }

    public function testHistoriaClinicaSeAlmacenaConCopiaDelPropietario(): void
    {
        $row = self::conectar('clinica')->query('SELECT mascota_id, propietario_id FROM cv_clinica.historias_clinicas WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
        self::assertSame(['mascota_id' => 4, 'propietario_id' => 3], array_map('intval', $row));
    }
}
