<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login();

$redirects = [
    'Receptionist'      => '/receptionist/index.php',
    'Nurse'             => '/nurse/index.php',
    'Doctor'            => '/doctor/index.php',
    'LabTechnician'     => '/lab/index.php',
    'Pharmacist'        => '/pharmacist/index.php',
    'BillingAccountant' => '/billing/index.php',
    'Patient'           => '/patient/index.php',
    'Admin'             => '/admin/users.php',
];

header('Location: ' . ($redirects[$user['role']] ?? '/login.php'));
exit;
