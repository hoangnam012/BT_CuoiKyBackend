<?php
// ============================================================
// api/auth/login.php
// POST /api/auth/login.php
// Đăng nhập bằng email + mật khẩu → trả về token
// ============================================================

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Phương thức không được hỗ trợ.', 405);
}

// --- 1. Lấy dữ liệu ---
$body     = getJsonBody();
$login    = trim($body['email'] ?? '');      // email hoặc số điện thoại
$password = $body['password'] ?? '';

if (empty($login) || empty($password)) {
    error('Vui lòng nhập email/SĐT và mật khẩu.');
}

// --- 2. Tìm user theo email hoặc phone ---
$db   = getDB();
$stmt = $db->prepare("
    SELECT id, username, email, password, is_verified, is_active, avatar_url
    FROM users
    WHERE email = ? OR phone = ?
    LIMIT 1
");
$stmt->execute([$login, $login]);
$user = $stmt->fetch();

// --- 3. Kiểm tra user tồn tại và mật khẩu ---
if (!$user || !password_verify($password, $user['password'])) {
    error('Email/SĐT hoặc mật khẩu không đúng.');
}

// --- 4. Kiểm tra tài khoản bị khoá ---
if (!$user['is_active']) {
    error('Tài khoản của bạn đã bị khoá. Vui lòng liên hệ hỗ trợ.', 403);
}

// --- 5. Kiểm tra xác minh email ---
if (!$user['is_verified']) {
    // Tạo OTP mới để gửi lại nếu chưa xác minh
    $otp       = generateOtp();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $db->prepare("DELETE FROM otp_codes WHERE email = ? AND type = 'register' AND is_used = 0")
       ->execute([$user['email']]);

    $db->prepare("INSERT INTO otp_codes (email, otp_code, type, expires_at) VALUES (?, ?, 'register', ?)")
       ->execute([$user['email'], $otp, $expiresAt]);

    sendOtpEmail($user['email'], $otp, 'register');

    error('Tài khoản chưa xác minh email. Chúng tôi đã gửi lại mã OTP.', 403);
}

// --- 6. Tạo session token ---
$token     = generateToken();
$expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
$deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? null;
$ipAddress  = $_SERVER['REMOTE_ADDR']     ?? null;

$db->prepare("
    INSERT INTO user_sessions (user_id, token, device_info, ip_address, expires_at)
    VALUES (?, ?, ?, ?, ?)
")->execute([$user['id'], $token, $deviceInfo, $ipAddress, $expiresAt]);

// --- 7. Trả về kết quả ---
success([
    'token'      => $token,
    'expires_at' => $expiresAt,
    'user'       => [
        'id'         => $user['id'],
        'username'   => $user['username'],
        'email'      => $user['email'],
        'avatar_url' => $user['avatar_url'],
    ],
], 'Đăng nhập thành công!');