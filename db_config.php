<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "vku_chat";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
// Cho phép Android truy cập (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
?>