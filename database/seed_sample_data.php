<?php
/**
 * Carga un puñado de datos de muestra (ubicaciones, responsables, marca/
 * modelo, 4 bienes en distintos estados y 1 préstamo) para poder evaluar
 * el comportamiento del sistema en este despliegue de pruebas sin partir
 * de una base completamente vacía. Deliberadamente poco: solo lo mínimo
 * para ejercitar los flujos principales (inventario, préstamo activo,
 * bien desincorporado, bien con estado físico regular/deteriorado).
 *
 * Corre en docker-entrypoint.sh después de bootstrap_demo_users.php (usa
 * el usuario ADMIN de prueba como "creador"). Es idempotente por
 * construcción: si ya existe algún bien, no hace nada — así que solo
 * siembra datos en el primer arranque, nunca sobre datos reales que ya
 * se hayan cargado a mano.
 *
 * Solo para este entorno de pruebas: quitar la llamada a este script en
 * docker-entrypoint.sh (y opcionalmente borrar los bienes/préstamo de
 * ejemplo desde la propia aplicación) antes de un despliegue real.
 */

require __DIR__ . '/../src/Support/autoload.php';

use App\Core\Database;

$db = Database::connection();

$assetCount = (int) $db->query('SELECT COUNT(*) FROM assets')->fetchColumn();
if ($assetCount > 0) {
    echo "Ya hay bienes registrados; no se cargan datos de muestra.\n";
    exit(0);
}

$adminId = $db->query("SELECT id FROM users WHERE email = 'admin@bienespublicos.local' LIMIT 1")->fetchColumn();
if (!$adminId) {
    fwrite(STDERR, "No se encontró el usuario admin de prueba; ¿corrió bootstrap_demo_users.php antes que este script?\n");
    exit(1);
}

function idByColumn(PDO $db, string $table, string $column, string $value): int
{
    $stmt = $db->prepare("SELECT id FROM {$table} WHERE {$column} = :v LIMIT 1");
    $stmt->execute(['v' => $value]);
    $id = $stmt->fetchColumn();
    if (!$id) {
        throw new RuntimeException("No se encontró {$table}.{$column} = {$value}");
    }
    return (int) $id;
}

