<?php
require_once __DIR__ . '/../config/db.php';

session_start();

const IDLE_TIMEOUT_SECONDS = 10 * 60; // 10 minutes, per the lab manual's security spec

// Writes an auditable event to system_logs — used for login/logout, idle
// auto-logout, and any role action worth tracking for the Admin's log screen.
function log_action(?int $userId, string $action, string $details = ''): void
{
    global $pdo;
    $stmt = $pdo->prepare(
        'INSERT INTO system_logs (user_id, action, details, ip) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

// Ensures a user is logged in, enforces the idle-timeout, and refreshes
// last_activity_at on every authenticated page load.
function require_login(): array
{
    global $pdo;

    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }

    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > IDLE_TIMEOUT_SECONDS) {
        log_action($_SESSION['user_id'], 'AUTO_LOGOUT_IDLE', 'Session expired due to inactivity');
        session_unset();
        session_destroy();
        header('Location: /login.php?expired=1');
        exit;
    }

    $_SESSION['last_activity'] = time();

    $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_active']) {
        session_unset();
        session_destroy();
        header('Location: /login.php');
        exit;
    }

    $stmt = $pdo->prepare('UPDATE users SET last_activity_at = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);

    return $user;
}

// Restricts a page to the given role(s). Admin is always allowed through,
// mirroring the Node backend's RBAC middleware.
function require_role(array $allowedRoles): array
{
    $user = require_login();
    if (!in_array($user['role'], $allowedRoles, true) && $user['role'] !== 'Admin') {
        http_response_code(403);
        die('<p style="font-family:sans-serif;padding:2rem;">403 — Your role does not have access to this page.</p>');
    }
    return $user;
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}
