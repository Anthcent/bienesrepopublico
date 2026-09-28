<?php

namespace App\Services;

use App\Core\View;
use App\Repositories\DocumentRepository;

/**
 * Genera documentos versionados a partir de una plantilla HTML imprimible.
 * La vista `templates/pages/documents/print.php` se usa como layout de
 * impresión (preview en modal + impresión/descarga vía el navegador).
 *
 * Nota técnica (ver PENDIENTES.md): para exportar a PDF real en servidor
 * se recomienda incorporar una librería como Dompdf vía Composer; mientras
 * tanto el documento es HTML versionado, previsualizable e imprimible,
 * que cumple el flujo funcional descrito en el Plan Maestro (M09).
 */
final class DocumentService
{
    private DocumentRepository $repository;

    public function __construct()
    {
        $this->repository = new DocumentRepository();
    }

    public function generateAssetIncorporation(array $asset, array $user): int
    {
        $html = View::capture('pages/documents/asset_incorporation', $this->withIdentity(['asset' => $asset, 'user' => $user, 'fecha' => date('d/m/Y H:i')]));
        return $this->repository->create([
            'tipo' => 'incorporacion',
            'entidad_tipo' => 'asset',
            'entidad_id' => $asset['id'],
            'contenido_html' => $html,
            'usuario_id' => $user['id'],
        ]);
    }

    public function generateMovement(array $asset, array $movement, array $user, string $tipo): int
    {
        $html = View::capture('pages/documents/movement', $this->withIdentity(['asset' => $asset, 'movement' => $movement, 'user' => $user, 'fecha' => date('d/m/Y H:i')]));
        return $this->repository->create([
            'tipo' => $tipo,
            'entidad_tipo' => 'movement',
            'entidad_id' => $movement['id'],
            'contenido_html' => $html,
            'usuario_id' => $user['id'],
        ]);
    }

    public function generateLoan(array $loan, array $details, array $user): int
    {
        $html = View::capture('pages/documents/loan', $this->withIdentity(['loan' => $loan, 'details' => $details, 'user' => $user, 'fecha' => date('d/m/Y H:i')]));
        return $this->repository->create([
            'tipo' => 'prestamo',
            'entidad_tipo' => 'loan',
            'entidad_id' => $loan['id'],
            'contenido_html' => $html,
            'usuario_id' => $user['id'],
        ]);
    }

    public function generateReturn(array $loan, array $details, array $user): int
    {
        $html = View::capture('pages/documents/return', $this->withIdentity(['loan' => $loan, 'details' => $details, 'user' => $user, 'fecha' => date('d/m/Y H:i')]));
        return $this->repository->create([
            'tipo' => 'devolucion',
            'entidad_tipo' => 'loan',
            'entidad_id' => $loan['id'],
            'contenido_html' => $html,
            'usuario_id' => $user['id'],
        ]);
    }

    public function generateVerificationSheet(array $campaign, array $items, array $campos, array $user): int
    {
        $html = View::capture('pages/documents/verification_sheet', $this->withIdentity([
            'campaign' => $campaign, 'items' => $items, 'campos' => $campos, 'user' => $user, 'fecha' => date('d/m/Y H:i'),
        ]));
        return $this->repository->create([
            'tipo' => 'verificacion_hoja',
            'entidad_tipo' => 'verification_campaign',
            'entidad_id' => $campaign['id'],
            'contenido_html' => $html,
            'usuario_id' => $user['id'],
        ]);
    }

    public function generateVerificationClosure(array $campaign, array $counters, array $user): int
    {
        $html = View::capture('pages/documents/verification_closure', $this->withIdentity([
            'campaign' => $campaign, 'counters' => $counters, 'user' => $user, 'fecha' => date('d/m/Y H:i'),
        ]));
        return $this->repository->create([
            'tipo' => 'verificacion_cierre',
            'entidad_tipo' => 'verification_campaign',
            'entidad_id' => $campaign['id'],
            'contenido_html' => $html,
            'usuario_id' => $user['id'],
        ]);
    }

    public function forEntity(string $entidadTipo, int $entidadId): array
    {
        return $this->repository->forEntity($entidadTipo, $entidadId);
    }

    public function find(int $id): ?array
    {
        return $this->repository->find($id);
    }

    public function recent(int $limit = 5): array
    {
        return $this->repository->recent($limit);
    }

    private function withIdentity(array $data): array
    {
        $data['identity'] = (new InstitutionalIdentityService())->current();
        return $data;
    }
}
