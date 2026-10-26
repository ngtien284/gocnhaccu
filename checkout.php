<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/connect.php'; 

// Lấy dữ liệu giỏ hàng thực tế từ session của bạn mình
$cart = $_SESSION['cart'] ?? [];

// Nếu giỏ hàng trống, không cho ở lại trang thanh toán, đẩy về giỏ hàng
if (empty($cart)) {
    header("Location: cart.php");
    exit();
}

/*
// Giả lập giỏ hàng để test (Nhóm làm xong cart.php thì xóa đoạn IF này đi)
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        1 => ['id' => 1, 'name' => 'Đàn Guitar Acoustic Fender', 'price' => 3500000, 'quantity' => 1],
        2 => ['id' => 2, 'name' => 'Kèn Harmonica Swan', 'price' => 250000, 'quantity' => 2]
    ];
}
*/

$total_amount = 0;
foreach ($cart as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// XỬ LÝ KHI NGƯỜI DÙNG BẤM NÚT XÁC NHẬN ĐẶT HÀNG
if (isset($_POST['btn_order'])) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $payment_method = $_POST['payment_method'];
    
    // Thu thập địa chỉ tách biệt làm 3 cột theo cấu trúc ảnh CSDL
    $sonha = trim($_POST['address_sonha']);
    $phuongxa = trim($_POST['address_phuongxa']);
    $thànhpho = trim($_POST['address_tp']);
    
    $order_date = date('Y-m-d H:i:s'); // Định dạng datetime cho cột DH_NgayDat

    try {
        // Kích hoạt Transaction
        $conn->beginTransaction();

        // 1. INSERT VÀO BẢNG DON_HANG (Tên cột khớp 100% với ảnh structure bạn gửi)
        $sql_order = "INSERT INTO don_hang (ND_Ma, DH_NgayDat, DH_TongTien, DH_DiaChiNhan_TP, DH_DiaChiNhan_PhuongXa, DH_DiaChiNhan_SoNha, DH_SoDTNhan, DH_TenNguoiNhan, DH_PhuongThucThanhToan, DH_TrangThaiThanhToan, DH_TrangThai) 
                      VALUES (:nd_ma, :ngay_dat, :tong_tien, :tp, :phuong_xa, :so_nha, :sdt, :ten_nhan, :phuong_thuc, :tt_thanhtoan, :trang_thai)";
        
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->execute([
            ':nd_ma'       => 1, // Giả định ID khách hàng là thành viên số 1
            ':ngay_dat'    => $order_date,
            ':tong_tien'   => $total_amount,
            ':tp'          => $thànhpho,
            ':phuong_xa'   => $phuongxa,
            ':so_nha'      => $sonha,
            ':sdt'         => $phone,
            ':ten_nhan'    => $fullname,
            ':phuong_thuc' => $payment_method,
            ':tt_thanhtoan'=> 'Chưa thanh toán', // Trạng thái mặc định
            ':trang_thai'  => 'Chờ duyệt'        // Trạng thái đơn hàng mặc định
        ]);

        // LẤY MÃ ĐƠN HÀNG (DH_Ma) VỪA TẠO RA
        $order_id = $conn->lastInsertId(); 

        // 2. INSERT VÀO BẢNG CHI_TIET_DON_HANG (Tên cột khớp 100% với ảnh structure bạn gửi)
        $sql_detail = "INSERT INTO chi_tiet_don_hang (DH_Ma, NC_Ma, CTDH_SoLuongSP, CTDH_Gia, CTDH_ThanhTien) 
                       VALUES (:dh_ma, :nc_ma, :soluong, :gia, :thanhtien)";
        $stmt_detail = $conn->prepare($sql_detail);

        foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['quantity']; // Tính thành tiền riêng từng món nhạc cụ
            
            $stmt_detail->execute([
                ':dh_ma'     => $order_id,     // Dùng mã đơn hàng vừa lấy ở trên để tạo liên kết
                ':nc_ma'     => $item['id'],   // Mã nhạc cụ lấy từ mảng của bạn mình làm
                ':soluong'   => $item['quantity'],
                ':gia'       => $item['price'],
                ':thanhtien' => $subtotal
            ]);
        }

        // Cam kết hoàn tất lưu vào database
        $conn->commit();

        // Xóa giỏ hàng để kết thúc chu kỳ mua
        unset($_SESSION['cart']);

        // Chuyển sang trang đặt hàng thành công
        header("Location: order_success.php?id=" . $order_id);
        exit();

    } catch (Exception $e) {
        $conn->rollBack(); // Lỗi thì trả database về nguyên vẹn như cũ
        $error_message = "Đặt hàng không thành công. Lỗi hệ thống: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Đơn Hàng</title>
    <!-- Nhúng đúng Bootstrap 5 giống bạn mình -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <!-- Đồng bộ biến màu sắc UI gỗ ấm với file cart.php -->
    <style>
        :root {
          --wood-primary: #8C5835;
          --wood-dark: #754728;
          --wood-bg: #FBF8F5;
          --wood-border: #E7E0D8;
          --wood-text: #2D2319;
        }
        body {
          background-color: var(--wood-bg);
          color: var(--wood-text);
          font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
        .btn-wood {
          background-color: var(--wood-primary);
          color: #FFFFFF;
          border: none;
        }
        .btn-wood:hover {
          background-color: var(--wood-dark);
          color: #FFFFFF;
        }
        .checkout-card {
          background: #ffffff;
          border-radius: 16px;
          border: 1px solid var(--wood-border) !important;
        }
        .product-img {
          width: 55px;
          height: 55px;
          object-fit: cover;
          border-radius: 8px;
        }
    </style>
</head>
<body class="py-5">

<main class="container">
    <div class="mb-4">
        <a href="cart.php" class="text-decoration-none" style="color: var(--wood-primary);"><i class="fa-solid fa-arrow-left me-2"></i>Quay lại giỏ hàng</a>
    </div>

    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger rounded-3 mb-4"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- BÊN TRÁI: FORM ĐIỀN THÔNG TIN GIAO HÀNG -->
        <div class="col-12 col-lg-7">
            <div class="p-4 shadow-sm checkout-card">
                <h2 class="fs-5 fw-bold mb-4"><i class="fa-solid fa-truck-ramped me-2" style="color: var(--wood-primary);"></i>Thông tin nhận hàng</h2>
                
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Họ và tên người nhận *</label>
                        <input type="text" name="fullname" class="form-control rounded-3" required placeholder="Nguyễn Văn A">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Số điện thoại nhận hàng *</label>
                        <input type="tel" name="phone" class="form-control rounded-3" required placeholder="0901234567">
                    </div>

                    <!-- Tách địa chỉ thành 3 ô theo đúng cấu trúc cột database của nhóm -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Số nhà, tên đường *</label>
                        <input type="text" name="address_sonha" class="form-control rounded-3" required placeholder="Ví dụ: 123 Đường Nguyễn Trãi">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Phường / Xã *</label>
                            <input type="text" name="address_phuongxa" class="form-control rounded-3" required placeholder="Ví dụ: Phường An Hội">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Quận / Thành phố, Tỉnh *</label>
                            <input type="text" name="address_tp" class="form-control rounded-3" required placeholder="Ví dụ: Quận Ninh Kiều, Cần Thơ">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Phương thức thanh toán</label>
                        <select name="payment_method" class="form-select rounded-3">
                            <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                            <option value="Banking">Chuyển khoản ngân hàng</option>
                        </select>
                    </div>

                    <button type="submit" name="btn_order" class="btn btn-wood w-100 rounded-pill py-2.5 fw-bold shadow-sm">
                        XÁC NHẬN ĐẶT HÀNG
                    </button>
                </form>
            </div>
        </div>

        <!-- BÊN PHẢI: HIỂN THỊ ĐƠN HÀNG LẤY TỪ GIỎ HÀNG CỦA BẠN MÌNH -->
        <div class="col-12 col-lg-4offset-lg-1 col-lg-5">
            <div class="p-4 shadow-sm checkout-card">
                <h2 class="fs-5 fw-bold mb-3 pb-2 border-bottom">Đơn hàng của bạn</h2>
                
                <div class="overflow-auto mb-4" style="max-height: 280px;">
                    <?php foreach ($cart as $item): 
                        $subtotal = $item['price'] * $item['quantity'];
                    ?>
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom border-light">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="product-img border">
                                <div>
                                    <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($item['name']) ?></div>
                                    <small class="text-muted">Số lượng: <?= $item['quantity'] ?></small>
                                </div>
                            </div>
                            <span class="small fw-bold text-dark"><?= number_format($subtotal, 0, ',', '.') ?> đ</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between mb-2 small text-secondary">
                    <span>Phí vận chuyển</span>
                    <span class="text-success fw-bold">Miễn phí</span>
                </div>


                
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <h5 class="mb-0">Tổng tiền:</h5>
                    <h4 class="text-danger fw-bold mb-0"><?php echo number_format($total_amount, 0, ',', '.'); ?>đ</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- GIAO DIỆN: CUỐI FILE NHÚNG JAVASCRIPT BOOTSTRAP -->
<script src="https://jsdelivr.net"></script>
</body>
</html>
