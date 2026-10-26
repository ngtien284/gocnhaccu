<?php
$host = 'localhost';
$db_name = 'gocnhaccu'; // Thay bằng tên database của bạn
$username = 'root';
$password = '';

try {
    // Kết nối qua thư viện PDO an toàn
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    // Bật chế độ báo lỗi nếu có sai sót câu lệnh SQL
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Kết nối database thất bại: " . $e->getMessage());
}
?>
