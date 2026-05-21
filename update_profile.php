<?php
// ============================================================
// update_profile.php
// PUT /api/update_profile.php
// Body JSON: { "user_id": 1, "username": "...", "bio": "...", "avatar_url": "..." }
// ============================================================

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

$userId = isset($body['user_id']) ? (int)$body['user_id'] : 0;
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id không hợp lệ']);
    exit;
}

// Lấy dữ liệu hiện tại
$fetch = $conn->prepare("SELECT username, bio, avatar_url FROM users WHERE id = ?");
$fetch->bind_param('i', $userId);
$fetch->execute();
$current = $fetch->get_result()->fetch_assoc();
$fetch->close();

if (!$current) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng']);
    $conn->close();
    exit;
}

// Merge: chỉ cập nhật trường được gửi lên
$username  = isset($body['username'])   ? trim($body['username'])   : $current['username'];
$bio       = isset($body['bio'])        ? trim($body['bio'])        : $current['bio'];
$avatarUrl = isset($body['avatar_url']) ? trim($body['avatar_url']) : $current['avatar_url'];

// Validate
if (empty($username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tên người dùng không được để trống']);
    $conn->close();
    exit;
}

if (strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tên người dùng tối đa 50 ký tự']);
    $conn->close();
    exit;
}

// Kiểm tra username trùng (bỏ qua chính user này)
$dupCheck = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$dupCheck->bind_param('si', $username, $userId);
$dupCheck->execute();
if ($dupCheck->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Tên người dùng đã tồn tại']);
    $dupCheck->close();
    $conn->close();
    exit;
}
$dupCheck->close();

$update = $conn->prepare("
    UPDATE users
    SET username = ?, bio = ?, avatar_url = ?, updated_at = NOW()
    WHERE id = ?
");
$update->bind_param('sssi', $username, $bio, $avatarUrl, $userId);

if ($update->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thành công',
        'data'    => [
            'id'         => $userId,
            'username'   => $username,
            'bio'        => $bio,
            'avatar_url' => $avatarUrl,
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $conn->error]);
}

$update->close();
$conn->close();