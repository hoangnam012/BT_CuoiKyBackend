<?php
// ============================================================
// api/auth/reset_password.php
// POST /api/auth/reset_password.php
// Đặt lại mật khẩu mới sau khi có reset_token hợp lệ
// ============================================================

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Phương thức không được hỗ trợ.', 405);
}

$body        = getJsonBody();
$resetToken  = trim($body['reset_token']   ?? '');
$newPassword = $body['new_password']        ?? '';
$confirmPass = $body['confirm_password']    ?? '';

// --- Validate ---
if (empty($resetToken) || empty($newPassword)) {
    error('Thiếu reset_token hoặc mật khẩu mới.');
}

if ($newPassword !== $confirmPass) {
    error('Mật khẩu xác nhận không khớp.');
}

if (strlen($newPassword) < 6) {
    error('Mật khẩu mới phải có ít nhất 6 ký tự.');
}

// --- Kiểm tra reset_token ---
$db   = getDB();
$stmt = $db->prepare("
    SELECT email FROM password_resets
    WHERE reset_token = ? AND is_used = 0 AND expires_at > NOW()
    LIMIT 1
");
$stmt->execute([$resetToken]);
$record = $stmt->fetch();

if (!$record) {
    error('Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
}

$email = $record['email'];

// --- Cập nhật mật khẩu ---
$hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
$db->prepare("UPDATE users SET password = ? WHERE email = ?")
   ->execute([$hashed, $email]);

// --- Đánh dấu token đã dùng ---
$db->prepare("UPDATE password_resets SET is_used = 1 WHERE reset_token = ?")
   ->execute([$resetToken]);

// --- Xoá tất cả session cũ (bảo mật: đăng xuất tất cả thiết bị) ---
$userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$userStmt->execute([$email]);
$user = $userStmt->fetch();

if ($user) {
    $db->prepare("DELETE FROM user_sessions WHERE user_id = ?")
       ->execute([$user['id']]);
}

success([], 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập lại.');