<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class InstitutionalIdentityRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function get(): array
    {
        $identity = $this->db->query('SELECT * FROM institutional_identity WHERE id = 1')->fetch();

        $defaults = [
            'id' => 1,
            'system_name' => 'Sistema de Bienes Públicos',
            'organization_name' => 'Bienes Públicos',
            'acronym' => 'DEM',
            'brand_mode' => 'initials',
            'tax_id' => null,
            'address' => 'Dirección completa del organismo',
            'phone' => '+58 000 0000000',
            'email' => 'contacto@organismo.gob',
            'website' => 'https://www.organismo.gob',
            'logo_data_uri' => null,
            'updated_by_user_id' => null,
        ];

        $current = $identity ?: $defaults;

        // Si el cliente tiene una cookie persistente de identidad configurada en su navegador, la aplicamos
        if (!empty($_COOKIE['bp_identity'])) {
            $raw = is_string($_COOKIE['bp_identity']) ? rawurldecode($_COOKIE['bp_identity']) : '';
            $cookieData = json_decode($raw, true);
            if (is_array($cookieData) && !empty($cookieData['acronym'])) {
                foreach (['system_name', 'organization_name', 'acronym', 'brand_mode', 'tax_id', 'address', 'phone', 'email', 'website', 'logo_data_uri'] as $key) {
                    if (array_key_exists($key, $cookieData)) {
                        $current[$key] = $cookieData[$key];
                    }
                }
            }
        }

        return $current;
    }

    public function update(array $data, int $userId): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO institutional_identity
                (id, system_name, organization_name, acronym, brand_mode, tax_id, address, phone, email, website, logo_data_uri, updated_by_user_id)
             VALUES
                (1, :system_name, :organization_name, :acronym, :brand_mode, :tax_id, :address, :phone, :email, :website, :logo_data_uri, :user_id)
             ON CONFLICT (id) DO UPDATE SET
                system_name = EXCLUDED.system_name,
                organization_name = EXCLUDED.organization_name,
                acronym = EXCLUDED.acronym,
                brand_mode = EXCLUDED.brand_mode,
                tax_id = EXCLUDED.tax_id,
                address = EXCLUDED.address,
                phone = EXCLUDED.phone,
                email = EXCLUDED.email,
                website = EXCLUDED.website,
                logo_data_uri = EXCLUDED.logo_data_uri,
                updated_by_user_id = EXCLUDED.updated_by_user_id'
        );
        $stmt->execute($data + ['user_id' => $userId]);

        // Escribir cookie persistente para que todas las instancias y el login la tengan siempre activa
        if (!headers_sent()) {
            setcookie('bp_identity', json_encode($data), [
                'expires' => time() + (365 * 24 * 60 * 60),
                'path' => '/',
                'secure' => true,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }

        return $this->get();
    }
}
