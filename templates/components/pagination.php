<?php
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $baseUrl */
use App\Core\View;

$totalPages = max(1, (int) ceil($total / $perPage));
$queryBase = $_GET;
$buildUrl = function (int $p) use ($baseUrl, $queryBase) {
    $queryBase['page'] = $p;
    return $baseUrl . '?' . http_build_query($queryBase);
};
if ($totalPages <= 1) return;
?>
<div class="pagination">
  <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= View::e($buildUrl(max(1, $page - 1))) ?>">‹</a>
  <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
    <?php if ($p === $page): ?>
      <span class="current"><?= $p ?></span>
    <?php else: ?>
      <a href="<?= View::e($buildUrl($p)) ?>"><?= $p ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  <a class="<?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= View::e($buildUrl(min($totalPages, $page + 1))) ?>">›</a>
</div>
