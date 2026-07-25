<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Doctor']);

$queue = $pdo->query(
    "SELECT e.*, p.name AS patient_name, p.age, p.gender
     FROM encounters e JOIN patients p ON p.id = e.patient_id
     WHERE e.status = 'WaitingForDoctor'
     ORDER BY e.is_high_risk DESC, e.created_at ASC"
)->fetchAll();

$pageTitle = 'Doctor — Consultation Queue';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Consultation Queue</h1>
  <p>Patients who have completed triage with the Nurse. High-risk vitals are highlighted.</p>
</div>

<div class="card">
  <?php if ($queue): ?>
    <table>
      <thead><tr><th>Token</th><th>Patient</th><th>Vitals</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($queue as $enc): ?>
        <tr class="<?= $enc['is_high_risk'] ? 'high-risk' : '' ?>">
          <td><?= htmlspecialchars($enc['token_number']) ?></td>
          <td><?= htmlspecialchars($enc['patient_name']) ?> (<?= (int) $enc['age'] ?>, <?= htmlspecialchars($enc['gender']) ?>)</td>
          <td>
            BP <?= htmlspecialchars($enc['blood_pressure']) ?>, Pulse <?= (int) $enc['pulse'] ?>,
            Temp <?= htmlspecialchars($enc['temperature']) ?>°C
            <?php if ($enc['is_high_risk']): ?><span class="badge badge-danger">High risk</span><?php endif; ?>
          </td>
          <td><a class="btn btn-secondary" href="/doctor/record.php?encounter_id=<?= (int) $enc['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="empty-state">No patients waiting for consultation.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
