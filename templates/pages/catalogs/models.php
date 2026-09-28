<?php
/** @var array $items */
/** @var array $stats */
$table = 'models';
$title = 'Modelos';
$subtitle = 'Modelos asociados a cada marca, usados en la identificación de equipos.';
$fields = [
    ['name' => 'nombre', 'label' => 'Nombre'],
];
include __DIR__ . '/_generic.php';
