<?php
// ============================================================
// check_friend_status.php
// GET /api/check_friend_status.php?user_id=1&target_id=2
//
// Trả về:
//   status: 'none' | 'pending_sent' | 'pending_received' | 'accepted' | 'blocked'
// ============================================================

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

$userId   = isset($_GET['user_id'])   ? (int)$_GET['user_id']   : 0;
$targetId = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;

if ($userId <= 0 || $targetId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tham số không hợp lệ']);
    exit;
}

if ($userId === $targetId) {
    echo json_encode([
        'success' => true,
        'data'    => ['status' => 'self']
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, sender_id, receiver_id, status
    FROM friends
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    LIMIT 1
");
$stmt->bind_param('iiii', $userId, $targetId, $targetId, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) {
    echo json_encode([
        'success' => true,
        'data'    => ['status' => 'none', 'friendship_id' => null]
    ]);
    exit;
}

// Xác định chiều gửi
$friendStatus = $row['status'];
if ($friendStatus === 'pending') {
    $friendStatus = ((int)$row['sender_id'] === $userId)
        ? 'pending_sent'
        : 'pending_received';
}

echo json_encode([
    'success' => true,
    'data'    => [
        'status'        => $friendStatus,   // none | pending_sent | pending_received | accepted | blocked
        'friendship_id' => (int)$row['id'],
    ]
]);