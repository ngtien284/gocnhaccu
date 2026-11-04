
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// Kiểm tra đăng nhập
if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header("Location: login.php");
    exit;
}

// Lấy mã người dùng
$nd_ma = (int)($_SESSION['ND_Ma'] ?? 0);

if ($nd_ma <= 0) {
    die("Không tìm thấy mã người dùng. Vui lòng đăng nhập lại.");
}

// Lấy mã đơn hàng từ URL
$order_id = (int)($_GET['id'] ?? 0);

if ($order_id <= 0) {
    die("Mã đơn hàng không hợp lệ.");
}

// Lấy thông tin đơn hàng
$sql_order = "
    SELECT *
    FROM don_hang
    WHERE DH_Ma = :dh_ma
    AND ND_Ma = :nd_ma
";

$stmt_order = $conn->prepare($sql_order);
$stmt_order->execute([
    ':dh_ma' => $order_id,
    ':nd_ma' => $nd_ma
]);

$order = $stmt_order->fetch(PDO::FETCH_ASSOC);

// Không tìm thấy đơn hàng
if (!$order) {
    die("Không tìm thấy đơn hàng.");
}

// Lấy chi tiết đơn hàng
$sql_detail = "
    SELECT *
    FROM chi_tiet_don_hang
    WHERE DH_Ma = :dh_ma
";

$stmt_detail = $conn->prepare($sql_detail);
$stmt_detail->execute([
    ':dh_ma' => $order_id
]);

$order_details = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container my-5">

    <!-- Thông báo thành công -->
    <div class="card shadow-sm border-0 p-4 text-center mb-4">

        <div class="mb-3">
            <i class="fa-solid fa-circle-check text-success"
               style="font-size: 65px;"></i>
        </div>

        <h2 class="text-success fw-bold">
            Đặt hàng thành công!
        </h2>

        <p class="text-muted mb-2">
            Cảm ơn bạn đã mua sắm tại Góc Nhạc Cụ.
        </p>

        <p class="mb-0">
            Mã đơn hàng:
            <strong class="text-danger">
                #<?= htmlspecialchars((string)$order['DH_Ma']) ?>
            </strong>
        </p>

    </div>

    <!-- Thông tin đơn hàng -->
    <div class="card shadow-sm border-0 p-4 mb-4">

        <h4 class="fw-bold border-bottom pb-3 mb-3">
            Thông tin đơn hàng
        </h4>

        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <strong>Ngày đặt:</strong>
                <?= htmlspecialchars($order['DH_NgayDat']) ?>
            </div>

            <div class="col-md-6 mb-2">
                <strong>Trạng thái đơn hàng:</strong>
                <span class="badge bg-warning text-dark">
                    <?= htmlspecialchars($order['DH_TrangThai']) ?>
                </span>
            </div>

            <div class="col-md-6 mb-2">
                <strong>Người nhận:</strong>
                <?= htmlspecialchars($order['DH_TenNguoiNhan']) ?>
            </div>

            <div class="col-md-6 mb-2">
                <strong>Số điện thoại:</strong>
                <?= htmlspecialchars($order['DH_SoDTNhan']) ?>
            </div>

            <div class="col-12 mb-2">
                <strong>Địa chỉ nhận hàng:</strong>
                <?= htmlspecialchars(
                    $order['DH_DiaChiNhan_SoNha'] . ', ' .
                    $order['DH_DiaChiNhan_PhuongXa'] . ', ' .
                    $order['DH_DiaChiNhan_TP']
                ) ?>
            </div>

            <div class="col-12 mb-2">
                <strong>Ghi chú đơn hàng:</strong>
                <?= !empty($order['DH_GhiChu'])
                    ? nl2br(htmlspecialchars($order['DH_GhiChu']))
                    : 'Không có' ?>
            </div>

            <div class="col-md-6 mb-2">
                <strong>Phương thức thanh toán:</strong>
                <?= htmlspecialchars($order['DH_PhuongThucThanhToan']) ?>
            </div>

            <div class="col-md-6 mb-2">
                <strong>Trạng thái thanh toán:</strong>
                <?= htmlspecialchars($order['DH_TrangThaiThanhToan'] ?? '') ?>
            </div>
        </div>

    </div>

    <!-- Chi tiết đơn hàng -->
    <div class="card shadow-sm border-0 p-4 mb-4">

        <h4 class="fw-bold border-bottom pb-3 mb-3">
            Chi tiết sản phẩm
        </h4>

        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">

                <thead class="table-light">
                    <tr>
                        <th>Mã nhạc cụ</th>
                        <th>Mã phụ kiện</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($order_details as $detail): ?>
                        <tr>
                            <td>
                                <?= $detail['NC_Ma'] !== null
                                    ? htmlspecialchars((string)$detail['NC_Ma'])
                                    : '-' ?>
                            </td>

                            <td>
                                <?= $detail['PK_Ma'] !== null
                                    ? htmlspecialchars((string)$detail['PK_Ma'])
                                    : '-' ?>
                            </td>

                            <td>
                                <?= (int)$detail['CTDH_SoLuongSP'] ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float)$detail['CTDH_Gia'],
                                    0, ',', '.'
                                ) ?> đ
                            </td>

                            <td>
                                <?= number_format(
                                    (float)$detail['CTDH_ThanhTien'],
                                    0, ',', '.'
                                ) ?> đ
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>

        <div class="text-end mt-3">
            <h4 class="fw-bold text-danger">
                Tổng tiền:
                <?= number_format(
                    (float)$order['DH_TongTien'],
                    0, ',', '.'
                ) ?> đ
            </h4>
        </div>

    </div>

    <!-- Nút điều hướng -->
    <div class="text-center">
        <a href="index.php" class="btn btn-warning fw-bold px-4 py-2">
            <i class="fa-solid fa-house me-2"></i>
            Tiếp tục mua sắm
        </a>

        <a href="orders.php" class="btn btn-outline-dark fw-bold px-4 py-2">
            Xem đơn hàng của tôi
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
