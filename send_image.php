<?php
// send_image.php
require_once 'db_config.php'; 

$response = array();


$sender_id = $_POST['sender_id'];
$receiver_id = $_POST['receiver_id'];


if (isset($_FILES['image']['name'])) {
    $target_dir = "uploads/"; 
    
    $file_name = time() . "_" . basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        

        $image_url = "http://10.0.2.2:8081/vku_app/" . $target_file;

        // Lưu vào Database với type là 'image'
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content, type) VALUES (?, ?, ?, 'image')");
        $stmt->bind_param("iis", $sender_id, $receiver_id, $image_url);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Gửi ảnh thành công";
            $response['image_url'] = $image_url;
        } else {
            $response['success'] = false;
            $response['message'] = "Lỗi Database";
        }
    } else {
        $response['success'] = false;
        $response['message'] = "Lỗi upload file lên server";
    }
} else {
    $response['success'] = false;
    $response['message'] = "Không tìm thấy file ảnh";
}

echo json_encode($response);
?>