<?php
$host = '127.0.0.1';
$db   = 'chat'; 
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Lỗi kết nối"]);
    exit;
}
?>