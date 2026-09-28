<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\InstitutionalIdentityRepository;
use Throwable;

final class InstitutionalIdentityService
{
    private const MAX_LOGO_BYTES = 524288;
    private static ?array $current = null;

    public function __construct(private ?InstitutionalIdentityRepository $repository = null)
    {
        $this->repository ??= new InstitutionalIdentityRepository();
    }

    public function current(): array
    {
        return self::$current ??= $this->repository->get();
    }

    public function update(array $input, int $userId): ServiceResult
    {
        $data = $this->normalize($input);
        $errors = $this->validate($data);
        if ($errors) {
            return ServiceResult::failure('Revisa los datos de identidad institucional.', $errors);
        }

        $db = Database::connection();
        $before = $this->repository->get();
        $db->beginTransaction();

        try {
            $updated = $this->repository->update($data, $userId);
            (new AuditService())->record(
                $userId,
                'institutional_identity.update',
                'institutional_identity',
                1,
                'Se actualizó la identidad institucional del sistema.',
                $this->auditSnapshot($before),
                $this->auditSnapshot($updated),
            );
            $db->commit();
            self::$current = $updated;

            return ServiceResult::success(['identity' => $updated], 'Identidad institucional actualizada.');
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }

    private function normalize(array $input): array
    {
        $nullable = static fn ($value): ?string => trim((string) $value) === '' ? null : trim((string) $value);

        return [
            'system_name' => trim((string) ($input['system_name'] ?? '')),
            'organization_name' => trim((string) ($input['organization_name'] ?? '')),
            'acronym' => $nullable($input['acronym'] ?? null),
            'brand_mode' => (string) ($input['brand_mode'] ?? 'initials'),
            'tax_id' => $nullable($input['tax_id'] ?? null),
            'address' => $nullable($input['address'] ?? null),
            'phone' => $nullable($input['phone'] ?? null),
            'email' => $nullable($input['email'] ?? null),
            'website' => $nullable($input['website'] ?? null),
            'logo_data_uri' => $nullable($input['logo_data_uri'] ?? null),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        $this->validateLength($errors, 'system_name', $data['system_name'], 3, 160, 'El nombre del sistema');
        $this->validateLength($errors, 'organization_name', $data['organization_name'], 3, 200, 'El nombre del organismo');

        if (!in_array($data['brand_mode'], ['logo', 'initials'], true)) {
            $errors['brand_mode'] = 'Selecciona si deseas mostrar el logotipo o las siglas.';
        }
        if ($data['brand_mode'] === 'initials' && $data['acronym'] === null) {
            $errors['acronym'] = 'Escribe las siglas que se mostrarán como marca.';
        }
        if ($data['brand_mode'] === 'logo' && $data['logo_data_uri'] === null) {
            $errors['logo_data_uri'] = 'Selecciona una imagen o utiliza el modo de siglas.';
        }

        foreach (['acronym' => 30, 'tax_id' => 40, 'address' => 500, 'phone' => 50, 'email' => 160, 'website' => 255] as $field => $max) {
            if ($data[$field] !== null && mb_strlen($data[$field]) > $max) {
                $errors[$field] = "No puede superar {$max} caracteres.";
            }
        }
        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo electrónico válido.';
        }
        if ($data['website'] !== null && (!filter_var($data['website'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $data['website']))) {
            $errors['website'] = 'Ingresa una URL completa que comience con http:// o https://.';
        }
        if ($data['logo_data_uri'] !== null) {
            if (!preg_match('#^data:image/(png|jpeg|webp);base64,([A-Za-z0-9+/]+={0,2})$#', $data['logo_data_uri'], $matches)) {
                $errors['logo_data_uri'] = 'El logotipo debe ser una imagen PNG, JPG o WebP válida.';
            } else {
                $decoded = base64_decode($matches[2], true);
                if ($decoded === false || strlen($decoded) > self::MAX_LOGO_BYTES) {
                    $errors['logo_data_uri'] = 'El logotipo no puede superar 512 KB.';
                }
            }
        }

        return $errors;
    }

    private function validateLength(array &$errors, string $field, string $value, int $min, int $max, string $label): void
    {
        $length = mb_strlen($value);
        if ($length < $min || $length > $max) {
            $errors[$field] = "{$label} debe tener entre {$min} y {$max} caracteres.";
        }
    }

    private function auditSnapshot(array $identity): array
    {
        $logoConfigured = !empty($identity['logo_data_uri']);
        unset($identity['logo_data_uri'], $identity['created_at'], $identity['updated_at']);
        $identity['logo_configured'] = $logoConfigured;
        return $identity;
    }
}
