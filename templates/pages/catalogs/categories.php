<?php
/** @var array $items */
/** @var array $stats */
$table = 'categories';
$title = 'Categorías';
$subtitle = 'Tipos de bien utilizados en el wizard de incorporación.';
$fields = [
    ['name' => 'nombre', 'label' => 'Nombre'],
    ['name' => 'tipo_bien', 'label' => 'Tipo de bien'],
];
include __DIR__ . '/_generic.php';
