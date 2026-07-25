<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Receptionist']);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'book') {
        $stmt = $pdo->prepare(
            'INSERT INTO appointments (patient_id, doctor_id, scheduled_at, booked_by) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([(int) $_POST['patient_id'], (int) $_POST['doctor_id'], $_POST['scheduled_at'], $user['id']]);
        $message = 'Appointment booked.';
    } elseif (($_POST['action'] ?? '') === 'update_status') {
        $stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
        $stmt->execute([$_POST['status'], (int) $_POST['appointment_id']]);
        $message = 'Appointment updated.';
    }
}

$patients = $pdo->query('SELECT id, name FROM patients ORDER BY name')->fetchAll();
$doctors = $pdo->query("SELECT id, name FROM users WHERE role = 'Doctor' ORDER BY name")->fetchAll();
$appointments = $pdo->query(
    'SELECT a.*, p.name AS patient_name, d.name AS doctor_name
     FROM appointments a
     JOIN patients p ON p.id = a.patient_id
     JOIN users d ON d.id = a.doctor_id
     ORDER BY a.scheduled_at DESC LIMIT 50'
)->fetchAll();

$pageTitle = 'Receptionist — Appointments';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Appointments</h1>
  <p>Book, reschedule, or cancel consultation slots.</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="card">
  <h3>Book a new appointment</h3>
  <form method="post" class="form-grid">
    <input type="hidden" name="action" value="book">
    <div class="field">
      <label>Patient</label>
      <select name="patient_id" required>
        <?php foreach ($patients as $p): ?>
          <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Doctor</label>
      <select name="doctor_id" required>
        <?php foreach ($doctors as $d): ?>
          <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Date &amp; time</label>
      <input type="datetime-local" name="scheduled_at" required>
    </div>
    <div class="field" style="align-self:end;">
      <button type="submit" class="btn btn-primary">Book</button>
    </div>
  </form>
</div>

<div class="card">
  <h3>Upcoming &amp; recent appointments</h3>
  <?php if ($appointments): ?>
    <table>
      <thead><tr><th>Patient</th><th>Doctor</th><th>When</th><th>Status</th><th>Update</th></tr></thead>
      <tbody>
      <?php foreach ($appointments as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['patient_name']) ?></td>
          <td><?= htmlspecialchars($a['doctor_name']) ?></td>
          <td><?= htmlspecialchars($a['scheduled_at']) ?></td>
          <td><span class="badge"><?= htmlspecialchars($a['status']) ?></span></td>
          <td>
            <form method="post" class="inline-form">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>">
              <select name="status">
                <?php foreach (['Booked', 'Rescheduled', 'Cancelled', 'CheckedIn', 'Completed'] as $s): ?>
                  <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-outline">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="empty-state">No appointments yet.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
