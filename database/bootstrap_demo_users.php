<?php
/**
 * Crea/actualiza los 3 usuarios de prueba (uno por rol: ADMIN, OPERATIVO,
 * CONSULTA) con las credenciales fijas de src/Config/demo_credentials.php
 * — las mismas que se muestran en la caja "Credenciales de prueba" del
 * login. Corre en cada arranque del contenedor (docker-entrypoint.sh),
 * después de database/migrate.php, para que el primer acceso tras un
 * despliegue no dependa de ejecutar nada a mano.
 *
 * Solo para el entorno de pruebas de este despliegue: quitar este
 * script, su llamada en docker-entrypoint.sh, src/Config/demo_credentials.php
 * y el bloque "auth-devbox" de templates/pages/auth/login.php antes de
 * pasar a producción real.
 */

require __DIR__ . '/../src/Support/autoload.php';

use App\Core\Database;

$db = Database::connection();
$credentials = require __DIR__ . '/../src/Config/demo_credentials.php';

foreach ($credentials as $cred) {
    $roleStmt = $db->prepare('SELECT id FROM roles WHERE codigo = :codigo LIMIT 1');
    $roleStmt->execute(['codigo' => $cred['role']]);
    $roleId = $roleStmt->fetchColumn();

    if (!$roleId) {
        fwrite(STDERR, "No se encontró el rol {$cred['role']}. ¿Se aplicó seed.postgres.sql?\n");
        exit(1);
    }

    $hash = password_hash($cred['password'], PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        "INSERT INTO users (role_id, nombre, cargo, email, password_hash, activo)
         VALUES (:role_id, :nombre, 'Cuenta de prueba', :email, :hash, 1)
         ON CONFLICT (email) DO UPDATE SET password_hash = EXCLUDED.password_hash, activo = 1, role_id = EXCLUDED.role_id"
    );
    $stmt->execute([
        'role_id' => $roleId,
        'nombre' => $cred['nombre'],
        'email' => $cred['email'],
        'hash' => $hash,
    ]);

    echo "Usuario de prueba listo: {$cred['email']} ({$cred['role']})\n";
}
