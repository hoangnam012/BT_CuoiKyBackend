<?php
// ============================================================
// send_friend_request.php
// POST /api/send_friend_request.php
// Body JSON: { "sender_id": 1, "receiver_id": 2 }
// ============================================================

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

$senderId   = isset($body['sender_id'])   ? (int)$body['sender_id']   : 0;
$receiverId = isset($body['receiver_id']) ? (int)$body['receiver_id'] : 0;

if ($senderId <= 0 || $receiverId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'sender_id / receiver_id không hợp lệ']);
    exit;
}

if ($senderId === $receiverId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Không thể kết bạn với chính mình']);
    exit;
}

// Kiểm tra đã tồn tại chưa (cả 2 chiều)
$check = $conn->prepare("
    SELECT id, status
    FROM friends
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
");
$check->bind_param('iiii', $senderId, $receiverId, $receiverId, $senderId);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    $statusMsg = match($existing['status']) {
        'pending'  => 'Lời mời kết bạn đã được gửi trước đó',
        'accepted' => 'Hai người đã là bạn bè',
        'blocked'  => 'Không thể gửi lời mời kết bạn',
        default    => 'Quan hệ bạn bè đã tồn tại',
    };
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => $statusMsg]);
    $conn->close();
    exit;
}

// Kiểm tra user tồn tại
$userCheck = $conn->prepare("SELECT id FROM users WHERE id IN (?, ?)");
$userCheck->bind_param('ii', $senderId, $receiverId);
$userCheck->execute();
$userResult = $userCheck->get_result();
if ($userResult->num_rows < 2) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Một trong hai người dùng không tồn tại']);
    $userCheck->close();
    $conn->close();
    exit;
}
$userCheck->close();

// Gửi lời mời
$insert = $conn->prepare("
    INSERT INTO friends (sender_id, receiver_id, status)
    VALUES (?, ?, 'pending')
");
$insert->bind_param('ii', $senderId, $receiverId);

if ($insert->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Đã gửi lời mời kết bạn',
        'data'    => [
            'friendship_id' => $conn->insert_id,
            'status'        => 'pending',
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $conn->error]);
}

$insert->close();
$conn->close();