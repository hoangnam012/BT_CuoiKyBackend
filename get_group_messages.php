<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

// Require file kết nối CSDL của ông vào đây
require_once 'db_config.php'; 

// Kiểm tra xem client có truyền group_id lên không
if (isset($_GET['group_id'])) {
    $group_id = $_GET['group_id'];

    // JOIN bảng messages và bảng users (sửa tên bảng users nếu của ông khác)
    // Điều kiện: receiver_id là ID của nhóm VÀ is_group = 1
    $sql = "SELECT m.*, u.username AS sender_name, u.avatar AS sender_avatar 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE m.receiver_id = ? AND m.is_group = 1 
            ORDER BY m.created_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = array();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    if (count($messages) > 0) {
        echo json_encode($messages);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Chưa có tin nhắn nào trong nhóm này",
            "data" => []
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Thiếu tham số group_id"
    ]);
}
?>