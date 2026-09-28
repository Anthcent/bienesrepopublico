<?php
/** @var string $content */
/** @var array $actor */
use App\Core\View;
use App\Core\Auth;
use App\Core\Csrf;
use App\Services\InstitutionalIdentityService;
use App\Services\LoanService;

$actor = Auth::user();
$identity = (new InstitutionalIdentityService())->current();
$initials = mb_strtoupper(mb_substr($actor['nombre'], 0, 1) . mb_substr(strrchr($actor['nombre'], ' ') ?: '', 1, 1));
$dockVencidos = (new LoanService())->countOverdue();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= View::e($identity['system_name']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/tokens.css" />
  <link rel="stylesheet" href="/assets/css/app.css" />
  <meta name="csrf-token" content="<?= View::e(Csrf::token()) ?>">
</head>
<body>
  <div class="app-shell">
    <?php View::component('sidebar', ['actor' => $actor, 'initials' => $initials, 'identity' => $identity]); ?>

    <main class="main">
      <?php View::component('topbar', ['actor' => $actor, 'initials' => $initials]); ?>

      <section class="page">
        <?= $content ?>
      </section>
    </main>
  </div>

  <?php View::component('filter_drawer'); ?>
  <?php View::component('notification_drawer'); ?>
  <?php View::component('asset_drawer'); ?>
  <?php View::component('loan_modal'); ?>
  <?php View::component('verification_wizard'); ?>
  <?php View::component('document_preview_modal'); ?>
  <?php View::component('modal_confirm'); ?>
  <?php View::component('operation_dock', ['vencidos' => $dockVencidos]); ?>
  <?php View::component('toast_stack'); ?>

  <script type="module" src="/assets/js/app.js"></script>
</body>
</html>
