<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Nurse']);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'vitals') {
    $encounterId = (int) $_POST['encounter_id'];
    $bp = trim($_POST['blood_pressure']);
    $pulse = (int) $_POST['pulse'];
    $temp = (float) $_POST['temperature'];
    $rr = (int) $_POST['respiratory_rate'];
    $weight = (float) $_POST['weight'];

    // High-risk flag: same thresholds as the API version, so a patient
    // flagged here highlights red on the Doctor's queue too.
    $systolic = $bp ? (int) explode('/', $bp)[0] : 0;
    $isHighRisk = ($systolic >= 180 || $pulse > 130 || $temp > 39.5) ? 1 : 0;

    $stmt = $pdo->prepare(
        'UPDATE encounters SET blood_pressure=?, pulse=?, temperature=?, respiratory_rate=?, weight=?,
         is_high_risk=?, vitals_recorded_by=?, vitals_recorded_at=NOW(), triage_fee=300, status="WaitingForDoctor"
         WHERE id=? AND status="WaitingForNurse"'
    );
    $stmt->execute([$bp, $pulse, $temp, $rr, $weight, $isHighRisk, $user['id'], $encounterId]);

    log_action($user['id'], 'RECORD_VITALS', "Encounter #$encounterId, high risk: $isHighRisk");
    $message = 'Vitals recorded. Patient moved to the doctor\'s queue.';
}

$queue = $pdo->query(
    "SELECT e.*, p.name AS patient_name, p.age, p.gender
     FROM encounters e JOIN patients p ON p.id = e.patient_id
     WHERE e.status = 'WaitingForNurse'
     ORDER BY e.created_at ASC"
)->fetchAll();

$pageTitle = 'Nurse — Waiting Queue';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Waiting Queue</h1>
  <p>Patients checked in by the Receptionist, awaiting triage.</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<?php if (!$queue): ?>
  <p class="empty-state">No patients waiting for triage right now.</p>
<?php endif; ?>

<?php foreach ($queue as $enc): ?>
  <div class="card">
    <h3><?= htmlspecialchars($enc['patient_name']) ?> — <?= (int) $enc['age'] ?>y, <?= htmlspecialchars($enc['gender']) ?> · Token <?= htmlspecialchars($enc['token_number']) ?></h3>
    <form method="post" class="form-grid">
      <input type="hidden" name="action" value="vitals">
      <input type="hidden" name="encounter_id" value="<?= (int) $enc['id'] ?>">
      <div class="field"><label>Blood pressure (e.g. 120/80)</label><input type="text" name="blood_pressure" required></div>
      <div class="field"><label>Pulse (bpm)</label><input type="number" name="pulse" required></div>
      <div class="field"><label>Temperature (°C)</label><input type="number" step="0.1" name="temperature" required></div>
      <div class="field"><label>Respiratory rate</label><input type="number" name="respiratory_rate" required></div>
      <div class="field"><label>Weight (kg)</label><input type="number" step="0.1" name="weight" required></div>
      <div class="field" style="align-self:end;">
        <button type="submit" class="btn btn-primary">Save vitals</button>
      </div>
    </form>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
