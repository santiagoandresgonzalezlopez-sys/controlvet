<?php
/**
 * Ruta: controlvet/core/Router.php
 *
 * Router frontal simple. Cada ruta ?route=xxx se asocia a un método de
 * un ViewModel (capa que orquesta Modelo + prepara datos para la Vista,
 * siguiendo el patrón MVVM solicitado).
 */

class Router
{
    private array $routes = [];

    public function add(string $route, callable $handler): void
    {
        $this->routes[$route] = $handler;
    }

    public function dispatch(string $route): void
    {
        if (isset($this->routes[$route])) {
            call_user_func($this->routes[$route]);
            return;
        }

        http_response_code(404);
        require __DIR__ . '/../views/errors/404.php';
    }
}
