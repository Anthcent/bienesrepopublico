<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Repositories\AssetRepository;
use App\Services\InstitutionalIdentityService;

/**
 * M10b — Etiquetas de bienes. El código QR apunta a la ficha del bien
 * (`/inventario/{id}`) en vez de codificar solo el número, porque el
 * usuario no cuenta con un lector de código de barras dedicado: cualquier
 * cámara de celular ya sabe abrir un QR, así que escanear lleva directo a
 * la ficha completa en vez de un número suelto que igual habría que buscar.
 */
final class LabelController
{
    public function index(Request $request): void
    {
        $assets = new AssetRepository();
        View::render('labels/index', [
            'recentAssets' => $assets->paginate([], 1, 8)['items'],
        ]);
    }

    public function print(Request $request): void
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->input('ids', ''))));
        $assets = new AssetRepository();
        $items = array_values(array_filter(array_map(fn (int $id) => $assets->find($id), $ids)));

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $fields = array_filter(explode(',', (string) $request->input('fields', '')));

        View::render('labels/print', [
            'items' => $items,
            'baseUrl' => "{$scheme}://{$host}",
            'fields' => $fields,
            'extraText' => (string) $request->input('text', ''),
            'includeQr' => $request->input('qr', '1') !== '0',
            'logoPosition' => (string) $request->input('logoPos', 'inline'),
            'identity' => (new InstitutionalIdentityService())->current(),
        ], 'layout/print');
    }
}
