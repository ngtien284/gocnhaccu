<?php
// dang-xuat.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chỉ xóa các biến session đăng nhập của user hiện tại
unset($_SESSION['user']);
unset($_SESSION['user_name']);
unset($_SESSION['user_avatar']);
unset($_SESSION['registered_email']);
unset($_SESSION['registered_password']);
unset($_SESSION['registered_name']);
unset($_SESSION['registered_phone']);
unset($_SESSION['registered_dob']);
unset($_SESSION['registered_address']);

// ⚠️ LƯU Ý: KHÔNG dùng session_destroy() ở đây vì nó sẽ xóa luôn mảng $_SESSION['users'] chứa danh sách tài khoản đã đăng ký.

// Chuyển hướng về trang chủ sau khi đăng xuất thành công
header("Location: index.php");
exit;
?>