<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Tests;

use ControlVet\Kernel\Config\Env;
use ControlVet\Kernel\Http\Request;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = (string)tempnam(sys_get_temp_dir(), 'cvenv');
        file_put_contents($this->file, "# comentario\nCV_PRUEBA_A=uno\nCV_PRUEBA_B=\"dos dos\"\nminuscula=x\nAPP_BASE_PATH=/controlvet/api/citas\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        putenv('CV_PRUEBA_PROCESO');
        Env::load('/no/existe');
        unset($_SERVER['REQUEST_URI']);
    }

    public function testLeeValoresDelArchivoSinTocarElEntornoDelProceso(): void
    {
        Env::load($this->file);

        self::assertSame('uno', Env::get('CV_PRUEBA_A'));
        self::assertSame('dos dos', Env::get('CV_PRUEBA_B'));
        self::assertSame('', Env::get('minuscula'));
        self::assertFalse(getenv('CV_PRUEBA_A'), 'No debe filtrarse al entorno compartido del proceso');
    }

    public function testRecurreAGetenvYAlValorPorDefecto(): void
    {
        putenv('CV_PRUEBA_PROCESO=sistema');
        Env::load('/no/existe/servicio.env');

        self::assertSame('sistema', Env::get('CV_PRUEBA_PROCESO'));
        self::assertSame('defecto', Env::get('CV_NO_DEFINIDA', 'defecto'));
    }

    public function testCadaCargaReemplazaLaConfiguracionAnterior(): void
    {
        Env::load($this->file);
        Env::load('/no/existe/servicio.env');

        self::assertSame('', Env::get('CV_PRUEBA_A'));
    }

    public function testLeeArchivoPhpQueRetornaArreglo(): void
    {
        $php = $this->file . '.php';
        file_put_contents($php, "<?php\nreturn ['DB_NAME' => 'cv_citas', 'DB_PORT' => 3307, 'malo-clave' => 'x', 'LISTA' => [1]];\n");
        try {
            Env::load($php);
            self::assertSame('cv_citas', Env::get('DB_NAME'));
            self::assertSame('3307', Env::get('DB_PORT'));
            self::assertSame('', Env::get('LISTA'));
        } finally {
            @unlink($php);
        }
    }

    public function testEliminaElPrefijoDePublicacion(): void
    {
        Env::load($this->file);
        $_SERVER['REQUEST_URI'] = '/controlvet/api/citas/citas/7?x=1';

        self::assertSame('/citas/7', Request::fromGlobals()->path);
    }
}
