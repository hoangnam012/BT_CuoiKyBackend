<?php
// ============================================================
// api/auth/verify_otp.php
// POST /api/auth/verify_otp.php
// Xác minh mã OTP (cho cả đăng ký và quên mật khẩu)
// ============================================================

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Phương thức không được hỗ trợ.', 405);
}

// --- 1. Lấy dữ liệu ---
$body  = getJsonBody();
$email = trim($body['email']    ?? '');
$otp   = trim($body['otp_code'] ?? '');
$type  = trim($body['type']     ?? '');  // 'register' hoặc 'forgot_password'

if (empty($email) || empty($otp) || empty($type)) {
    error('Thiếu thông tin email, OTP hoặc loại xác minh.');
}

if (!in_array($type, ['register', 'forgot_password'], true)) {
    error('Loại xác minh không hợp lệ.');
}

// --- 2. Tìm OTP trong DB ---
$db   = getDB();
$stmt = $db->prepare("
    SELECT id FROM otp_codes
    WHERE email    = ?
      AND otp_code = ?
      AND type     = ?
      AND is_used  = 0
      AND expires_at > NOW()
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$email, $otp, $type]);
$record = $stmt->fetch();

if (!$record) {
    error('Mã OTP không đúng hoặc đã hết hạn.');
}

// --- 3. Đánh dấu OTP đã dùng ---
$db->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?")
   ->execute([$record['id']]);

// --- 4. Xử lý theo loại ---
if ($type === 'register') {
    // Kích hoạt tài khoản
    $stmt = $db->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() === 0) {
        error('Không tìm thấy tài khoản với email này.', 404);
    }

    // Tự động đăng nhập sau xác minh → tạo token
    $userStmt = $db->prepare("SELECT id, username, email, avatar_url FROM users WHERE email = ?");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch();

    $token     = generateToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    $db->prepare("INSERT INTO user_sessions (user_id, token, expires_at) VALUES (?, ?, ?)")
       ->execute([$user['id'], $token, $expiresAt]);

    success([
        'token' => $token,
        'user'  => [
            'id'         => $user['id'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'avatar_url' => $user['avatar_url'],
        ],
    ], 'Xác minh email thành công! Tài khoản đã được kích hoạt.');

} else {
    // forgot_password → tạo reset_token tạm thời để đặt lại mật khẩu
    $resetToken = generateToken();
    $expiresAt  = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Xoá token cũ
    $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

    $db->prepare("
        INSERT INTO password_resets (email, reset_token, expires_at)
        VALUES (?, ?, ?)
    ")->execute([$email, $resetToken, $expiresAt]);

    success([
        'reset_token' => $resetToken,
        'email'       => $email,
    ], 'Xác minh OTP thành công! Dùng reset_token để đặt mật khẩu mới.');
}