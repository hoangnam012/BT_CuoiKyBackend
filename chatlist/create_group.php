<?php
require 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$groupName = $data['groupName'];

// 1. Tạo một ID nhóm ngẫu nhiên (ví dụ: g_1684039201)
$newGroupId = "g_" . time();

// 2. Thêm vào bảng chat_targets (is_group = 1)
$stmt = $pdo->prepare("INSERT INTO chat_targets (id, is_group, group_name) VALUES (?, 1, ?)");

if ($stmt->execute([$newGroupId, $groupName])) {
    // Nếu bạn có bảng group_members, bạn có thể chạy vòng lặp $data['memberIds'] để thêm thành viên vào đây
    echo json_encode(["status" => "success", "id" => $newGroupId]);
} else {
    echo json_encode(["status" => "error"]);
}
?>