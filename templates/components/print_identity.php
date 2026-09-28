<?php
/** @var array $identity */
use App\Core\View;

$details = array_values(array_filter([
    $identity['tax_id'] ?? null,
    $identity['email'] ?? null,
    $identity['phone'] ?? null,
]));
$showLogo = ($identity['brand_mode'] ?? 'logo') === 'logo' && !empty($identity['logo_data_uri']);
?>
<span class="print-identity">
  <?php if ($showLogo): ?>
    <img src="<?= View::e($identity['logo_data_uri']) ?>" alt="">
  <?php else: ?>
    <strong class="print-identity-initials"><?= View::e($identity['acronym'] ?: 'DEM') ?></strong>
  <?php endif; ?>
  <span>
    <strong><?= View::e($identity['organization_name']) ?></strong>
    <small><?= View::e($identity['system_name']) ?></small>
    <?php if ($details): ?><small><?= View::e(implode(' · ', $details)) ?></small><?php endif; ?>
    <?php if (!empty($identity['address'])): ?><small><?= View::e($identity['address']) ?></small><?php endif; ?>
  </span>
</span>
