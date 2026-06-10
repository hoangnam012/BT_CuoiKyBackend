<?php
require 'db.php';
header('Content-Type: application/json');

$searchQuery = isset($_GET['q']) ? $_GET['q'] : '';

// Nếu không có từ khóa, trả về mảng rỗng
if (empty($searchQuery)) {
    echo json_encode([]);
    exit;
}

// Từ khóa tìm kiếm (thêm % để tìm gần đúng)
$keyword = "%" . $searchQuery . "%";

// CÂU LỆNH SQL: 
// 1. JOIN bảng chat_targets với bảng users
// 2. Tên hiển thị (display_name): Nếu group_name không null/rỗng thì dùng nó, ngược lại dùng u.name
// 3. Lọc WHERE theo display_name đó
$stmt = $pdo->prepare("
    SELECT 
        ct.id, 
        ct.is_pinned,
        CASE 
            WHEN ct.is_group = 1 AND ct.group_name IS NOT NULL AND ct.group_name != '' THEN ct.group_name
            ELSE u.name 
        END AS display_name
    FROM chat_targets ct
    LEFT JOIN users u ON ct.id = u.id AND ct.is_group = 0
    HAVING display_name LIKE ?
    LIMIT 20
");

$stmt->execute([$keyword]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$response = [];
foreach ($results as $row) {
    // Chỉ thêm vào nếu có tên hợp lệ
    if ($row['display_name']) {
        $response[] = [
            "id" => $row['id'],
            "name" => $row['display_name'],
            "isPinned" => ($row['is_pinned'] == 1)
        ];
    }
}

echo json_encode($response);
?>