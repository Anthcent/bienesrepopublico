<?php

namespace App\Commands;

use App\Core\Request;

final class ReassignCommand
{
    public function __construct(
        public readonly int $locationId,
        public readonly int $responsibleId,
        public readonly ?string $motivo,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            locationId: (int) $request->input('location_id'),
            responsibleId: (int) $request->input('responsible_id'),
            motivo: $request->input('motivo') ?: null,
        );
    }
}
