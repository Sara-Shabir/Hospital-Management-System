<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['BillingAccountant']);

$encounters = $pdo->query(
    "SELECT e.*, p.name AS patient_name
     FROM encounters e JOIN patients p ON p.id = e.patient_id
     WHERE e.status = 'AwaitingBilling'
     ORDER BY e.created_at ASC"
)->fetchAll();

$pageTitle = 'Billing — Encounters to Invoice';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Encounters Ready for Checkout</h1>
  <p>Compile itemized charges from every department and process payment.</p>
</div>

<?php if (!$encounters): ?><p class="empty-state">No encounters currently awaiting billing.</p><?php endif; ?>

<?php foreach ($encounters as $enc): ?>
  <div class="card" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
      <strong><?= htmlspecialchars($enc['patient_name']) ?></strong> — Token <?= htmlspecialchars($enc['token_number']) ?>
    </div>
    <a class="btn btn-primary" href="/billing/invoice.php?encounter_id=<?= (int) $enc['id'] ?>">Open invoice</a>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
