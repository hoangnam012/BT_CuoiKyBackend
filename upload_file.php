<?php
// Nhớ require file kết nối database của m vào đây (vd: include 'db.php';)
include 'db_config.php';

$senderId = $_POST['sender_id'];
$receiverId = $_POST['receiver_id'];
$is_group = isset($_POST['is_group']) ? (int)$_POST['is_group'] : 0;
$type = "file"; // Gắn cứng luôn loại tin nhắn là file

if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    
    // 1. Lấy Tên Đẹp (Original Name) do Android gửi lên
    $originalName = $_FILES['file']['name'];

    
    // 2. Tạo Tên Xấu (Safe Name) để chống đè file
    $safeName = time() . '_' . basename($originalName);
    
    // 3. Đường dẫn lưu file vật lý trên Server (nhớ tạo thư mục uploads/files/ trước nhé)
    $targetDir = "uploads/files/";
    $targetFilePath = $targetDir . $safeName;

    // 4. Di chuyển file từ bộ nhớ tạm vào kho
    if(move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)) {
        
        // Tạo link URL đầy đủ để Android tải
        // Nhớ sửa lại đoạn "http://..." bằng IP hoặc Domain thật của m
        $fullUrl = "http://10.0.2.2:8081/BT_CuoiKyBackend/" . $targetFilePath;

        // 5. LƯU VÀO DATABASE (Có thêm cột file_name)
        $sql = "INSERT INTO messages (sender_id, receiver_id, content, type, file_name, is_group) 
                VALUES ('$senderId', '$receiverId', '$fullUrl', '$type', '$originalName', '$is_group')";
                
        if (mysqli_query($conn, $sql)) {
            echo json_encode(["status" => "success", "message" => "Đã lưu file ngon lành!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Lỗi Database: " . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Không thể move file vào kho!"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Đéo nhận được file hoặc file bị lỗi!"]);
}
?>