<?php
/**
 * Fuente única de las credenciales de prueba: las usa tanto
 * database/bootstrap_demo_users.php (para crearlas/actualizarlas en el
 * arranque del contenedor) como templates/pages/auth/login.php (para
 * mostrarlas en la caja "Credenciales de prueba" del login), así que
 * nunca quedan desincronizadas entre sí.
 *
 * Solo pensado para el entorno de pruebas del despliegue. Quitar este
 * archivo, database/bootstrap_demo_users.php y el bloque "auth-devbox"
 * de login.php antes de pasar a producción real.
 */
return [
    [
        'role' => 'ADMIN',
        'nombre' => 'Administrador de prueba',
        'email' => 'admin@bienespublicos.local',
        'password' => 'Admin123!Bien',
        'label' => 'Administrador',
    ],
    [
        'role' => 'OPERATIVO',
        'nombre' => 'Operativo de prueba',
        'email' => 'operativo@bienespublicos.local',
        'password' => 'Operativo123!Bien',
        'label' => 'Usuario operativo',
    ],
    [
        'role' => 'CONSULTA',
        'nombre' => 'Consulta de prueba',
        'email' => 'consulta@bienespublicos.local',
        'password' => 'Consulta123!Bien',
        'label' => 'Usuario de consulta',
    ],
];
