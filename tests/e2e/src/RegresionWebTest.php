<?php

declare(strict_types=1);

namespace ControlVet\E2E;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regresión funcional del frontend: reproduce la navegación de v1.0.0
 * (login por formulario, menú por rol, pantallas y permisos) sobre la nueva
 * arquitectura, para demostrar que se conservan las funcionalidades.
 */
final class RegresionWebTest extends TestCase
{
    /** @var array<string,string> rol => cookie de sesión */
    private static array $sesiones = [];

    private static function web(string $query, ?string $cookie = null, ?string $form = null): array
    {
        return Http::request($form === null ? 'GET' : 'POST', Http::url('web') . '/index.php?' . $query, null, null, [], $form, $cookie);
    }

    private static function cookieDe(array $respuesta): ?string
    {
        foreach ($respuesta['headers'] as $h) {
            if (preg_match('/^Set-Cookie:\s*(PHPSESSID=[^;]+)/i', $h, $m)) {
                return $m[1];
            }
        }
        return null;
    }

    private static function csrfDe(string $html): string
    {
        preg_match('/name="_csrf" value="([a-f0-9]{64})"/', $html, $m);
        return $m[1] ?? '';
    }

    private static function sesion(string $rol): string
    {
        if (isset(self::$sesiones[$rol])) {
            return self::$sesiones[$rol];
        }
        $login = self::web('route=login');
        $cookie = self::cookieDe($login);
        [$email, $pass] = Http::USUARIOS[$rol];

        $r = self::web('route=login.procesar', $cookie, http_build_query(['_csrf' => self::csrfDe($login['body']), 'email' => $email, 'password' => $pass]));
        self::assertSame(302, $r['status'], "Login web de $rol");
        self::assertContains('Location: index.php?route=dashboard', $r['headers']);

        return self::$sesiones[$rol] = self::cookieDe($r) ?? $cookie;
    }

    public static function pantallas(): array
    {
        return [
            // rol, ruta, estado, texto esperado
            ['administrador', 'dashboard', 200, 'Panel de Administración'],
            ['administrador', 'usuarios', 200, 'admin@controlvet.com'],
            ['administrador', 'inventario', 200, 'Vacuna polivalente canina'],
            ['administrador', 'documentos', 200, 'Documentos'],
            ['recepcionista', 'dashboard', 200, 'Rocky'],
            ['recepcionista', 'propietarios', 200, '1098765432'],
            ['recepcionista', 'mascotas', 200, 'Michi'],
            ['recepcionista', 'citas', 200, 'Luna'],
            ['recepcionista', 'citas.crear', 200, 'Nueva cita'],
            ['recepcionista', 'propietarios.detalle&id=1', 200, 'Carlos'],
            ['recepcionista', 'usuarios', 403, ''],
            ['recepcionista', 'inventario', 403, ''],
            ['veterinario', 'dashboard', 200, 'Dr. Jorge'],
            ['veterinario', 'mascotas.detalle&id=4', 200, 'Displasia de cadera grado I, estable'],
            ['veterinario', 'historias.detalle&id=1', 200, 'Condroitina + Glucosamina'],
            ['veterinario', 'historias.crear&mascota_id=1', 200, 'Rocky'],
            ['veterinario', 'inventario', 403, ''],
            ['cliente', 'dashboard', 200, 'Michi'],
            ['cliente', 'citas', 200, 'Rocky'],
            ['cliente', 'mascotas.detalle&id=1', 200, 'Rocky'],
            ['cliente', 'mascotas.detalle&id=3', 403, ''],
            ['cliente', 'historias.detalle&id=1', 403, ''],
            ['cliente', 'propietarios', 403, ''],
        ];
    }

    #[DataProvider('pantallas')]
    public function testPantallaPorRol(string $rol, string $ruta, int $estado, string $texto): void
    {
        $r = self::web('route=' . $ruta, self::sesion($rol));

        self::assertSame($estado, $r['status'], "$rol -> $ruta");
        if ($texto !== '') {
            self::assertStringContainsString(htmlspecialchars($texto, ENT_QUOTES), $r['body']);
        }
    }

    public function testRutaProtegidaSinSesionRedirigeAlLogin(): void
    {
        $r = self::web('route=dashboard');
        self::assertSame(302, $r['status']);
        self::assertContains('Location: index.php?route=login', $r['headers']);
    }

    public function testLoginConContrasenaIncorrectaMuestraMensaje(): void
    {
        $login = self::web('route=login');
        $r = self::web('route=login.procesar', self::cookieDe($login), http_build_query([
            '_csrf' => self::csrfDe($login['body']), 'email' => 'admin@controlvet.com', 'password' => 'incorrecta',
        ]));
        self::assertSame(200, $r['status']);
        self::assertStringContainsString('Correo o contraseña incorrectos.', $r['body']);
    }

    public function testAccionSinTokenCsrfEsRechazada(): void
    {
        $r = self::web('route=usuarios.desactivar&id=2', self::sesion('administrador'));
        self::assertSame(403, $r['status']);

        $usuarios = Http::api('GET', 'auth', '/usuarios/2', Http::token('administrador'));
        self::assertSame('activo', $usuarios['json']['estado'], 'La acción no debe ejecutarse sin token CSRF');
    }

    public function testComprobantePdfDesdeElFrontend(): void
    {
        $r = self::web('route=citas.pdf&id=1', self::sesion('cliente'));
        self::assertSame(200, $r['status']);
        self::assertContains('Content-Type: application/pdf', $r['headers']);
    }

    public function testCrearPropietarioDesdeFormularioConErroresDelServicio(): void
    {
        $cookie = self::sesion('recepcionista');
        $form = self::web('route=propietarios.crear', $cookie);
        $r = self::web('route=propietarios.guardar', $cookie, http_build_query([
            '_csrf' => self::csrfDe($form['body']), 'nombre' => 'Duplicado', 'apellido' => 'X', 'documento' => '1098765432', 'telefono' => '1',
        ]));
        self::assertSame(200, $r['status']);
        self::assertStringContainsString('Ya existe un propietario registrado con este documento.', $r['body']);
    }
}
