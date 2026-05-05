<?php
include 'db_config.php';

$sender_id = $_GET['sender_id'];
$receiver_id = $_GET['receiver_id'];

$sql = "SELECT * FROM messages WHERE 
        (sender_id = $sender_id AND receiver_id = $receiver_id) OR 
        (sender_id = $receiver_id AND receiver_id = $sender_id) 
        ORDER BY created_at DESC";

$result = $conn->query($sql);
$messages = [];

while($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);
?>