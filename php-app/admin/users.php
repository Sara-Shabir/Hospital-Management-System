<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Admin']);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([trim($_POST['name']), trim($_POST['email']), $hash, $_POST['role']]);
        log_action($user['id'], 'ADMIN_CREATE_USER', 'New user: ' . $_POST['email']);
        $message = 'User account created.';
    }

    if ($action === 'toggle_active') {
        $targetId = (int) $_POST['user_id'];
        $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?')->execute([$targetId]);
        log_action($user['id'], 'ADMIN_TOGGLE_USER', 'User #' . $targetId);
        $message = 'User status updated.';
    }

    if ($action === 'change_role') {
        $targetId = (int) $_POST['user_id'];
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$_POST['role'], $targetId]);
        log_action($user['id'], 'ADMIN_CHANGE_ROLE', "User #$targetId -> {$_POST['role']}");
        $message = 'Role updated.';
    }
}

$users = $pdo->query('SELECT id, name, email, role, is_active, last_activity_at FROM users ORDER BY created_at DESC')->fetchAll();
$roles = ['Admin', 'Receptionist', 'Nurse', 'Doctor', 'LabTechnician', 'Pharmacist', 'BillingAccountant', 'Patient'];

$pageTitle = 'Admin — User Accounts';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>User Accounts</h1>
  <p>Create staff accounts, assign roles (RBAC), and activate/deactivate access.</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="card">
  <h3>Create a new account</h3>
  <form method="post" class="form-grid">
    <input type="hidden" name="action" value="create">
    <div class="field"><label>Name</label><input type="text" name="name" required></div>
    <div class="field"><label>Email</label><input type="email" name="email" required></div>
    <div class="field"><label>Temporary password</label><input type="text" name="password" required></div>
    <div class="field">
      <label>Role</label>
      <select name="role" required>
        <?php foreach ($roles as $r): ?><option value="<?= $r ?>"><?= $r ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field" style="align-self:end;"><button type="submit" class="btn btn-primary">Create account</button></div>
  </form>
</div>

<div class="card">
  <h3>All accounts</h3>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last activity</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <form method="post" class="inline-form">
            <input type="hidden" name="action" value="change_role">
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <select name="role" onchange="this.form.submit()">
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td><?php if ($u['is_active']): ?><span class="badge badge-success">Active</span><?php else: ?><span class="badge badge-danger">Inactive</span><?php endif; ?></td>
        <td><?= htmlspecialchars($u['last_activity_at'] ?? '—') ?></td>
        <td>
          <form method="post" class="inline-form">
            <input type="hidden" name="action" value="toggle_active">
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <button type="submit" class="btn btn-outline"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
