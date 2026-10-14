<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Http;

/**
 * Enrutador REST mínimo: método + patrón con parámetros {nombre}.
 * Los parámetros solo aceptan dígitos, lo que bloquea rutas ambiguas
 * y entradas no numéricas en identificadores.
 */
final class Router
{
    /** @var array<int,array{0:string,1:string,2:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable $handler): void { $this->add('POST', $pattern, $handler); }
    public function put(string $pattern, callable $handler): void { $this->add('PUT', $pattern, $handler); }
    public function patch(string $pattern, callable $handler): void { $this->add('PATCH', $pattern, $handler); }

    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>\d+)', rtrim($pattern, '/') ?: '/') . '$#';
        $this->routes[] = [$method, $regex, $handler];
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;
        foreach ($this->routes as [$method, $regex, $handler]) {
            if (!preg_match($regex, $request->path, $m)) {
                continue;
            }
            $pathMatched = true;
            if ($method !== $request->method) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return $handler($request->withParams($params));
        }

        if ($pathMatched) {
            throw new HttpException(405, 'Método no permitido');
        }
        throw HttpException::notFound('Ruta no encontrada');
    }
}
