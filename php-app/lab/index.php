<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['LabTechnician']);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int) $_POST['order_id'];
    $action = $_POST['action'] ?? '';

    if ($action === 'collect') {
        $pdo->prepare('UPDATE lab_test_orders SET status="SampleCollected", sample_collected_at=NOW(), processed_by=? WHERE id=?')
            ->execute([$user['id'], $orderId]);
        $message = 'Sample collection logged.';
    }

    if ($action === 'enter_results') {
        $pdo->prepare('UPDATE lab_test_orders SET result_text=?, status="ResultEntered" WHERE id=?')
            ->execute([trim($_POST['result_text']), $orderId]);
        $message = 'Results saved.';
    }

    if ($action === 'publish') {
        $pdo->prepare('UPDATE lab_test_orders SET status="Published", published_at=NOW() WHERE id=?')->execute([$orderId]);
        $encStmt = $pdo->prepare('SELECT encounter_id FROM lab_test_orders WHERE id=?');
        $encStmt->execute([$orderId]);
        $encounterId = $encStmt->fetchColumn();
        $pdo->prepare('UPDATE encounters SET status="WaitingForDoctor" WHERE id=?')->execute([$encounterId]);
        log_action($user['id'], 'PUBLISH_LAB_RESULT', 'Order #' . $orderId);
        $message = 'Result published — returned to the doctor\'s queue and visible on the patient portal.';
    }
}

$worklist = $pdo->query(
    "SELECT o.*, p.name AS patient_name, p.age, p.gender, d.name AS ordered_by_name
     FROM lab_test_orders o
     JOIN patients p ON p.id = o.patient_id
     JOIN users d ON d.id = o.ordered_by
     WHERE o.status != 'Published'
     ORDER BY (o.priority = 'STAT') DESC, o.created_at ASC"
)->fetchAll();

$pageTitle = 'Lab Technician — Worklist';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Diagnostic Worklist</h1>
  <p>Incoming test requests. STAT (urgent) orders are shown first.</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<?php if (!$worklist): ?><p class="empty-state">No pending lab orders.</p><?php endif; ?>

<?php foreach ($worklist as $o): ?>
  <div class="card">
    <h3>
      <?= htmlspecialchars($o['test_name']) ?> —
      <?= htmlspecialchars($o['patient_name']) ?> (<?= (int) $o['age'] ?>, <?= htmlspecialchars($o['gender']) ?>)
      <?php if ($o['priority'] === 'STAT'): ?><span class="badge badge-danger">STAT</span><?php else: ?><span class="badge">Routine</span><?php endif; ?>
    </h3>
    <p style="color:var(--color-text-muted);font-size:0.85rem;">Ordered by Dr. <?= htmlspecialchars($o['ordered_by_name']) ?> · Status: <?= htmlspecialchars($o['status']) ?></p>

    <?php if ($o['status'] === 'Pending'): ?>
      <form method="post" class="inline-form">
        <input type="hidden" name="action" value="collect">
        <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
        <button type="submit" class="btn btn-secondary">Log sample collected</button>
      </form>
    <?php elseif ($o['status'] === 'SampleCollected'): ?>
      <form method="post" class="inline-form">
        <input type="hidden" name="action" value="enter_results">
        <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
        <div class="field" style="flex:1;"><textarea name="result_text" rows="2" placeholder="Result details..." required></textarea></div>
        <button type="submit" class="btn btn-secondary">Save results</button>
      </form>
    <?php elseif ($o['status'] === 'ResultEntered'): ?>
      <p><?= nl2br(htmlspecialchars($o['result_text'])) ?></p>
      <form method="post" class="inline-form">
        <input type="hidden" name="action" value="publish">
        <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
        <button type="submit" class="btn btn-primary">Publish result</button>
      </form>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
