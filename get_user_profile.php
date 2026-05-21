<?php
// ============================================================
// get_user_profile.php
// GET /api/get_user_profile.php?user_id=1
// ============================================================

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id không hợp lệ']);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, username, email, avatar_url, bio, is_online, created_at
    FROM users
    WHERE id = ?
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

// Không trả về password
echo json_encode([
    'success' => true,
    'data'    => [
        'id'         => (int)$user['id'],
        'username'   => $user['username'],
        'email'      => $user['email'],
        'avatar_url' => $user['avatar_url'],
        'bio'        => $user['bio'] ?? '',
        'is_online'  => (bool)$user['is_online'],
        'created_at' => $user['created_at'],
    ]
]);

$stmt->close();
$conn->close();