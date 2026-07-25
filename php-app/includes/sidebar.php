<?php
$role = $user['role'] ?? '';
$navByRole = [
    'Receptionist'       => [['/receptionist/index.php', 'Check-in & Patients'], ['/receptionist/appointments.php', 'Appointments']],
    'Nurse'              => [['/nurse/index.php', 'Waiting Queue']],
    'Doctor'             => [['/doctor/index.php', 'Consultation Queue']],
    'LabTechnician'      => [['/lab/index.php', 'Lab Worklist']],
    'Pharmacist'         => [['/pharmacist/index.php', 'Prescription Queue'], ['/pharmacist/inventory.php', 'Inventory']],
    'BillingAccountant'  => [['/billing/index.php', 'Encounters to Bill']],
    'Patient'            => [['/patient/index.php', 'My Portal']],
    'Admin'              => [['/admin/users.php', 'User Accounts'], ['/admin/logs.php', 'System Logs']],
];
$links = $navByRole[$role] ?? [];
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<aside class="sidebar">
  <div class="sidebar-brand">HMS</div>
  <div class="sidebar-role"><?= htmlspecialchars($role) ?></div>
  <nav class="sidebar-nav">
    <?php foreach ($links as [$href, $label]): ?>
      <a class="sidebar-link<?= $currentPath === $href ? ' active' : '' ?>" href="<?= htmlspecialchars($href) ?>">
        <?= htmlspecialchars($label) ?>
      </a>
    <?php endforeach; ?>
    <?php if ($role === 'Admin'): ?>
      <a class="sidebar-link" href="/receptionist/index.php">Receptionist view</a>
      <a class="sidebar-link" href="/nurse/index.php">Nurse view</a>
      <a class="sidebar-link" href="/doctor/index.php">Doctor view</a>
      <a class="sidebar-link" href="/lab/index.php">Lab view</a>
      <a class="sidebar-link" href="/pharmacist/index.php">Pharmacist view</a>
      <a class="sidebar-link" href="/billing/index.php">Billing view</a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-footer">
    <div style="font-size:0.85rem;margin-bottom:0.6rem;color:#d3e8e2;">
      <?= htmlspecialchars($user['name'] ?? '') ?>
    </div>
    <form method="post" action="/logout.php">
      <button type="submit" class="logout-btn">Log out</button>
    </form>
  </div>
</aside>
