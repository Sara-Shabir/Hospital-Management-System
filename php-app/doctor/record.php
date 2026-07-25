<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Doctor']);

$encounterId = (int) ($_GET['encounter_id'] ?? 0);
$message = '';
$allergyWarning = '';

$stmt = $pdo->prepare('SELECT e.*, p.* , e.id AS encounter_id FROM encounters e JOIN patients p ON p.id = e.patient_id WHERE e.id = ?');
$stmt->execute([$encounterId]);
$record = $stmt->fetch();

if (!$record) {
    die('Encounter not found.');
}

// Move into "InConsultation" and assign this doctor, the first time it's opened.
if ($record['status'] === 'WaitingForDoctor') {
    $pdo->prepare('UPDATE encounters SET status = "InConsultation", assigned_doctor = ? WHERE id = ?')
        ->execute([$user['id'], $encounterId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_note') {
        $pdo->prepare('INSERT INTO clinical_notes (encounter_id, notes, written_by) VALUES (?, ?, ?)')
            ->execute([$encounterId, trim($_POST['notes']), $user['id']]);
        $pdo->prepare('UPDATE encounters SET consultation_fee = COALESCE(consultation_fee, 1500) WHERE id = ?')
            ->execute([$encounterId]);
        $message = 'Clinical note saved.';
    }

    if ($action === 'order_lab') {
        $stmt = $pdo->prepare(
            'INSERT INTO lab_test_orders (encounter_id, patient_id, ordered_by, test_name, priority) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$encounterId, $record['patient_id'], $user['id'], trim($_POST['test_name']), $_POST['priority']]);
        $pdo->prepare('UPDATE encounters SET status = "AwaitingLab" WHERE id = ?')->execute([$encounterId]);
        log_action($user['id'], 'ORDER_LAB_TEST', 'Encounter #' . $encounterId);
        $message = 'Lab test ordered — sent to the Lab Technician\'s worklist.';
    }

    if ($action === 'prescribe') {
        $medNames = $_POST['med_name'] ?? [];
        $dosages = $_POST['med_dosage'] ?? [];
        $quantities = $_POST['med_quantity'] ?? [];
        $instructions = $_POST['med_instructions'] ?? [];

        $pStmt = $pdo->prepare('INSERT INTO prescriptions (encounter_id, patient_id, doctor_id) VALUES (?, ?, ?)');
        $pStmt->execute([$encounterId, $record['patient_id'], $user['id']]);
        $prescriptionId = $pdo->lastInsertId();

        $allergies = array_map('trim', explode(',', (string) $record['allergies']));
        $conflicts = [];

        $mStmt = $pdo->prepare(
            'INSERT INTO prescription_medicines (prescription_id, name, dosage, quantity, instructions) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($medNames as $i => $name) {
            if (trim($name) === '') continue;
            $mStmt->execute([$prescriptionId, trim($name), trim($dosages[$i]), (int) $quantities[$i], trim($instructions[$i])]);
            foreach ($allergies as $a) {
                if ($a !== '' && strcasecmp($a, trim($name)) === 0) $conflicts[] = trim($name);
            }
        }

        $pdo->prepare('UPDATE encounters SET status = "AwaitingPharmacy" WHERE id = ?')->execute([$encounterId]);
        log_action($user['id'], 'PRESCRIBE_MEDICATION', 'Prescription #' . $prescriptionId);

        $message = 'Prescription sent to the Pharmacist.';
        if ($conflicts) {
            $allergyWarning = 'Warning: patient is allergic to ' . implode(', ', $conflicts);
        }
    }

    if ($action === 'close') {
        $hasOpenRx = $pdo->prepare('SELECT COUNT(*) FROM prescriptions WHERE encounter_id = ? AND status = "Pending"');
        $hasOpenRx->execute([$encounterId]);
        $status = $hasOpenRx->fetchColumn() > 0 ? 'AwaitingPharmacy' : 'AwaitingBilling';
        $pdo->prepare('UPDATE encounters SET status = ? WHERE id = ?')->execute([$status, $encounterId]);
        log_action($user['id'], 'CLOSE_CONSULTATION', 'Encounter #' . $encounterId);
        header('Location: /doctor/index.php');
        exit;
    }
}

$notes = $pdo->prepare('SELECT * FROM clinical_notes WHERE encounter_id = ? ORDER BY created_at DESC');
$notes->execute([$encounterId]);
$notes = $notes->fetchAll();

$labHistory = $pdo->prepare('SELECT * FROM lab_test_orders WHERE patient_id = ? ORDER BY created_at DESC');
$labHistory->execute([$record['patient_id']]);
$labHistory = $labHistory->fetchAll();

$rxHistory = $pdo->prepare('SELECT * FROM prescriptions WHERE patient_id = ? ORDER BY created_at DESC');
$rxHistory->execute([$record['patient_id']]);
$rxHistory = $rxHistory->fetchAll();

$pageTitle = 'Doctor — ' . $record['name'];
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><?= htmlspecialchars($record['name']) ?></h1>
  <p><?= (int) $record['age'] ?>y, <?= htmlspecialchars($record['gender']) ?> · Token <?= htmlspecialchars($record['token_number']) ?>
     · Allergies: <?= htmlspecialchars($record['allergies'] ?: 'None recorded') ?></p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($allergyWarning): ?><div class="alert alert-error"><?= htmlspecialchars($allergyWarning) ?></div><?php endif; ?>

<div class="card">
  <h3>Vitals from Nurse</h3>
  <p>BP <?= htmlspecialchars($record['blood_pressure']) ?> · Pulse <?= (int) $record['pulse'] ?> ·
     Temp <?= htmlspecialchars($record['temperature']) ?>°C · RR <?= (int) $record['respiratory_rate'] ?> ·
     Weight <?= htmlspecialchars($record['weight']) ?>kg
     <?php if ($record['is_high_risk']): ?><span class="badge badge-danger">High risk</span><?php endif; ?>
  </p>
</div>

<div class="card">
  <h3>Clinical notes</h3>
  <?php foreach ($notes as $n): ?>
    <p style="border-bottom:1px solid #eef2f1;padding-bottom:0.5rem;"><?= nl2br(htmlspecialchars($n['notes'])) ?>
      <br><small style="color:var(--color-text-muted);"><?= htmlspecialchars($n['created_at']) ?></small></p>
  <?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="action" value="add_note">
    <div class="field"><textarea name="notes" rows="3" required placeholder="Write case notes..."></textarea></div>
    <button type="submit" class="btn btn-primary">Save note</button>
  </form>
</div>

<div class="card">
  <h3>Order a lab test</h3>
  <form method="post" class="inline-form">
    <input type="hidden" name="action" value="order_lab">
    <div class="field"><label>Test name</label><input type="text" name="test_name" required></div>
    <div class="field">
      <label>Priority</label>
      <select name="priority"><option>Routine</option><option>STAT</option></select>
    </div>
    <button type="submit" class="btn btn-secondary">Order test</button>
  </form>
  <?php if ($labHistory): ?>
    <table style="margin-top:1rem;">
      <thead><tr><th>Test</th><th>Status</th><th>Result</th></tr></thead>
      <tbody>
      <?php foreach ($labHistory as $l): ?>
        <tr><td><?= htmlspecialchars($l['test_name']) ?></td><td><span class="badge"><?= htmlspecialchars($l['status']) ?></span></td><td><?= htmlspecialchars($l['result_text'] ?: '—') ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h3>Prescribe medication</h3>
  <form method="post">
    <input type="hidden" name="action" value="prescribe">
    <?php for ($i = 0; $i < 3; $i++): ?>
      <div class="form-grid">
        <div class="field"><label>Medicine name</label><input type="text" name="med_name[]"></div>
        <div class="field"><label>Dosage</label><input type="text" name="med_dosage[]"></div>
        <div class="field"><label>Quantity</label><input type="number" name="med_quantity[]"></div>
        <div class="field"><label>Instructions</label><input type="text" name="med_instructions[]"></div>
      </div>
    <?php endfor; ?>
    <button type="submit" class="btn btn-secondary">Send prescription to pharmacy</button>
  </form>
  <?php if ($rxHistory): ?>
    <table style="margin-top:1rem;">
      <thead><tr><th>Date</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rxHistory as $rx): ?>
        <tr><td><?= htmlspecialchars($rx['created_at']) ?></td><td><span class="badge"><?= htmlspecialchars($rx['status']) ?></span></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<form method="post">
  <input type="hidden" name="action" value="close">
  <button type="submit" class="btn btn-primary">Close consultation</button>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
