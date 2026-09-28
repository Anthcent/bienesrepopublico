<?php

namespace App\Commands;

use App\Core\Request;

final class DecommissionCommand
{
    public function __construct(
        public readonly string $motivo,
        public readonly ?string $observaciones,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            motivo: trim((string) $request->input('motivo')),
            observaciones: $request->input('observaciones') ?: null,
        );
    }
}
