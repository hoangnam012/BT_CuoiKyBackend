<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'db_config.php';

if (isset($_POST['sender_id']) && isset($_POST['receiver_id']) && isset($_POST['content'])) {
    
    $sender_id = $_POST['sender_id'];
    $receiver_id = $_POST['receiver_id'];
    $content = $_POST['content'];

    // Dùng Prepared Statement để chống SQL Injection (Lead là phải code bảo mật)
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $content);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Đã gửi tin nhắn"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Lỗi: " . $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Thiếu tham số đầu vào"]);
}

$conn->close();
?>