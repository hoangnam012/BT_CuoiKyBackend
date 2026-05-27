<?php
// ============================================================
// api/auth/register.php
// POST /api/auth/register.php
// Đăng ký tài khoản mới → gửi OTP xác minh email thật
// ============================================================

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Phương thức không được hỗ trợ.', 405);
}

// --- 1. Lấy dữ liệu từ body JSON ---
$body    = getJsonBody();
$username = trim($body['username'] ?? '');
$email    = trim($body['email']    ?? '');
$phone    = trim($body['phone']    ?? '');
$password = $body['password']      ?? '';

// --- 2. Validate đầu vào ---
if (empty($username) || empty($email) || empty($password)) {
    error('Vui lòng nhập đầy đủ tên, email và mật khẩu.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error('Email không đúng định dạng.');
}

if (strlen($password) < 6) {
    error('Mật khẩu phải có ít nhất 6 ký tự.');
}

if (strlen($username) < 3 || strlen($username) > 50) {
    error('Tên người dùng phải từ 3 đến 50 ký tự.');
}

// --- 3. Kiểm tra trùng lặp ---
$db = getDB();

$stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
$stmt->execute([$email, $username]);
$existing = $stmt->fetch();

if ($existing) {
    error('Email hoặc tên người dùng đã được sử dụng.');
}

// --- 4. Lưu user (chưa xác minh) ---
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare("
    INSERT INTO users (username, email, phone, password, is_verified)
    VALUES (?, ?, ?, ?, 0)
");
$stmt->execute([
    $username,
    $email,
    $phone ?: null,
    $hashedPassword
]);
$userId = (int) $db->lastInsertId();

// --- 5. Tạo và lưu OTP ---
$otp = generateOtp();
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Xoá OTP cũ chưa dùng cho email này (tránh rác)
$db->prepare("DELETE FROM otp_codes WHERE email = ? AND type = 'register' AND is_used = 0")
   ->execute([$email]);

$stmt = $db->prepare("
    INSERT INTO otp_codes (email, otp_code, type, expires_at)
    VALUES (?, ?, 'register', ?)
");
$stmt->execute([$email, $otp, $expiresAt]);

// --- 6. Gửi email OTP THẬT qua hệ thống PHPMailer ---
// Đã được bỏ dấu // và thêm logic kiểm tra lỗi gửi thư ngầm
$sent = sendOtpEmail($email, $otp, 'register');

if (!$sent) {
    // Nếu hệ thống mạng local bị lỗi SMTP hoặc chặn kết nối Google, báo lỗi ngay để bảo vệ luồng dữ liệu
    error('Tài khoản đã tạo nhưng hệ thống không thể gửi mã OTP xác minh. Vui lòng thử lại sau.');
}

// --- 7. Trả về kết quả ---
success([
    'user_id' => $userId,
    'email'   => $email,
], 'Đăng ký thành công! Vui lòng kiểm tra hộp thư email của bạn để lấy mã xác minh.');