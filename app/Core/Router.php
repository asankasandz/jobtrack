<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $route, callable $handler): void
    {
        $this->routes['GET'][$route] = $handler;
    }

    public function post(string $route, callable $handler): void
    {
        $this->routes['POST'][$route] = $handler;
    }

    public function dispatch(string $method, string $route): void
    {
        $handler = $this->routes[$method][$route] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '<h1>404 Not Found</h1><p>No route matched "' . htmlspecialchars($route) . '" for ' . $method . '.</p>';
            echo '<p><a href="index.php">Return home</a></p>';
            return;
        }

        $handler();
    }
}
