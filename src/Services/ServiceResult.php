<?php

namespace App\Services;

/**
 * Resultado uniforme de operaciones de servicio: éxito/fallo + datos o
 * errores de validación. Los controladores lo traducen a JSON o a un
 * flash + redirect, sin conocer las reglas de negocio.
 */
final class ServiceResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly array $data = [],
        public readonly ?string $message = null,
        public readonly array $errors = [],
    ) {
    }

    public static function success(array $data = [], ?string $message = null): self
    {
        return new self(true, $data, $message);
    }

    public static function failure(string $message, array $errors = []): self
    {
        return new self(false, [], $message, $errors);
    }
}
