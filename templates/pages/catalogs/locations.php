<?php
/** @var array $items */
/** @var array $stats */
$table = 'locations';
$title = 'Ubicaciones';
$subtitle = 'Almacenes, oficinas y depósitos donde residen los bienes.';
$fields = [
    ['name' => 'nombre', 'label' => 'Nombre'],
    ['name' => 'piso_zona', 'label' => 'Piso / zona'],
    ['name' => 'imagen_url', 'label' => 'Imagen (URL, opcional)'],
];
include __DIR__ . '/_generic.php';
