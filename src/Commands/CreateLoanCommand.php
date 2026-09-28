<?php

namespace App\Commands;

use App\Core\Request;

final class CreateLoanCommand
{
    /**
     * El prestatario ya no es texto libre: se elige (o se da de alta rápida)
     * del catálogo Responsables. `prestatario_nombre/cargo/dependencia` los
     * arma `LoanService::create()` a partir del registro encontrado — no se
     * confía en lo que mande el cliente para esos campos, solo en el ID.
     *
     * @param int[] $assetIds
     */
    public function __construct(
        public readonly array $assetIds,
        public readonly int $responsibleId,
        public readonly string $fechaPrestamo,
        public readonly string $fechaVencimiento,
        public readonly ?string $motivo,
        public readonly ?string $observaciones,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $assetIds = $request->input('asset_ids', []);
        if (is_string($assetIds)) {
            $assetIds = array_filter(explode(',', $assetIds));
        }

        return new self(
            assetIds: array_map('intval', $assetIds),
            responsibleId: (int) $request->input('responsible_id'),
            fechaPrestamo: (string) $request->input('fecha_prestamo', date('Y-m-d')),
            fechaVencimiento: (string) $request->input('fecha_vencimiento'),
            motivo: $request->input('motivo') ?: null,
            observaciones: $request->input('observaciones') ?: null,
        );
    }
}
