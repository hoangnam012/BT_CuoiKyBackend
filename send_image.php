<?php
// send_image.php
require_once 'db_config.php'; 

$response = array();


$sender_id = $_POST['sender_id'];
$receiver_id = $_POST['receiver_id'];

$is_group = isset($_POST['is_group']) ? (int)$_POST['is_group'] : 0;


if (isset($_FILES['image']['name'])) {
    $target_dir = "uploads/"; 

    $original_name = basename($_FILES["image"]["name"]);

    if (strpos($original_name, '.') === false) {
        $original_name .= ".jpg"; 
    }
    
    $file_name = time() . "_" . $original_name;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        

        $image_url = "http://10.0.2.2:8081/BT_CuoiKyBackend/" . $target_file;

        // Lưu vào Database với type là 'image'
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content, type, is_group) VALUES (?, ?, ?, 'image')");
        $stmt->bind_param("iisi", $sender_id, $receiver_id, $image_url, $is_group);
        
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