
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// Kiểm tra đăng nhập
if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'orders.php';
    header("Location: login.php");
    exit;
}

$nd_ma = (int)($_SESSION['ND_Ma'] ?? 0);

if ($nd_ma <= 0) {
    die("Không tìm thấy mã người dùng. Vui lòng đăng xuất và đăng nhập lại.");
}

// Lấy mã đơn hàng
$order_id = (int)($_GET['id'] ?? 0);

if ($order_id <= 0) {
    die("Mã đơn hàng không hợp lệ.");
}

// Chỉ lấy đơn hàng thuộc người dùng đang đăng nhập
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

if (!$order) {
    die("Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.");
}

// Lấy chi tiết đơn hàng
$sql_detail = "
    SELECT *
    FROM chi_tiet_don_hang
    WHERE DH_Ma = :dh_ma
    ORDER BY CTDH_Ma ASC
";

$stmt_detail = $conn->prepare($sql_detail);
$stmt_detail->execute([':dh_ma' => $order_id]);

$order_details = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

function detailOrderStatusClass($status) {
    switch ($status) {
        case 'Chờ duyệt':
            return 'status-pending';
        case 'Đã duyệt':
            return 'status-approved';
        case 'Đang giao':
            return 'status-shipping';
        case 'Đã giao':
            return 'status-completed';
        case 'Đã hủy':
            return 'status-cancelled';
        default:
            return 'status-default';
    }
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<style>
.order-detail-page {
    background: #f8f5ef;
    min-height: 70vh;
    padding: 45px 0;
}
.order-detail-page .page-title {
    color: #5b3d2a;
    font-weight: 700;
}
.detail-card {
    background: #fff;
    border: 1px solid #eee5d8;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(75, 49, 28, 0.06);
    padding: 25px;
    margin-bottom: 22px;
}
.detail-card h5 {
    color: #68472f;
    font-weight: 700;
    border-bottom: 2px solid #ead9c3;
    padding-bottom: 13px;
    margin-bottom: 20px;
}
.detail-label {
    color: #88796b;
    font-size: 14px;
    margin-bottom: 4px;
}
.detail-value {
    color: #40352c;
    font-weight: 600;
    overflow-wrap: anywhere;
}
.detail-table thead th {
    background: #f2e9dc;
    color: #5b3d2a;
    white-space: nowrap;
}
.detail-table td {
    vertical-align: middle;
    color: #514438;
}
.detail-total {
    color: #a34d2d;
    font-size: 23px;
    font-weight: 700;
}
.order-status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}
.status-pending { background: #fff0c2; color: #8a6200; }
.status-approved { background: #e4edff; color: #315b9a; }
.status-shipping { background: #e6e2ff; color: #5945a0; }
.status-completed { background: #dff3e5; color: #287442; }
.status-cancelled { background: #fce4e4; color: #a33b3b; }
.status-default { background: #eee9e2; color: #65594c; }
.btn-back-orders {
    display: inline-block;
    background: #8b5e3c;
    color: #fff;
    border-radius: 8px;
    padding: 10px 18px;
    text-decoration: none;
    font-weight: 600;
}
.btn-back-orders:hover {
    background: #684329;
    color: #fff;
}
@media (max-width: 768px) {
    .order-detail-page { padding: 25px 0; }
    .detail-card { padding: 18px; }
}
</style>

<div class="order-detail-page">
    <div class="container">

        <div class="mb-4">
            <h2 class="page-title">
                <i class="fa-solid fa-file-invoice me-2"></i>
                Chi tiết đơn hàng #<?= (int)$order['DH_Ma'] ?>
            </h2>
            <p class="text-muted mb-0">
                Thông tin sản phẩm và giao nhận của đơn hàng.
            </p>
        </div>

        <!-- Trạng thái đơn hàng -->
        <div class="detail-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="detail-label">Mã đơn hàng</div>
                    <div class="detail-value fs-5">
                        #<?= (int)$order['DH_Ma'] ?>
                    </div>
                </div>

                <div>
                    <div class="detail-label">Ngày đặt hàng</div>
                    <div class="detail-value">
                        <?= htmlspecialchars(
                            date('d/m/Y H:i', strtotime($order['DH_NgayDat']))
                        ) ?>
                    </div>
                </div>

                <div>
                    <div class="detail-label">Trạng thái đơn hàng</div>
                    <span class="order-status <?= detailOrderStatusClass($order['DH_TrangThai']) ?>">
                        <?= htmlspecialchars($order['DH_TrangThai']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Thông tin giao hàng -->
        <div class="detail-card">
            <h5>
                <i class="fa-solid fa-location-dot me-2"></i>
                Thông tin giao hàng
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="detail-label">Người nhận</div>
                    <div class="detail-value">
                        <?= htmlspecialchars($order['DH_TenNguoiNhan']) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="detail-label">Số điện thoại</div>
                    <div class="detail-value">
                        <?= htmlspecialchars($order['DH_SoDTNhan']) ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="detail-label">Địa chỉ nhận hàng</div>
                    <div class="detail-value">
                        <?= htmlspecialchars(
                            $order['DH_DiaChiNhan_SoNha'] . ', ' .
                            $order['DH_DiaChiNhan_PhuongXa'] . ', ' .
                            $order['DH_DiaChiNhan_TP']
                        ) ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="detail-label">Ghi chú</div>
                    <div class="detail-value">
                        <?php if (!empty($order['DH_GhiChu'])): ?>
                            <?= nl2br(htmlspecialchars($order['DH_GhiChu'])) ?>
                        <?php else: ?>
                            <span class="text-muted fw-normal">Không có ghi chú</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin thanh toán -->
        <div class="detail-card">
            <h5>
                <i class="fa-solid fa-credit-card me-2"></i>
                Thông tin thanh toán
            </h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="detail-label">Phương thức thanh toán</div>
                    <div class="detail-value">
                        <?= htmlspecialchars($order['DH_PhuongThucThanhToan']) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="detail-label">Trạng thái thanh toán</div>
                    <div class="detail-value">
                        <?= htmlspecialchars($order['DH_TrangThaiThanhToan'] ?? 'Chưa cập nhật') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="detail-card">
            <h5>
                <i class="fa-solid fa-box-open me-2"></i>
                Sản phẩm trong đơn hàng
            </h5>

            <div class="table-responsive">
                <table class="table table-bordered detail-table text-center align-middle">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã nhạc cụ</th>
                            <th>Mã phụ kiện</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($order_details)): ?>
                            <tr>
                                <td colspan="6" class="text-muted py-4">
                                    Đơn hàng chưa có chi tiết sản phẩm.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($order_details as $index => $detail): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>

                                    <td>
                                        <?= $detail['NC_Ma'] !== null
                                            ? (int)$detail['NC_Ma']
                                            : '-' ?>
                                    </td>

                                    <td>
                                        <?= $detail['PK_Ma'] !== null
                                            ? (int)$detail['PK_Ma']
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

                                    <td class="fw-bold">
                                        <?= number_format(
                                            (float)$detail['CTDH_ThanhTien'],
                                            0, ',', '.'
                                        ) ?> đ
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3">
                <div class="detail-label">Tổng thanh toán</div>
                <div class="detail-total">
                    <?= number_format(
                        (float)$order['DH_TongTien'],
                        0, ',', '.'
                    ) ?> đ
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="orders.php" class="btn-back-orders">
                <i class="fa-solid fa-arrow-left me-2"></i>
                Quay lại đơn hàng của tôi
            </a>

            <a href="index.php" class="btn btn-outline-secondary px-3 py-2">
                Tiếp tục mua sắm
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
