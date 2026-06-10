<?php
require 'db.php';
header('Content-Type: application/json');

// Lấy danh sách những người dùng đang online (is_active = 1)
$stmt = $pdo->query("SELECT id, name, avatar, is_active FROM users WHERE is_active = 1");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$response = [];
foreach ($users as $u) {
    $response[] = [
        "id" => $u['id'],
        "name" => $u['name'],
        "avatarUrl" => $u['avatar'] ? $u['avatar'] : "", 
        "isOnline" => true
    ];
}

echo json_encode($response);
?>