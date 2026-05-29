<?php
// ============================================================
// config/database.php
// Cấu hình kết nối MySQL qua XAMPP
// ============================================================

define('DB_HOST',     'localhost');   // XAMPP dùng localhost
define('DB_PORT',     3306);          // Cổng mặc định MySQL
define('DB_NAME',     'vku_chat');    // Tên database
define('DB_USER',     'root');        // User mặc định XAMPP
define('DB_PASS',     '');            // Mật khẩu mặc định XAMPP (để trống)
define('DB_CHARSET',  'utf8mb4');

/**
 * Trả về kết nối PDO duy nhất (Singleton pattern).
 * Gọi getDB() bất kỳ đâu để lấy kết nối.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Ném exception khi lỗi
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Kết quả trả về dạng mảng kết hợp
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Dùng Prepared Statement thật
            ]);

		date_default_timezone_set('Asia/Ho_Chi_Minh'); 
    		$pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            // Không lộ thông tin lỗi ra ngoài
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
            exit;
        }
    }

    return $pdo;
}