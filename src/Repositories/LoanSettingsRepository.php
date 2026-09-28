<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LoanSettingsRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function get(): array
    {
        $settings = $this->db->query('SELECT * FROM loan_settings WHERE id = 1')->fetch();

        return $settings ?: [
            'id' => 1,
            'default_loan_days' => 7,
            'due_alert_days' => 3,
            'require_return_observation' => 1,
            'updated_by_user_id' => null,
        ];
    }

    public function update(array $data, int $userId): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO loan_settings
                (id, default_loan_days, due_alert_days, require_return_observation, updated_by_user_id)
             VALUES (1, :default_loan_days, :due_alert_days, :require_return_observation, :user_id)
             ON CONFLICT (id) DO UPDATE SET
                default_loan_days = EXCLUDED.default_loan_days,
                due_alert_days = EXCLUDED.due_alert_days,
                require_return_observation = EXCLUDED.require_return_observation,
                updated_by_user_id = EXCLUDED.updated_by_user_id'
        );
        $stmt->execute($data + ['user_id' => $userId]);

        return $this->get();
    }
}
