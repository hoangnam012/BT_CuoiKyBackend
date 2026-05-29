<?php
// ============================================================
// api/auth/forgot_password.php
// POST /api/auth/forgot_password.php
// Gửi OTP quên mật khẩu → kiểm tra email/phone tồn tại trước
// ============================================================

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Phương thức không được hỗ trợ.', 405);
}

$body  = getJsonBody();
$login = trim($body['email'] ?? '');  // email hoặc số điện thoại

if (empty($login)) {
    error('Vui lòng nhập email hoặc số điện thoại.');
}

// --- Tìm user ---
$db   = getDB();
$stmt = $db->prepare("
    SELECT id, email, is_verified FROM users
    WHERE email = ? OR phone = ?
    LIMIT 1
");
$stmt->execute([$login, $login]);
$user = $stmt->fetch();

// Luôn trả về thành công để không lộ thông tin user
// (tránh attacker dò email)
if (!$user || !$user['is_verified']) {
    success([], 'Nếu email/SĐT tồn tại, mã xác minh đã được gửi.');
}

// --- Tạo OTP ---
$otp       = generateOtp();
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$db->prepare("DELETE FROM otp_codes WHERE email = ? AND type = 'forgot_password' AND is_used = 0")
   ->execute([$user['email']]);

$db->prepare("
    INSERT INTO otp_codes (email, otp_code, type, expires_at)
    VALUES (?, ?, 'forgot_password', ?)
")->execute([$user['email'], $otp, $expiresAt]);

sendOtpEmail($user['email'], $otp, 'forgot_password');

// DEV: bỏ comment để xem OTP khi không có email thật
success([
    // 'otp' => $otp,
    'email' => $user['email'],
], 'Mã xác minh đã được gửi đến email của bạn.');