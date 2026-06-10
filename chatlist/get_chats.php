<?php
require 'db.php';
header('Content-Type: application/json');

$currentUserId = isset($_GET['userId']) ? $_GET['userId'] : 'u1'; // Mặc định là u1 nếu không truyền

// Câu SQL thần thánh: Dùng FIND_IN_SET để tìm xem currentUserId có trong cột pinned_by không. 
// Nếu có trả về 1, không có trả về 0. Sau đó ORDER BY theo cột này để đẩy lên đầu tiên.
$stmt = $pdo->prepare("
    SELECT ct.id, ct.is_group, ct.group_name, 
           IF(FIND_IN_SET(?, ct.pinned_by) > 0, 1, 0) AS is_pinned_by_me,
           u.name AS user_name, u.avatar, u.is_active
    FROM chat_targets ct
    LEFT JOIN users u ON ct.id = u.id AND ct.is_group = 0
    ORDER BY is_pinned_by_me DESC, ct.id ASC
");
$stmt->execute([$currentUserId]);
$targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$response = [];
foreach ($targets as $t) {
    $isGroup = ($t['is_group'] == 1);
    $name = $isGroup ? $t['group_name'] : $t['user_name'];
    if (!$name) $name = "Người dùng ẩn danh";

    $response[] = [
        "id" => $t['id'],
        "senderName" => $name, // Chỗ này đã xử lý ưu tiên group_name, nếu null thì lấy users.name
        "avatarUrl" => $t['avatar'] ? $t['avatar'] : "",
        "lastMessage" => "Đây là tin nhắn cuối cùng...",
        "time" => "10:30",
        "isOnline" => ($t['is_active'] == 1),
        "isGroup" => $isGroup,
        "unreadCount" => rand(0, 3),
        "isPinned" => ($t['is_pinned_by_me'] == 1) // Trả về trạng thái ghim của riêng người này
    ];
}

echo json_encode($response);
?>