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

        return $identity ?: [
            'id' => 1,
            'system_name' => 'Sistema de Bienes Públicos',
            'organization_name' => 'Bienes Públicos',
            'acronym' => 'SBP',
            'brand_mode' => 'initials',
            'tax_id' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
            'logo_data_uri' => null,
            'updated_by_user_id' => null,
        ];
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

        return $this->get();
    }
}
