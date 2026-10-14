<?php

declare(strict_types=1);

namespace ControlVet\Kernel;

use Closure;
use ControlVet\Kernel\Config\Env;
use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\Response;
use ControlVet\Kernel\Http\Router;
use ControlVet\Kernel\Log\Logger;
use PDO;
use Throwable;

/**
 * Punto de arranque común de un microservicio:
 *  - registra GET /health (estado del proceso y de SU base de datos),
 *  - despacha las rutas del servicio,
 *  - traduce HttpException a JSON y oculta errores internos (500).
 */
final class App
{
    private ?PDO $pdo = null;
    private readonly Router $router;

    /** @param Closure():PDO $connect */
    public function __construct(
        public readonly string $service,
        public readonly Logger $logger,
        private readonly Closure $connect,
    ) {
        // La zona horaria es de la aplicación, no del php.ini del servidor
        // (XAMPP trae Europe/Berlin y "hoy" no coincidiría con CURDATE() de MySQL).
        date_default_timezone_set(Env::get('APP_TIMEZONE', 'America/Bogota'));
        $this->router = new Router();
        $this->router->get('/health', fn() => $this->health());
    }

    public static function version(): string
    {
        return Env::get('APP_VERSION', 'dev');
    }

    /** Conexión perezosa: solo se abre si la ruta la necesita. */
    public function db(): PDO
    {
        return $this->pdo ??= ($this->connect)();
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function handle(Request $request): Response
    {
        $started = microtime(true);
        try {
            $response = $this->router->dispatch($request);
        } catch (HttpException $e) {
            $payload = ['error' => $e->getMessage()];
            if ($e->errors()) {
                $payload['errors'] = $e->errors();
            }
            $response = Response::json($payload, $e->status());
        } catch (Throwable $e) {
            $this->logger->error('Excepción no controlada', [
                'exception' => $e::class,
                'detail' => $e->getMessage(),
                'at' => basename($e->getFile()) . ':' . $e->getLine(),
            ]);
            $response = Response::json(['error' => 'Error interno del servicio'], 500);
        }

        if ($request->path !== '/health') {
            $this->logger->info('request', [
                'method' => $request->method,
                'path' => $request->path,
                'status' => $response->status,
                'ms' => (int)round((microtime(true) - $started) * 1000),
            ]);
        }
        return $response;
    }

    private function health(): Response
    {
        $checks = ['database' => 'ok'];
        try {
            $this->db()->query('SELECT 1')->fetchColumn();
        } catch (Throwable $e) {
            $this->pdo = null;
            $checks['database'] = 'error';
            $this->logger->error('Health check: base de datos no disponible', ['detail' => $e->getMessage()]);
        }
        $ok = !in_array('error', $checks, true);

        return Response::json([
            'status' => $ok ? 'ok' : 'error',
            'service' => $this->service,
            'version' => self::version(),
            'checks' => $checks,
            'time' => gmdate('c'),
        ], $ok ? 200 : 503);
    }
}
