<?php
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$error = '';
if (isset($_GET['expired'])) {
    $error = 'Your session expired due to inactivity. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        $upd = $pdo->prepare('UPDATE users SET last_activity_at = NOW() WHERE id = ?');
        $upd->execute([$user['id']]);

        log_action($user['id'], 'LOGIN', 'Role: ' . $user['role']);

        header('Location: /index.php');
        exit;
    }

    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log in — Hospital Management System</title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="login-screen">
  <div class="login-card">
    <div class="login-brand">Hospital Management System</div>
    <div class="login-tagline">Sign in to continue to your dashboard</div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary">Log in</button>
    </form>

    <div class="demo-hint">
      Demo accounts (password: <code>password123</code>):<br>
      admin@hms.test · receptionist@hms.test · nurse@hms.test · doctor@hms.test<br>
      labtech@hms.test · pharmacist@hms.test · billing@hms.test · patient@hms.test
    </div>
  </div>
</div>
</body>
</html>
