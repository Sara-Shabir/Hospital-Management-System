<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Receptionist']);

$message = '';

// Register a new patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $stmt = $pdo->prepare(
        'INSERT INTO patients (name, age, gender, cnic, phone, emergency_contact, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        trim($_POST['name']), (int) $_POST['age'], $_POST['gender'],
        trim($_POST['cnic']), trim($_POST['phone']), trim($_POST['emergency_contact']),
        $user['id'],
    ]);
    log_action($user['id'], 'REGISTER_PATIENT', 'Patient ID: ' . $pdo->lastInsertId());
    $message = 'Patient registered successfully.';
}

// Check in a patient: generates a token and queues them for the Nurse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkin') {
    $patientId = (int) $_POST['patient_id'];
    $token = 'T-' . substr((string) time(), -6);
    $stmt = $pdo->prepare(
        'INSERT INTO encounters (patient_id, token_number, checked_in_by, status) VALUES (?, ?, ?, "WaitingForNurse")'
    );
    $stmt->execute([$patientId, $token, $user['id']]);
    log_action($user['id'], 'CHECK_IN', "Token: $token");
    $message = "Checked in. Token number: $token";
}

$search = trim($_GET['q'] ?? '');
$patients = [];
if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT * FROM patients WHERE name LIKE ? OR cnic = ? OR phone = ? ORDER BY created_at DESC LIMIT 25'
    );
    $stmt->execute(["%$search%", $search, $search]);
    $patients = $stmt->fetchAll();
}

$pageTitle = 'Receptionist — Front Desk';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Front Desk</h1>
  <p>Search returning patients, register new ones, and check patients in.</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="card">
  <h3>Search patient (by name, CNIC, or phone)</h3>
  <form method="get" class="inline-form">
    <div class="field" style="flex:1;">
      <input type="text" name="q" placeholder="e.g. Ahmed, 35201-... or 0300..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
  </form>

  <?php if ($search !== ''): ?>
    <?php if ($patients): ?>
      <table style="margin-top:1rem;">
        <thead><tr><th>Name</th><th>Age</th><th>Gender</th><th>Phone</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($patients as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= (int) $p['age'] ?></td>
            <td><?= htmlspecialchars($p['gender']) ?></td>
            <td><?= htmlspecialchars($p['phone']) ?></td>
            <td>
              <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="checkin">
                <input type="hidden" name="patient_id" value="<?= (int) $p['id'] ?>">
                <button type="submit" class="btn btn-secondary">Check in</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="empty-state">No matching patient found. Register them below.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="card">
  <h3>Register a new patient</h3>
  <form method="post">
    <input type="hidden" name="action" value="register">
    <div class="form-grid">
      <div class="field"><label>Full name</label><input type="text" name="name" required></div>
      <div class="field"><label>Age</label><input type="number" name="age" min="0" required></div>
      <div class="field">
        <label>Gender</label>
        <select name="gender" required>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="field"><label>CNIC</label><input type="text" name="cnic"></div>
      <div class="field"><label>Phone</label><input type="text" name="phone"></div>
      <div class="field"><label>Emergency contact</label><input type="text" name="emergency_contact"></div>
    </div>
    <button type="submit" class="btn btn-primary">Register patient</button>
  </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