$db->beginTransaction();
try {
    // Ubicaciones
    $loc1 = (int) $db->query("INSERT INTO locations (nombre, piso_zona) VALUES ('Almacén Central', 'Planta baja') RETURNING id")->fetchColumn();
    $loc2 = (int) $db->query("INSERT INTO locations (nombre, piso_zona) VALUES ('Oficina Administrativa', 'Piso 2') RETURNING id")->fetchColumn();

    // Responsables
    $stmt = $db->prepare(
        "INSERT INTO responsibles (nombre, cedula, cargo, dependencia, telefono, ubicacion_habitual_id)
         VALUES ('Ana Pérez', 'V-12345678', 'Encargada de almacén', 'Administración', '0414-1234567', :loc) RETURNING id"
    );
    $stmt->execute(['loc' => $loc1]);
    $resp1 = (int) $stmt->fetchColumn();

    $stmt = $db->prepare(
        "INSERT INTO responsibles (nombre, cedula, cargo, dependencia, telefono, ubicacion_habitual_id)
         VALUES ('Carlos Gómez', 'V-87654321', 'Coordinador de TI', 'Tecnología', '0424-7654321', :loc) RETURNING id"
    );
    $stmt->execute(['loc' => $loc2]);
    $resp2 = (int) $stmt->fetchColumn();

    // Marca y modelos
    $brand = (int) $db->query("INSERT INTO brands (nombre) VALUES ('HP') RETURNING id")->fetchColumn();
    $stmt = $db->prepare("INSERT INTO models (brand_id, nombre) VALUES (:b, :n) RETURNING id");
    $stmt->execute(['b' => $brand, 'n' => 'ProBook 440']);
    $model1 = (int) $stmt->fetchColumn();
    $stmt->execute(['b' => $brand, 'n' => 'LaserJet Pro']);
    $model2 = (int) $stmt->fetchColumn();

    $catLaptop = idByColumn($db, 'categories', 'nombre', 'Laptop');
    $catDesktop = idByColumn($db, 'categories', 'nombre', 'Computador de escritorio');
    $catPrinter = idByColumn($db, 'categories', 'nombre', 'Impresora');
    $catFurniture = idByColumn($db, 'categories', 'nombre', 'Mobiliario');

    $estadoBueno = idByColumn($db, 'physical_states', 'codigo', 'BUENO');
    $estadoRegular = idByColumn($db, 'physical_states', 'codigo', 'REGULAR');
    $estadoDeteriorado = idByColumn($db, 'physical_states', 'codigo', 'DETERIORADO');

    $movIncorporacion = idByColumn($db, 'movement_types', 'codigo', 'INCORPORACION');
    $movDesincorporacion = idByColumn($db, 'movement_types', 'codigo', 'DESINCORPORACION');

    $insertAsset = $db->prepare(
        "INSERT INTO assets (numero_bien, serial, descripcion, category_id, brand_id, model_id, color, material,
            location_id, responsible_id, physical_state_id, estado_administrativo, disponibilidad,
            informacion_completa, usuario_creador_id)
         VALUES (:numero, :serial, :descripcion, :category_id, :brand_id, :model_id, :color, :material,
            :location_id, :responsible_id, :physical_state_id, :estado_administrativo, :disponibilidad,
            0, :usuario_creador_id)
         RETURNING id"
    );

    // Bien 1 — laptop disponible, estado bueno.
    $insertAsset->execute([
        'numero' => 'BP-0001', 'serial' => 'SN-DEMO-0001', 'descripcion' => 'Laptop HP ProBook para oficina',
        'category_id' => $catLaptop, 'brand_id' => $brand, 'model_id' => $model1,
        'color' => 'Gris', 'material' => 'Metal',
        'location_id' => $loc1, 'responsible_id' => $resp1, 'physical_state_id' => $estadoBueno,
        'estado_administrativo' => 'ACTIVO', 'disponibilidad' => 'DISPONIBLE', 'usuario_creador_id' => $adminId,
    ]);
    $asset1 = (int) $insertAsset->fetchColumn();

    // Bien 2 — computador prestado (se le crea el préstamo más abajo).
    $insertAsset->execute([
        'numero' => 'BP-0002', 'serial' => null, 'descripcion' => 'Computador de escritorio para coordinación TI',
        'category_id' => $catDesktop, 'brand_id' => null, 'model_id' => null,
        'color' => null, 'material' => null,
        'location_id' => $loc2, 'responsible_id' => $resp2, 'physical_state_id' => $estadoBueno,
        'estado_administrativo' => 'ACTIVO', 'disponibilidad' => 'PRESTADO', 'usuario_creador_id' => $adminId,
    ]);
    $asset2 = (int) $insertAsset->fetchColumn();

    // Bien 3 — impresora con estado físico regular.
    $insertAsset->execute([
        'numero' => 'BP-0003', 'serial' => 'SN-DEMO-0003', 'descripcion' => 'Impresora multifuncional de oficina',
        'category_id' => $catPrinter, 'brand_id' => $brand, 'model_id' => $model2,
        'color' => null, 'material' => null,
        'location_id' => $loc1, 'responsible_id' => $resp1, 'physical_state_id' => $estadoRegular,
        'estado_administrativo' => 'ACTIVO', 'disponibilidad' => 'DISPONIBLE', 'usuario_creador_id' => $adminId,
    ]);
    $asset3 = (int) $insertAsset->fetchColumn();

    // Bien 4 — mobiliario desincorporado (deteriorado).
    $insertAsset->execute([
        'numero' => 'BP-0004', 'serial' => null, 'descripcion' => 'Escritorio auxiliar fuera de servicio',
        'category_id' => $catFurniture, 'brand_id' => null, 'model_id' => null,
        'color' => null, 'material' => 'Madera',
        'location_id' => $loc2, 'responsible_id' => $resp2, 'physical_state_id' => $estadoDeteriorado,
        'estado_administrativo' => 'DESINCORPORADO', 'disponibilidad' => 'DISPONIBLE', 'usuario_creador_id' => $adminId,
    ]);
    $asset4 = (int) $insertAsset->fetchColumn();

    // Movimientos — solo los dos que no se explican por sí solos en la ficha.
    $insertMovement = $db->prepare(
        "INSERT INTO asset_movements (asset_id, movement_type_id, motivo, usuario_id)
         VALUES (:asset_id, :type_id, :motivo, :usuario_id)"
    );
    $insertMovement->execute(['asset_id' => $asset1, 'type_id' => $movIncorporacion, 'motivo' => 'Incorporación inicial (dato de muestra)', 'usuario_id' => $adminId]);
    $insertMovement->execute(['asset_id' => $asset4, 'type_id' => $movDesincorporacion, 'motivo' => 'Desincorporado por deterioro (dato de muestra)', 'usuario_id' => $adminId]);

    // Préstamo activo sobre el bien 2.
    $stmt = $db->prepare(
        "INSERT INTO loans (codigo, responsible_id, prestatario_nombre_snapshot, prestatario_cargo_snapshot,
            prestatario_dependencia_snapshot, fecha_prestamo, fecha_vencimiento, motivo, estado, usuario_creador_id)
         VALUES ('PR-DEMO-0001', :responsible_id, 'Carlos Gómez', 'Coordinador de TI', 'Tecnología',
            CURRENT_DATE, CURRENT_DATE + INTERVAL '7 days', 'Préstamo de muestra para pruebas', 'ACTIVO', :usuario_id)
         RETURNING id"
    );
    $stmt->execute(['responsible_id' => $resp2, 'usuario_id' => $adminId]);
    $loanId = (int) $stmt->fetchColumn();

    $db->prepare('INSERT INTO loan_details (loan_id, asset_id, estado_salida_id) VALUES (:loan_id, :asset_id, :estado)')
        ->execute(['loan_id' => $loanId, 'asset_id' => $asset2, 'estado' => $estadoBueno]);

    $db->commit();
    echo "Datos de muestra cargados: 4 bienes (BP-0001..BP-0004) y 1 préstamo activo (PR-DEMO-0001).\n";
} catch (\Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Fallo al cargar datos de muestra: {$e->getMessage()}\n");
    exit(1);
}
