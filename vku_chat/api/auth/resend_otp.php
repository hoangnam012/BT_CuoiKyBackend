<?php
// ============================================================
// api/auth/resend_otp.php
// POST /api/auth/resend_otp.php
// Gửi lại mã OTP (dùng trong màn hình OtpVerificationScreen)
// ============================================================

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Phương thức không được hỗ trợ.', 405);
}

$body  = getJsonBody();
$email = trim($body['email'] ?? '');
$type  = trim($body['type']  ?? 'register');  // 'register' hoặc 'forgot_password'

if (empty($email)) {
    error('Vui lòng nhập email.');
}

if (!in_array($type, ['register', 'forgot_password'], true)) {
    error('Loại OTP không hợp lệ.');
}

// --- Giới hạn gửi: tối đa 1 lần/phút ---
$db   = getDB();
$stmt = $db->prepare("
    SELECT created_at FROM otp_codes
    WHERE email = ? AND type = ?
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$email, $type]);
$last = $stmt->fetch();

if ($last) {
    $diff = time() - strtotime($last['created_at']);
    if ($diff < 60) {
        error('Vui lòng chờ ' . (60 - $diff) . ' giây trước khi gửi lại.');
    }
}

// --- Tạo OTP mới ---
$otp       = generateOtp();
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$db->prepare("DELETE FROM otp_codes WHERE email = ? AND type = ? AND is_used = 0")
   ->execute([$email, $type]);

$db->prepare("
    INSERT INTO otp_codes (email, otp_code, type, expires_at)
    VALUES (?, ?, ?, ?)
")->execute([$email, $otp, $type, $expiresAt]);

sendOtpEmail($email, $otp, $type);

success([
    // 'otp' => $otp,  // Bỏ comment khi debug
], 'Mã OTP mới đã được gửi đến email của bạn.');