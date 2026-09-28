<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\SecuritySettingsRepository;
use Throwable;

final class SecuritySettingsService
{
    private const RANGES = [
        'idle_timeout_minutes' => [5, 120, 'La inactividad debe estar entre 5 y 120 minutos.'],
        'absolute_session_hours' => [1, 24, 'La duración absoluta debe estar entre 1 y 24 horas.'],
        'password_min_length' => [8, 64, 'La contraseña debe exigir entre 8 y 64 caracteres.'],
        'max_login_attempts' => [3, 10, 'Los intentos permitidos deben estar entre 3 y 10.'],
        'lockout_minutes' => [5, 120, 'El bloqueo debe durar entre 5 y 120 minutos.'],
        'backup_warning_hours' => [1, 168, 'La alerta de respaldo debe estar entre 1 y 168 horas.'],
    ];

    private static ?array $current = null;

    public function __construct(private ?SecuritySettingsRepository $repository = null)
    {
        $this->repository ??= new SecuritySettingsRepository();
    }

    public function current(): array
    {
        return self::$current ??= $this->normalize($this->repository->get());
    }

    public function update(array $input, int $userId): ServiceResult
    {
        $data = [];
        $errors = [];
        foreach (self::RANGES as $field => [$min, $max, $message]) {
            $value = filter_var($input[$field] ?? null, FILTER_VALIDATE_INT);
            if ($value === false || $value < $min || $value > $max) {
                $errors[$field] = $message;
            } else {
                $data[$field] = $value;
            }
        }
        if ($errors) {
            return ServiceResult::failure('Revisa las políticas de seguridad.', $errors);
        }

        $db = Database::connection();
        $before = $this->current();
        $db->beginTransaction();
        try {
            $updated = $this->repository->update($data, $userId);
            (new AuditService())->record(
                $userId,
                'security_settings.update',
                'security_settings',
                1,
                'Se actualizaron las políticas de seguridad del sistema.',
                $this->snapshot($before),
                $this->snapshot($updated),
            );
            $db->commit();
            self::$current = $this->normalize($updated);
            return ServiceResult::success(['settings' => self::$current], 'Políticas de seguridad actualizadas.');
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }

    private function normalize(array $settings): array
    {
        foreach (array_keys(self::RANGES) as $field) {
            $settings[$field] = (int) $settings[$field];
        }
        return $settings;
    }

    private function snapshot(array $settings): array
    {
        return array_intersect_key($settings, self::RANGES);
    }
}
