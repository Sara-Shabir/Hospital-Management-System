<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Admin']);

$logs = $pdo->query(
    'SELECT l.*, u.name AS user_name, u.role
     FROM system_logs l LEFT JOIN users u ON u.id = l.user_id
     ORDER BY l.created_at DESC LIMIT 200'
)->fetchAll();

$pageTitle = 'Admin — System Logs';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>System Logs</h1>
  <p>Login/logout events, idle auto-logouts, and administrative actions.</p>
</div>

<div class="card">
  <table>
    <thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Details</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
      <tr class="<?= $l['action'] === 'AUTO_LOGOUT_IDLE' ? 'high-risk' : '' ?>">
        <td><?= htmlspecialchars($l['created_at']) ?></td>
        <td><?= htmlspecialchars($l['user_name'] ?? 'Unknown') ?></td>
        <td><?= htmlspecialchars($l['role'] ?? '—') ?></td>
        <td><span class="badge"><?= htmlspecialchars($l['action']) ?></span></td>
        <td><?= htmlspecialchars($l['details']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
