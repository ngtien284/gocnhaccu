<?php
// cart_update.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? ''; // 'increase', 'decrease', hoặc 'set'

    if ($id && isset($_SESSION['cart'][$id])) {
        if ($action === 'increase') {
            $_SESSION['cart'][$id]['quantity'] += 1;
        } elseif ($action === 'decrease') {
            $_SESSION['cart'][$id]['quantity'] -= 1;
            // Nếu giảm về 0 thì tự động xóa sản phẩm
            if ($_SESSION['cart'][$id]['quantity'] <= 0) {
                unset($_SESSION['cart'][$id]);
            }
        } elseif ($action === 'set' && isset($_POST['quantity'])) {
            $new_qty = (int)$_POST['quantity'];
            if ($new_qty > 0) {
                $_SESSION['cart'][$id]['quantity'] = $new_qty;
            } else {
                unset($_SESSION['cart'][$id]);
            }
        }
    }
}

header("Location: gio-hang.php");
exit;
