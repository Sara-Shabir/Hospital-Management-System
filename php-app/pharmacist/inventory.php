<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['Pharmacist']);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_batch') {
    $stmt = $pdo->prepare(
        'INSERT INTO inventory_items (name, batch_number, quantity, unit_price, expiry_date, low_stock_threshold)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        trim($_POST['name']), trim($_POST['batch_number']), (int) $_POST['quantity'],
        (float) $_POST['unit_price'], $_POST['expiry_date'] ?: null, (int) $_POST['low_stock_threshold'],
    ]);
    $message = 'Inventory batch added.';
}

$items = $pdo->query('SELECT * FROM inventory_items ORDER BY name')->fetchAll();

$pageTitle = 'Pharmacist — Inventory';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Pharmacy Inventory</h1>
  <p>Stock levels and low-stock alerts.</p>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="card">
  <h3>Add a new stock batch</h3>
  <form method="post" class="form-grid">
    <input type="hidden" name="action" value="add_batch">
    <div class="field"><label>Medicine name</label><input type="text" name="name" required></div>
    <div class="field"><label>Batch number</label><input type="text" name="batch_number" required></div>
    <div class="field"><label>Quantity</label><input type="number" name="quantity" required></div>
    <div class="field"><label>Unit price</label><input type="number" step="0.01" name="unit_price" required></div>
    <div class="field"><label>Expiry date</label><input type="date" name="expiry_date"></div>
    <div class="field"><label>Low stock threshold</label><input type="number" name="low_stock_threshold" value="20"></div>
    <div class="field" style="align-self:end;"><button type="submit" class="btn btn-primary">Add batch</button></div>
  </form>
</div>

<div class="card">
  <h3>Current stock</h3>
  <table>
    <thead><tr><th>Name</th><th>Batch</th><th>Qty</th><th>Unit price</th><th>Expiry</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $i): ?>
      <tr class="<?= $i['quantity'] <= $i['low_stock_threshold'] ? 'high-risk' : '' ?>">
        <td><?= htmlspecialchars($i['name']) ?></td>
        <td><?= htmlspecialchars($i['batch_number']) ?></td>
        <td><?= (int) $i['quantity'] ?></td>
        <td><?= number_format((float) $i['unit_price'], 2) ?></td>
        <td><?= htmlspecialchars($i['expiry_date'] ?? '—') ?></td>
        <td><?php if ($i['quantity'] <= $i['low_stock_threshold']): ?><span class="badge badge-danger">Low stock</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
