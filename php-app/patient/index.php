<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Patient']);

$patientStmt = $pdo->prepare('SELECT * FROM patients WHERE user_account_id = ?');
$patientStmt->execute([$user['id']]);
$patient = $patientStmt->fetch();

$message = '';
if (!$patient) {
    $pageTitle = 'Patient Portal';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-error">No patient record is linked to this account yet. Please ask the Receptionist to link your profile.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    $stmt = $pdo->prepare(
        'INSERT INTO appointments (patient_id, doctor_id, scheduled_at, booked_by, booked_via_portal) VALUES (?, ?, ?, ?, 1)'
    );
    $stmt->execute([$patient['id'], (int) $_POST['doctor_id'], $_POST['scheduled_at'], $user['id']]);
    $message = 'Appointment requested.';
}

$doctors = $pdo->query("SELECT id, name FROM users WHERE role = 'Doctor' ORDER BY name")->fetchAll();

$labReports = $pdo->prepare("SELECT * FROM lab_test_orders WHERE patient_id = ? AND status = 'Published' ORDER BY published_at DESC");
$labReports->execute([$patient['id']]);
$labReports = $labReports->fetchAll();

$prescriptions = $pdo->prepare('SELECT * FROM prescriptions WHERE patient_id = ? ORDER BY created_at DESC');
$prescriptions->execute([$patient['id']]);
$prescriptions = $prescriptions->fetchAll();

$invoices = $pdo->prepare('SELECT * FROM invoices WHERE patient_id = ? ORDER BY created_at DESC');
$invoices->execute([$patient['id']]);
$invoices = $invoices->fetchAll();

$appointments = $pdo->prepare(
    'SELECT a.*, d.name AS doctor_name FROM appointments a JOIN users d ON d.id = a.doctor_id WHERE a.patient_id = ? ORDER BY a.scheduled_at DESC'
);
$appointments->execute([$patient['id']]);
$appointments = $appointments->fetchAll();

$pageTitle = 'My Patient Portal';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Welcome, <?= htmlspecialchars($patient['name']) ?></h1>
  <p>Everything below is read-only, except booking a new appointment.</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="card">
  <h3>Book an appointment</h3>
  <form method="post" class="inline-form">
    <input type="hidden" name="action" value="book">
    <div class="field">
      <label>Doctor</label>
      <select name="doctor_id" required>
        <?php foreach ($doctors as $d): ?><option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label>Date &amp; time</label><input type="datetime-local" name="scheduled_at" required></div>
    <button type="submit" class="btn btn-primary">Request appointment</button>
  </form>

  <?php if ($appointments): ?>
    <table style="margin-top:1rem;">
      <thead><tr><th>Doctor</th><th>When</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($appointments as $a): ?>
        <tr><td><?= htmlspecialchars($a['doctor_name']) ?></td><td><?= htmlspecialchars($a['scheduled_at']) ?></td><td><span class="badge"><?= htmlspecialchars($a['status']) ?></span></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h3>Lab &amp; radiology reports</h3>
  <?php if ($labReports): ?>
    <table>
      <thead><tr><th>Test</th><th>Published</th><th>Result</th></tr></thead>
      <tbody>
      <?php foreach ($labReports as $r): ?>
        <tr><td><?= htmlspecialchars($r['test_name']) ?></td><td><?= htmlspecialchars($r['published_at']) ?></td><td><?= nl2br(htmlspecialchars($r['result_text'])) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?><p class="empty-state">No published reports yet.</p><?php endif; ?>
</div>

<div class="card">
  <h3>Prescriptions</h3>
  <?php if ($prescriptions): ?>
    <table>
      <thead><tr><th>Date</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($prescriptions as $rx): ?>
        <tr><td><?= htmlspecialchars($rx['created_at']) ?></td><td><span class="badge"><?= htmlspecialchars($rx['status']) ?></span></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?><p class="empty-state">No prescriptions yet.</p><?php endif; ?>
</div>

<div class="card">
  <h3>Billing history</h3>
  <?php if ($invoices): ?>
    <table>
      <thead><tr><th>Date</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($invoices as $inv): ?>
        <tr><td><?= htmlspecialchars($inv['created_at']) ?></td><td><?= number_format((float) $inv['total_amount'], 2) ?></td><td><span class="badge badge-success"><?= htmlspecialchars($inv['status']) ?></span></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?><p class="empty-state">No billing history yet.</p><?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
