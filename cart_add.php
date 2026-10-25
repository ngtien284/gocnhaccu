<?php
// cart_add.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    $id = $_REQUEST['id'] ?? null;
    $name = $_REQUEST['name'] ?? 'Sản phẩm GOCNHACCU';
    $price = (float)($_REQUEST['price'] ?? 0);
    $image = $_REQUEST['image'] ?? 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=300&q=80';
    $quantity = (int)($_REQUEST['quantity'] ?? 1);

    if ($id) {
        // Nếu sản phẩm đã có trong giỏ hàng -> Tăng số lượng
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += $quantity;
        } else {
            // Sản phẩm mới -> Thêm mới vào mảng
            $_SESSION['cart'][$id] = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'image' => $image,
                'quantity' => $quantity
            ];
        }
    }
}

// Chuyển hướng sang trang giỏ hàng
header("Location: gio-hang.php");
exit;
