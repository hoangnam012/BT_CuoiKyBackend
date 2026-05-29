<?php
// ============================================================
// config/cors.php
// Cho phép Android app gọi API (CORS + JSON header)
// ============================================================

// Cho phép mọi origin trong môi trường dev (XAMPP local)
// Khi lên production, thay * bằng domain thật
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Trình duyệt / Retrofit gửi OPTIONS preflight → trả về 200 ngay
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================================
// helpers/response.php  (gộp chung vào file này cho tiện)
// Các hàm tiện ích dùng chung toàn backend
// ============================================================

// --- NHÚNG THƯ VIỆN PHPMAILER ĐỂ GỬI MAIL THẬT ---
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Trả về JSON thành công.
 */
function success(array $data = [], string $message = 'Thành công'): void {
    echo json_encode(array_merge(['success' => true, 'message' => $message], $data));
    exit;
}

/**
 * Trả về JSON lỗi.
 */
function error(string $message, int $httpCode = 400): void {
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * Lấy body JSON từ request Android gửi lên.
 */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Tạo token ngẫu nhiên an toàn (dùng cho session & reset_token).
 */
function generateToken(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes)); // 64 ký tự hex
}

/**
 * Tạo mã OTP 6 chữ số ngẫu nhiên.
 */
function generateOtp(): string {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Gửi email OTP thật qua máy chủ SMTP của Google Gmail (Sử dụng thư viện PHPMailer).
 */
function sendOtpEmail(string $toEmail, string $otp, string $type): bool {
    $mail = new PHPMailer(true);

    try {
        // --- 1. Cấu hình kết nối Server Gmail ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                     // Máy chủ gửi thư của Google
        $mail->SMTPAuth   = true;                                 // Bật tính năng xác thực đăng nhập SMTP
        $mail->Username   = 'anhhoang24050303@gmail.com';   // ĐIỀN EMAIL THẬT CỦA BẠN VÀO ĐÂY
        $mail->Password   = 'cnqckmczouiwwyxa';                 // ĐIỀN MẬT KHẨU ỨNG DỤNG GMAIL 16 KÝ TỰ VÀO ĐÂY
$mail->SMTPDebug  = 'error_log'; 
$mail->Debugoutput = function($str, $level) { error_log("SMTP_DEBUG: $str"); };
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Cơ chế mã hóa bảo mật TLS
        $mail->Port       = 587;                                  // Cổng kết nối mạng SMTP TLS của Google
        $mail->CharSet    = 'UTF-8';                              // Ép font UTF-8 để hiển thị tiếng Việt không bị lỗi dấu

        // --- 2. Định danh người gửi & người nhận ---
        $mail->setFrom('anhhoang24050303@gmail.com', 'VKU Chat System');
        $mail->addAddress($toEmail);                              // Địa chỉ Email khách hàng (từ Android gửi lên)

        // --- 3. Thiết lập nội dung thư dựa theo hành động (HTML) ---
        $mail->isHTML(true);

        if ($type === 'register') {
            $mail->Subject = '[VKU Chat] Mã xác minh kích hoạt tài khoản mới';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e0e0e0; max-width: 500px;'>
                    <h3 style='color: #5865F2;'>Chào mừng bạn đến với VKU Chat!</h3>
                    <p>Cảm ơn bạn đã đăng ký tài khoản. Mã OTP để xác minh và kích hoạt tài khoản của bạn là:</p>
                    <div style='background: #f1f3f4; padding: 15px; font-size: 24px; font-weight: bold; text-align: center; color: #5865F2; letter-spacing: 2px;'>
                        $otp
                    </div>
                    <p style='color: #666; font-size: 13px; margin-top: 15px;'>Mã có hiệu lực trong vòng 10 phút. Tuyệt đối không chia sẻ mã này với bất kỳ ai để bảo vệ tài khoản.</p>
                </div>
            ";
        } else {
            $mail->Subject = '[VKU Chat] Yêu cầu đặt lại mật khẩu tài khoản';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e0e0e0; max-width: 500px;'>
                    <h3 style='color: #f5a623;'>Yêu cầu khôi phục mật khẩu</h3>
                    <p>Chúng tôi nhận được yêu cầu cài đặt lại mật khẩu cho tài khoản này. Mã OTP bảo mật của bạn là:</p>
                    <div style='background: #f1f3f4; padding: 15px; font-size: 24px; font-weight: bold; text-align: center; color: #f5a623; letter-spacing: 2px;'>
                        $otp
                    </div>
                    <p style='color: #666; font-size: 13px; margin-top: 15px;'>Mã có hiệu lực trong 10 phút. Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.</p>
                </div>
            ";
        }

        // --- 4. Gửi thư ---
        $mail->send();
        return true;

    } catch (Exception $e) {
        // Ghi nhật ký lỗi vào file log của XAMPP để tiện xử lý khi cấu hình sai mật khẩu
        error_log("PHPMailer Error: Mail không thể gửi đi. Chi tiết lỗi: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Xác thực token từ header Authorization: Bearer <token>
 * Trả về user_id nếu hợp lệ, null nếu không hợp lệ.
 */
function requireAuth(): int {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/', $header, $m)) {
        error('Chưa đăng nhập hoặc token không hợp lệ.', 401);
    }
    $token = $m[1];

    $db = getDB();
    $stmt = $db->prepare("
        SELECT user_id FROM user_sessions
        WHERE token = ? AND expires_at > NOW()
    ");
    $stmt->execute([token]);
    $row = $stmt->fetch();

    if (!$row) {
        error('Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.', 401);
    }
    return (int) $row['user_id'];
}