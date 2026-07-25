<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Pharmacist']);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'dispense') {
    $prescriptionId = (int) $_POST['prescription_id'];

    $meds = $pdo->prepare('SELECT * FROM prescription_medicines WHERE prescription_id = ?');
    $meds->execute([$prescriptionId]);
    $meds = $meds->fetchAll();

    $pdo->beginTransaction();
    try {
        $totalCost = 0;
        foreach ($meds as $line) {
            $stock = $pdo->prepare('SELECT * FROM inventory_items WHERE name = ? FOR UPDATE');
            $stock->execute([$line['name']]);
            $stock = $stock->fetch();

            if (!$stock || $stock['quantity'] < $line['quantity']) {
                throw new RuntimeException("Insufficient stock for {$line['name']}");
            }

            $pdo->prepare('UPDATE inventory_items SET quantity = quantity - ? WHERE id = ?')
                ->execute([$line['quantity'], $stock['id']]);
            $totalCost += $stock['unit_price'] * $line['quantity'];
        }

        $pdo->prepare('UPDATE prescriptions SET status="Dispensed", dispensed_by=?, dispensed_at=NOW(), total_cost=? WHERE id=?')
            ->execute([$user['id'], $totalCost, $prescriptionId]);

        $encStmt = $pdo->prepare('SELECT encounter_id FROM prescriptions WHERE id = ?');
        $encStmt->execute([$prescriptionId]);
        $pdo->prepare('UPDATE encounters SET status="AwaitingBilling" WHERE id=?')->execute([$encStmt->fetchColumn()]);

        $pdo->commit();
        log_action($user['id'], 'DISPENSE_MEDICATION', "Prescription #$prescriptionId, cost $totalCost");
        $message = 'Medication dispensed. Charges sent to Billing.';
    } catch (RuntimeException $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$queue = $pdo->query(
    "SELECT rx.*, p.name AS patient_name, d.name AS doctor_name
     FROM prescriptions rx
     JOIN patients p ON p.id = rx.patient_id
     JOIN users d ON d.id = rx.doctor_id
     WHERE rx.status = 'Pending'
     ORDER BY rx.created_at ASC"
)->fetchAll();

$medsByRx = [];
if ($queue) {
    $ids = array_column($queue, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM prescription_medicines WHERE prescription_id IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $m) {
        $medsByRx[$m['prescription_id']][] = $m;
    }
}

$pageTitle = 'Pharmacist — Prescription Queue';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Prescription Queue</h1>
  <p>Electronic prescriptions awaiting dispensing.</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (!$queue): ?><p class="empty-state">No pending prescriptions.</p><?php endif; ?>

<?php foreach ($queue as $rx): ?>
  <div class="card">
    <h3><?= htmlspecialchars($rx['patient_name']) ?> — prescribed by Dr. <?= htmlspecialchars($rx['doctor_name']) ?></h3>
    <table>
      <thead><tr><th>Medicine</th><th>Dosage</th><th>Qty</th><th>Instructions</th></tr></thead>
      <tbody>
      <?php foreach ($medsByRx[$rx['id']] ?? [] as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['name']) ?></td>
          <td><?= htmlspecialchars($m['dosage']) ?></td>
          <td><?= (int) $m['quantity'] ?></td>
          <td><?= htmlspecialchars($m['instructions']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" style="margin-top:0.75rem;">
      <input type="hidden" name="action" value="dispense">
      <input type="hidden" name="prescription_id" value="<?= (int) $rx['id'] ?>">
      <button type="submit" class="btn btn-primary">Dispense</button>
    </form>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
