<?php

namespace App\Core;

final class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $params = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $overrideMethod = $_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
        if ($overrideMethod) {
            $this->method = strtoupper($overrideMethod);
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $config = require __DIR__ . '/../Config/config.php';
        $basePath = rtrim($config['app']['base_path'], '/');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $this->path = $uri === '' ? '/' : $uri;

        $this->query = $_GET;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $this->body = json_decode($raw, true) ?: [];
        } else {
            $this->body = $_POST;
        }
    }

    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function param(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body, $this->params);
    }

    public function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_starts_with($this->path, '/api/') || str_contains($accept, 'application/json');
    }
}
