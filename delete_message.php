<?php
include 'db_config.php';

// Thiết lập header trả về JSON
header('Content-Type: application/json');

// Kiểm tra xem có message_id gửi lên không
if (isset($_POST['message_id'])) {
    
    $message_id = $_POST['message_id'];

    // Dùng Prepared Statement để xóa cho an toàn
    $stmt = $conn->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $message_id);

    if ($stmt->execute()) {
        // Kiểm tra xem có dòng nào thực sự bị xóa không (tránh ID ảo)
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                "status" => "success", 
                "message" => "Đã thu hồi tin nhắn thành công"
            ]);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "Không tìm thấy tin nhắn để xóa"
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Lỗi hệ thống: " . $conn->error
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Thiếu ID tin nhắn cần thu hồi"
    ]);
}

$conn->close();
?>