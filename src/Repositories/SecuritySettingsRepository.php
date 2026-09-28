<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class SecuritySettingsRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function get(): array
    {
        $settings = $this->db->query('SELECT * FROM security_settings WHERE id = 1')->fetch();

        return $settings ?: [
            'id' => 1,
            'idle_timeout_minutes' => 30,
            'absolute_session_hours' => 8,
            'password_min_length' => 12,
            'max_login_attempts' => 5,
            'lockout_minutes' => 15,
            'backup_warning_hours' => 24,
            'updated_by_user_id' => null,
        ];
    }

    public function update(array $data, int $userId): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO security_settings
                (id, idle_timeout_minutes, absolute_session_hours, password_min_length,
                 max_login_attempts, lockout_minutes, backup_warning_hours, updated_by_user_id)
             VALUES
                (1, :idle_timeout_minutes, :absolute_session_hours, :password_min_length,
                 :max_login_attempts, :lockout_minutes, :backup_warning_hours, :user_id)
             ON CONFLICT (id) DO UPDATE SET
                idle_timeout_minutes = EXCLUDED.idle_timeout_minutes,
                absolute_session_hours = EXCLUDED.absolute_session_hours,
                password_min_length = EXCLUDED.password_min_length,
                max_login_attempts = EXCLUDED.max_login_attempts,
                lockout_minutes = EXCLUDED.lockout_minutes,
                backup_warning_hours = EXCLUDED.backup_warning_hours,
                updated_by_user_id = EXCLUDED.updated_by_user_id'
        );
        $stmt->execute($data + ['user_id' => $userId]);
        return $this->get();
    }
}
