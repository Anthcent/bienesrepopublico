<?php

namespace App\Core;

final class Router
{
    /** @var array<string, array<int, array{pattern:string, regex:string, params:array, handler:callable, middleware:array}>> */
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, callable $handler, array $middleware): void
    {
        $paramNames = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_]+)\}#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[$method][] = [
            'pattern' => $path,
            'regex' => '#^' . $regex . '$#',
            'params' => $paramNames,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        $routes = $this->routes[$request->method] ?? [];

        foreach ($routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches)) {
                array_shift($matches);
                $request->params = array_combine($route['params'], $matches) ?: [];

                foreach ($route['middleware'] as $middleware) {
                    $result = $middleware($request);
                    if ($result === false) {
                        return;
                    }
                }

                if (in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                    if (!\App\Middleware\CsrfMiddleware::handle($request)) {
                        return;
                    }
                }

                call_user_func($route['handler'], $request);
                return;
            }
        }

        $this->notFound($request);
    }

    private function notFound(Request $request): void
    {
        if ($request->wantsJson()) {
            Response::error(404, 'Recurso no encontrado.');
        }
        http_response_code(404);
        View::render('errors/404');
    }
}
