<?php
require_once __DIR__ . '/auth.php';
logoutAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: index.php');
    exit;
}
echo json_encode(['success' => true]);
