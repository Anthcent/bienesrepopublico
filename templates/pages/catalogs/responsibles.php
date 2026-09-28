<?php
/** @var array $items */
/** @var array $stats */
$table = 'responsibles';
$title = 'Responsables';
$subtitle = 'Personas responsables de la custodia de los bienes y elegibles como prestatarios.';
$fields = [
    ['name' => 'nombre', 'label' => 'Nombre'],
    ['name' => 'cedula', 'label' => 'Cédula'],
    ['name' => 'cargo', 'label' => 'Cargo'],
    ['name' => 'dependencia', 'label' => 'Dependencia'],
    ['name' => 'telefono', 'label' => 'Teléfono'],
    ['name' => 'email', 'label' => 'Correo', 'type' => 'email'],
];
include __DIR__ . '/_generic.php';
