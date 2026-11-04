<?php
// don-hang.php - Danh sách đơn hàng của tôi
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/orders.php';

if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'don-hang.php';
    header('Location: login.php');
    exit;
}

$user = current_user_row($conn);
$orders = [];
if ($user) {
    $st = $conn->prepare("
        SELECT d.*, (SELECT COUNT(*) FROM chi_tiet_don_hang c WHERE c.DH_Ma = d.DH_Ma) AS so_dong
        FROM don_hang d
        WHERE d.ND_Ma = ?
        ORDER BY d.DH_NgayDat DESC, d.DH_Ma DESC
    ");
    $st->execute([(int)$user['ND_Ma']]);
    $orders = $st->fetchAll(PDO::FETCH_ASSOC);
}

$message = $_SESSION['order_message'] ?? '';
unset($_SESSION['order_message']);

$page_title = 'Đơn hàng của tôi';
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-1 bg-[#FBF8F5] text-[#2D2319]">
  <div class="max-w-5xl mx-auto px-6 py-8">

    <nav class="flex items-center gap-2 text-xs text-[#7A6F64] mb-5">
      <a href="index.php" class="hover:text-[#8C5835]">Trang chủ</a><span>/</span>
      <span class="text-[#2D2319] font-semibold">Đơn hàng của tôi</span>
    </nav>

    <h1 class="text-2xl font-bold font-serif mb-5">Đơn hàng của tôi</h1>

    <?php if ($message !== ''): ?>
      <div class="mb-5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl p-3 text-sm"><?= e_html($message) ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
      <div class="bg-white border border-[#E7E0D8] rounded-2xl p-10 text-center text-[#7A6F64]">
        <p class="mb-4">Bạn chưa có đơn hàng nào.</p>
        <a href="sanpham.php" class="inline-block bg-[#8C5835] text-white px-5 py-2.5 rounded-xl font-bold text-xs">Mua sắm ngay</a>
      </div>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($orders as $o):
            $cancelled = $o['DH_TrangThai'] === ORDER_STATUS_CANCEL;
            $needPay   = !$cancelled && $o['DH_TrangThaiThanhToan'] !== PAY_STATUS_PAID;
        ?>
          <div class="bg-white border border-[#E7E0D8] rounded-2xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
              <p class="font-bold">Đơn #<?= (int)$o['DH_Ma'] ?>
                <span class="text-xs font-normal text-[#7A6F64]">· <?= e_html(date('d/m/Y H:i', strtotime($o['DH_NgayDat']))) ?></span>
              </p>
              <p class="text-xs text-[#7A6F64]"><?= (int)$o['so_dong'] ?> sản phẩm · Giao đến: <?= e_html($o['DH_TenNguoiNhan']) ?></p>
              <div class="flex flex-wrap gap-2 pt-1">
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border <?= status_badge_class($o['DH_TrangThaiThanhToan'] ?? '') ?>">
                  <?= e_html($o['DH_TrangThaiThanhToan'] ?? '') ?>
                </span>
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border <?= status_badge_class($o['DH_TrangThai']) ?>">
                  <?= e_html($o['DH_TrangThai']) ?>
                </span>
              </div>
            </div>

            <div class="flex items-center gap-4 md:flex-col md:items-end">
              <span class="text-lg font-black text-[#8C5835]"><?= format_vnd($o['DH_TongTien']) ?></span>
              <div class="flex gap-2">
                <?php if ($needPay): ?>
                  <a href="thanh-toan-qr.php?id=<?= (int)$o['DH_Ma'] ?>" class="bg-[#8C5835] hover:bg-[#724324] text-white text-xs font-bold px-4 py-2 rounded-xl transition">Thanh toán QR</a>
                <?php endif; ?>
                <a href="chi-tiet-don-hang.php?id=<?= (int)$o['DH_Ma'] ?>" class="border border-[#E7E0D8] hover:bg-[#FBF8F5] text-xs font-bold px-4 py-2 rounded-xl transition">Xem chi tiết</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>