<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\LoanSettingsRepository;
use Throwable;

final class LoanSettingsService
{
    private static ?array $current = null;

    public function __construct(private ?LoanSettingsRepository $repository = null)
    {
        $this->repository ??= new LoanSettingsRepository();
    }

    public function current(): array
    {
        return self::$current ??= $this->normalizeStored($this->repository->get());
    }

    public function update(array $input, int $userId): ServiceResult
    {
        $returnRule = null;
        if (array_key_exists('require_return_observation', $input)) {
            $rawReturnRule = $input['require_return_observation'];
            if (is_bool($rawReturnRule)) {
                $returnRule = $rawReturnRule;
            } elseif (in_array($rawReturnRule, [0, 1, '0', '1'], true)) {
                $returnRule = (bool) $rawReturnRule;
            }
        }
        $data = [
            'default_loan_days' => filter_var($input['default_loan_days'] ?? null, FILTER_VALIDATE_INT),
            'due_alert_days' => filter_var($input['due_alert_days'] ?? null, FILTER_VALIDATE_INT),
            'require_return_observation' => $returnRule,
        ];
        $errors = $this->validate($data);
        if ($errors) {
            return ServiceResult::failure('Revisa la configuración de préstamos y alertas.', $errors);
        }

        $stored = [
            'default_loan_days' => $data['default_loan_days'],
            'due_alert_days' => $data['due_alert_days'],
            'require_return_observation' => $data['require_return_observation'] ? 1 : 0,
        ];
        $db = Database::connection();
        $before = $this->current();
        $db->beginTransaction();

        try {
            $updated = $this->repository->update($stored, $userId);
            (new AuditService())->record(
                $userId,
                'loan_settings.update',
                'loan_settings',
                1,
                'Se actualizó la configuración de préstamos y alertas.',
                $this->auditSnapshot($before),
                $this->auditSnapshot($updated),
            );
            $db->commit();
            self::$current = $this->normalizeStored($updated);

            return ServiceResult::success(['settings' => self::$current], 'Configuración de préstamos actualizada.');
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['default_loan_days'] === false || $data['default_loan_days'] < 1 || $data['default_loan_days'] > 365) {
            $errors['default_loan_days'] = 'El plazo debe estar entre 1 y 365 días.';
        }
        if ($data['due_alert_days'] === false || $data['due_alert_days'] < 0 || $data['due_alert_days'] > 90) {
            $errors['due_alert_days'] = 'La anticipación debe estar entre 0 y 90 días.';
        }
        if ($data['require_return_observation'] === null) {
            $errors['require_return_observation'] = 'Selecciona una regla de devolución válida.';
        }
        return $errors;
    }

    private function normalizeStored(array $settings): array
    {
        $settings['default_loan_days'] = (int) $settings['default_loan_days'];
        $settings['due_alert_days'] = (int) $settings['due_alert_days'];
        $settings['require_return_observation'] = (bool) $settings['require_return_observation'];
        return $settings;
    }

    private function auditSnapshot(array $settings): array
    {
        return array_intersect_key($settings, array_flip([
            'default_loan_days',
            'due_alert_days',
            'require_return_observation',
        ]));
    }
}
