<?php
/** @var ?string $permission */
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acceso restringido — Sistema de Bienes Públicos</title>
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <div class="auth-shell">
    <div class="auth-card" style="text-align:center">
      <h1>Acceso restringido</h1>
      <p>Tu rol no tiene el permiso <?= $permission ? '<strong>' . View::e($permission) . '</strong>' : '' ?> necesario para ver esta sección.</p>
      <a class="btn btn-primary wide" href="/dashboard">Volver al panel principal</a>
    </div>
  </div>
</body>
</html>
