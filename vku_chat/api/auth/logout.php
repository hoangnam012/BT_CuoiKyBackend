<?php
// ============================================================
// api/auth/logout.php
// POST /api/auth/logout.php
// Đăng xuất — xoá token hiện tại
// ============================================================

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Phương thức không được hỗ trợ.', 405);
}

$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(\S+)$/', $header, $m)) {
    $token = $m[1];
    $db = getDB();
    $db->prepare("DELETE FROM user_sessions WHERE token = ?")
       ->execute([$token]);
}

success([], 'Đăng xuất thành công.');