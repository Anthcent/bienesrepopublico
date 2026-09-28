<?php

namespace App\Core;

/**
 * Renderizador simple de plantillas PHP con soporte de layout y
 * componentes reutilizables (evita templates gigantes monolíticos).
 */
final class View
{
    private static string $basePath = __DIR__ . '/../../templates';

    public static function render(string $page, array $data = [], ?string $layout = 'layout/app'): void
    {
        if (str_starts_with($page, 'errors/')) {
            $layout = null;
        }

        $content = self::capture("pages/{$page}", $data);

        if ($layout === null) {
            echo $content;
            return;
        }

        $data['content'] = $content;
        echo self::capture($layout, $data);
    }

    public static function component(string $name, array $data = []): void
    {
        echo self::capture("components/{$name}", $data);
    }

    /**
     * Los nombres de parámetro/variable local usan el prefijo `__view` para
     * no chocar con `extract($data, EXTR_SKIP)`: si `$data` trae una clave
     * llamada igual que una variable local ya existente (p. ej. `template`
     * o `file`), EXTR_SKIP la descarta en silencio — pasó de verdad con
     * `View::render('reports/preview', ['template' => ...])`, que nunca
     * llegaba a la vista porque este método ya tenía su propio `$template`.
     */
    public static function capture(string $__viewTemplate, array $__viewData = []): string
    {
        $__viewFile = self::$basePath . '/' . $__viewTemplate . '.php';
        if (!is_file($__viewFile)) {
            return "<!-- Plantilla no encontrada: {$__viewTemplate} -->";
        }

        extract($__viewData, EXTR_SKIP);
        ob_start();
        include $__viewFile;
        return ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
