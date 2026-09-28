<?php
/**
 * Aplica el esquema y los datos semilla de PostgreSQL de forma segura e
 * idempotente. Se ejecuta en cada arranque del contenedor (ver
 * docker-entrypoint.sh) antes de levantar el servidor HTTP: todas las
 * sentencias usan IF NOT EXISTS / ON CONFLICT, así que repetirlo en cada
 * despliegue no duplica datos ni falla si ya estaban aplicadas.
 *
 * Uso: php database/migrate.php
 */

require __DIR__ . '/../src/Support/autoload.php';

use App\Core\Database;

$schema = file_get_contents(__DIR__ . '/schema.postgres.sql');
$seed = file_get_contents(__DIR__ . '/seed.postgres.sql');

try {
    $db = Database::connection();
    $db->exec($schema);
    $db->exec($seed);
} catch (\PDOException $e) {
    fwrite(STDERR, "Fallo al aplicar el esquema/datos semilla: {$e->getMessage()}\n");
    exit(1);
}

echo "Esquema y datos semilla aplicados correctamente.\n";
