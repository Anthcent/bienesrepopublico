<?php
/**
 * Crea o actualiza el usuario administrador con un hash de contraseña
 * generado correctamente (password_hash/bcrypt). No se debe insertar un
 * hash fabricado a mano en seed.sql.
 *
 * Uso:
 *   php database/create_admin.php correo@dominio.local "ContraseñaSegura" ["Nombre completo"]
 */

require __DIR__ . '/../src/Support/autoload.php';

use App\Core\Database;

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$nombre = $argv[3] ?? 'Administrador del sistema';

if (!$email || !$password) {
    fwrite(STDERR, "Uso: php database/create_admin.php correo@dominio.local \"Contraseña\" [\"Nombre\"]\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}

$db = Database::connection();

$roleStmt = $db->prepare("SELECT id FROM roles WHERE codigo = 'ADMIN' LIMIT 1");
$roleStmt->execute();
$roleId = $roleStmt->fetchColumn();

if (!$roleId) {
    fwrite(STDERR, "No se encontró el rol ADMIN. Importa primero database/seed.sql.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare(
    "INSERT INTO users (role_id, nombre, cargo, email, password_hash, activo)
     VALUES (:role_id, :nombre, 'Administrador', :email, :hash, 1)
     ON CONFLICT (email) DO UPDATE SET password_hash = EXCLUDED.password_hash, activo = 1, role_id = EXCLUDED.role_id"
);
$stmt->execute([
    'role_id' => $roleId,
    'nombre' => $nombre,
    'email' => $email,
    'hash' => $hash,
]);

echo "Usuario administrador listo: {$email}\n";
