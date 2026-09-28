<?php

namespace App\Commands;

use App\Core\Request;

final class CreateAssetCommand
{
    public function __construct(
        public readonly string $numeroBien,
        public readonly ?string $serial,
        public readonly string $descripcion,
        public readonly ?int $categoryId,
        public readonly ?int $brandId,
        public readonly ?int $modelId,
        public readonly ?string $color,
        public readonly ?string $material,
        public readonly ?string $imagenUrl,
        public readonly int $locationId,
        public readonly int $responsibleId,
        public readonly int $physicalStateId,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            numeroBien: trim((string) $request->input('numero_bien')),
            serial: $request->input('serial') !== '' ? trim((string) $request->input('serial')) : null,
            descripcion: trim((string) $request->input('descripcion')),
            categoryId: $request->input('category_id') ? (int) $request->input('category_id') : null,
            brandId: $request->input('brand_id') ? (int) $request->input('brand_id') : null,
            modelId: $request->input('model_id') ? (int) $request->input('model_id') : null,
            color: $request->input('color') ?: null,
            material: $request->input('material') ?: null,
            imagenUrl: $request->input('imagen_url') ?: null,
            locationId: (int) $request->input('location_id'),
            responsibleId: (int) $request->input('responsible_id'),
            physicalStateId: (int) $request->input('physical_state_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'numero_bien' => $this->numeroBien,
            'serial' => $this->serial,
            'descripcion' => $this->descripcion,
            'category_id' => $this->categoryId,
            'brand_id' => $this->brandId,
            'model_id' => $this->modelId,
            'color' => $this->color,
            'material' => $this->material,
            'imagen_url' => $this->imagenUrl,
            'location_id' => $this->locationId,
            'responsible_id' => $this->responsibleId,
            'physical_state_id' => $this->physicalStateId,
        ];
    }
}
