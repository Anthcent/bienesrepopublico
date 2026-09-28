<?php
/** @var string $content */
/** @var array $identity */
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Documento — <?= View::e($identity['system_name']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/tokens.css" />
  <link rel="stylesheet" href="/assets/css/app.css" />
  <link rel="stylesheet" href="/assets/css/print.css" />
</head>
<body>
  <div class="print-page">
    <?= $content ?>
    <div class="no-print print-actions">
      <button onclick="window.print()" class="btn btn-primary">Imprimir / Guardar PDF</button>
    </div>
  </div>
</body>
</html>
