<?php
/** @var string $title */
/** @var string $message */
/** @var string $icon */
use App\Core\View;
$icon = $icon ?? '▦';
?>
<div class="empty-state">
  <div class="empty-icon"><?= View::e($icon) ?></div>
  <strong><?= View::e($title) ?></strong>
  <p><?= View::e($message) ?></p>
</div>
