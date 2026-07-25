<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['BillingAccountant']);

$encounterId = (int) ($_GET['encounter_id'] ?? 0);

$stmt = $pdo->prepare('SELECT e.*, p.name AS patient_name FROM encounters e JOIN patients p ON p.id = e.patient_id WHERE e.id = ?');
$stmt->execute([$encounterId]);
$enc = $stmt->fetch();
if (!$enc) die('Encounter not found.');

function build_line_items(PDO $pdo, array $enc): array
{
    $items = [];
    if ($enc['registration_fee'] > 0) $items[] = ['Registration / Token Fee', 'Receptionist', (float) $enc['registration_fee']];
    if ($enc['triage_fee'] > 0) $items[] = ['Triage / Consumables Fee', 'Nurse', (float) $enc['triage_fee']];
    if ($enc['consultation_fee'] > 0) $items[] = ['Consultation Fee', 'Doctor', (float) $enc['consultation_fee']];

    $labs = $pdo->prepare("SELECT test_name, cost FROM lab_test_orders WHERE encounter_id = ? AND status = 'Published'");
    $labs->execute([$enc['id']]);
    foreach ($labs->fetchAll() as $l) {
        $items[] = ['Lab Test: ' . $l['test_name'], 'LabTechnician', (float) $l['cost']];
    }

    $rx = $pdo->prepare("SELECT total_cost FROM prescriptions WHERE encounter_id = ? AND status = 'Dispensed'");
    $rx->execute([$enc['id']]);
    foreach ($rx->fetchAll() as $r) {
        if ($r['total_cost'] > 0) $items[] = ['Dispensed Medication', 'Pharmacist', (float) $r['total_cost']];
    }

    return $items;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discount = (float) $_POST['discount'];
    $insurance = (float) $_POST['insurance_covered'];
    $paymentMethod = $_POST['payment_method'];

    $lineItems = build_line_items($pdo, $enc);
    $subtotal = array_sum(array_column($lineItems, 2));
    $total = max($subtotal - $discount - $insurance, 0);

    $pdo->beginTransaction();
    $ins = $pdo->prepare(
        'INSERT INTO invoices (encounter_id, patient_id, subtotal, discount, insurance_covered, total_amount, payment_method, status, processed_by, paid_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, "Paid", ?, NOW())'
    );
    $ins->execute([$enc['id'], $enc['patient_id'], $subtotal, $discount, $insurance, $total, $paymentMethod, $user['id']]);
    $invoiceId = $pdo->lastInsertId();

    $liStmt = $pdo->prepare('INSERT INTO invoice_line_items (invoice_id, description, source_role, amount) VALUES (?, ?, ?, ?)');
    foreach ($lineItems as [$desc, $role, $amount]) {
        $liStmt->execute([$invoiceId, $desc, $role, $amount]);
    }

    $pdo->prepare('UPDATE encounters SET status="Discharged", discharged_at=NOW() WHERE id=?')->execute([$enc['id']]);
    $pdo->commit();

    log_action($user['id'], 'PROCESS_PAYMENT_DISCHARGE', "Invoice #$invoiceId, total $total");

    header('Location: /billing/index.php');
    exit;
}

$lineItems = build_line_items($pdo, $enc);
$subtotal = array_sum(array_column($lineItems, 2));

$pageTitle = 'Billing — Invoice';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><?= htmlspecialchars($enc['patient_name']) ?> — Invoice</h1>
  <p>Token <?= htmlspecialchars($enc['token_number']) ?></p>
</div>

<div class="card">
  <h3>Itemized charges</h3>
  <table>
    <thead><tr><th>Description</th><th>From</th><th>Amount</th></tr></thead>
    <tbody>
    <?php foreach ($lineItems as [$desc, $role, $amount]): ?>
      <tr><td><?= htmlspecialchars($desc) ?></td><td><?= htmlspecialchars($role) ?></td><td><?= number_format($amount, 2) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p style="text-align:right;font-weight:600;margin-top:0.75rem;">Subtotal: <?= number_format($subtotal, 2) ?></p>
</div>

<div class="card">
  <h3>Apply discount / insurance and collect payment</h3>
  <form method="post" class="form-grid">
    <div class="field"><label>Discount</label><input type="number" step="0.01" name="discount" value="0"></div>
    <div class="field"><label>Insurance covered</label><input type="number" step="0.01" name="insurance_covered" value="0"></div>
    <div class="field">
      <label>Payment method</label>
      <select name="payment_method" required>
        <option value="Cash">Cash</option>
        <option value="Card">Card</option>
        <option value="InsuranceClaim">Insurance claim</option>
      </select>
    </div>
    <div class="field" style="align-self:end;">
      <button type="submit" class="btn btn-primary">Process payment &amp; discharge</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
