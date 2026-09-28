<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\DocumentService;

final class DocumentController
{
    public function preview(Request $request): void
    {
        $id = (int) $request->param('id');
        $document = (new DocumentService())->find($id);
        if (!$document) {
            http_response_code(404);
            echo 'Documento no encontrado.';
            return;
        }

        // The application defaults to DENY, but this response is rendered in
        // the authenticated document-preview iframe on the same origin.
        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: frame-ancestors 'self'");
        header('Content-Type: text/html; charset=utf-8');
        echo $document['contenido_html'];
    }

    public function download(Request $request): void
    {
        $id = (int) $request->param('id');
        $document = (new DocumentService())->find($id);
        if (!$document) {
            http_response_code(404);
            echo 'Documento no encontrado.';
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="documento-' . $id . '.html"');
        echo $document['contenido_html'];
    }
}
