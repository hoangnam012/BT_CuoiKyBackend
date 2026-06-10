<?php
require 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$targetId = $data['id'];
$isPinned = $data['isPinned']; 
$userId = $data['userId']; // ID của người đang dùng app (VD: "u1")

// 1. Lấy danh sách những người đã ghim cuộc trò chuyện này
$stmt = $pdo->prepare("SELECT pinned_by FROM chat_targets WHERE id = ?");
$stmt->execute([$targetId]);
$row = $stmt->fetch();
$pinnedByArray = $row['pinned_by'] ? explode(',', $row['pinned_by']) : [];

// 2. Thêm hoặc Xóa userId khỏi danh sách
if ($isPinned) {
    if (!in_array($userId, $pinnedByArray)) {
        $pinnedByArray[] = $userId;
    }
} else {
    $pinnedByArray = array_diff($pinnedByArray, [$userId]);
}

// 3. Cập nhật lại vào Database
$newPinnedBy = implode(',', $pinnedByArray);
$updateStmt = $pdo->prepare("UPDATE chat_targets SET pinned_by = ? WHERE id = ?");
if($updateStmt->execute([$newPinnedBy, $targetId])) {
    echo json_encode(["status" => "success", "pinned_by" => $newPinnedBy]);
} else {
    echo json_encode(["status" => "error"]);
}
?>