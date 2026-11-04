<?php
// chi-tiet-don-hang.php - Chi tiết một đơn hàng
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/orders.php';

if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'don-hang.php';
    header('Location: login.php');
    exit;
}

$user    = current_user_row($conn);
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user || !$orderId) {
    header('Location: don-hang.php');
    exit;
}

// Khách chỉ xem được đơn của mình; quản trị viên xem được mọi đơn
if (is_admin_user()) {
    $st = $conn->prepare("SELECT * FROM don_hang WHERE DH_Ma = ? LIMIT 1");
    $st->execute([$orderId]);
} else {
    $st = $conn->prepare("SELECT * FROM don_hang WHERE DH_Ma = ? AND ND_Ma = ? LIMIT 1");
    $st->execute([$orderId, (int)$user['ND_Ma']]);
}
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}

$items     = fetch_order_items($conn, $orderId);
$cancelled = $order['DH_TrangThai'] === ORDER_STATUS_CANCEL;
$needPay   = !$cancelled && $order['DH_TrangThaiThanhToan'] !== PAY_STATUS_PAID;

$page_title = 'Chi tiết đơn hàng #' . $orderId;
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-1 bg-[#FBF8F5] text-[#2D2319]">
  <div class="max-w-4xl mx-auto px-6 py-8">

    <nav class="flex items-center gap-2 text-xs text-[#7A6F64] mb-5">
      <a href="index.php" class="hover:text-[#8C5835]">Trang chủ</a><span>/</span>
      <a href="don-hang.php" class="hover:text-[#8C5835]">Đơn hàng của tôi</a><span>/</span>
      <span class="text-[#2D2319] font-semibold">Đơn #<?= (int)$orderId ?></span>
    </nav>

    <div class="bg-white border border-[#E7E0D8] rounded-2xl p-6 shadow-sm mb-5">
      <div class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-4 border-b border-[#E7E0D8]">
        <div>
          <h1 class="text-xl font-bold font-serif">Đơn hàng #<?= (int)$orderId ?></h1>
          <p class="text-xs text-[#7A6F64]">Đặt lúc <?= e_html(date('d/m/Y H:i', strtotime($order['DH_NgayDat']))) ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
          <span class="text-xs font-bold px-3 py-1 rounded-full border <?= status_badge_class($order['DH_TrangThaiThanhToan'] ?? '') ?>"><?= e_html($order['DH_TrangThaiThanhToan'] ?? '') ?></span>
          <span class="text-xs font-bold px-3 py-1 rounded-full border <?= status_badge_class($order['DH_TrangThai']) ?>"><?= e_html($order['DH_TrangThai']) ?></span>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4 text-sm mb-2">
        <div>
          <p class="text-xs text-[#7A6F64]">Người nhận</p>
          <p class="font-semibold"><?= e_html($order['DH_TenNguoiNhan']) ?></p>
          <p class="text-[#7A6F64]"><?= e_html($order['DH_SoDTNhan']) ?></p>
        </div>
        <div>
          <p class="text-xs text-[#7A6F64]">Địa chỉ nhận hàng</p>
          <p class="font-semibold"><?= e_html($order['DH_DiaChiNhan_SoNha'] . ', ' . $order['DH_DiaChiNhan_PhuongXa'] . ', ' . $order['DH_DiaChiNhan_TP']) ?></p>
        </div>
        <div>
          <p class="text-xs text-[#7A6F64]">Phương thức thanh toán</p>
          <p class="font-semibold"><?= e_html($order['DH_PhuongThucThanhToan'] === 'QR' ? 'Chuyển khoản QR' : $order['DH_PhuongThucThanhToan']) ?></p>
        </div>
        <div>
          <p class="text-xs text-[#7A6F64]">Nội dung chuyển khoản</p>
          <p class="font-semibold"><?= e_html(pay_content((int)$orderId)) ?></p>
        </div>
      </div>
    </div>

    <div class="bg-white border border-[#E7E0D8] rounded-2xl p-6 shadow-sm">
      <h2 class="font-bold font-serif mb-3 pb-2 border-b border-[#E7E0D8]">Sản phẩm</h2>
      <ul class="divide-y divide-[#E7E0D8]">
        <?php foreach ($items as $it): ?>
          <li class="py-3 flex gap-4 items-center">
            <?php if ($it['image'] !== ''): ?>
              <img src="<?= e_html($it['image']) ?>" alt="" class="w-16 h-16 object-contain rounded-lg border border-[#E7E0D8] bg-[#FBF8F5] p-1 shrink-0">
            <?php endif; ?>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-sm"><?= e_html($it['name']) ?></p>
              <p class="text-xs text-[#7A6F64]"><?= (int)$it['qty'] ?> × <?= format_vnd($it['price']) ?></p>
            </div>
            <span class="font-bold text-[#8C5835]"><?= format_vnd($it['total']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="flex justify-between items-baseline pt-4 mt-2 border-t border-[#E7E0D8]">
        <span class="font-bold">Tổng thanh toán</span>
        <span class="text-2xl font-black text-[#8C5835]"><?= format_vnd($order['DH_TongTien']) ?></span>
      </div>

      <div class="mt-5 flex flex-wrap gap-3">
        <?php if ($needPay && (int)$order['ND_Ma'] === (int)$user['ND_Ma']): ?>
          <a href="thanh-toan-qr.php?id=<?= (int)$orderId ?>" class="bg-[#8C5835] hover:bg-[#724324] text-white text-sm font-bold px-5 py-2.5 rounded-xl transition">Thanh toán bằng QR</a>
        <?php endif; ?>
        <a href="don-hang.php" class="border border-[#E7E0D8] hover:bg-[#FBF8F5] text-sm font-bold px-5 py-2.5 rounded-xl transition">← Danh sách đơn hàng</a>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>