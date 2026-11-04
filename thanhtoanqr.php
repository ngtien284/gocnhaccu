<?php
// thanh-toan-qr.php - Trang thanh toán đơn hàng bằng mã QR (VietQR)
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/orders.php';

if (empty($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'don-hang.php';
    header('Location: login.php');
    exit;
}

$user = current_user_row($conn);
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user || !$orderId) {
    header('Location: don-hang.php');
    exit;
}

// Chỉ cho xem đơn của chính mình
$st = $conn->prepare("SELECT * FROM don_hang WHERE DH_Ma = ? AND ND_Ma = ? LIMIT 1");
$st->execute([$orderId, (int)$user['ND_Ma']]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Không tìm thấy đơn hàng.');
}

// Xử lý nút bấm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'paid_claim'
        && $order['DH_TrangThaiThanhToan'] === PAY_STATUS_WAITING
        && $order['DH_TrangThai'] !== ORDER_STATUS_CANCEL) {
        $conn->prepare("UPDATE don_hang SET DH_TrangThaiThanhToan = ? WHERE DH_Ma = ?")
             ->execute([PAY_STATUS_CONFIRM, $orderId]);
        $_SESSION['order_message'] = 'Cảm ơn bạn! Shop sẽ kiểm tra giao dịch và xác nhận đơn hàng sớm nhất.';
    } elseif ($action === 'cancel' && $order['DH_TrangThaiThanhToan'] !== PAY_STATUS_PAID) {
        if (cancel_order($conn, $orderId)) {
            $_SESSION['order_message'] = 'Đã hủy đơn hàng #' . $orderId . '.';
            header('Location: don-hang.php');
            exit;
        }
    }
    header('Location: thanh-toan-qr.php?id=' . $orderId);
    exit;
}

$items       = fetch_order_items($conn, $orderId);
$amount      = (int)round((float)$order['DH_TongTien']);
$content     = pay_content($orderId);
$qrUrl       = pay_qr_url($amount, $content);
$isPaid      = $order['DH_TrangThaiThanhToan'] === PAY_STATUS_PAID;
$isConfirm   = $order['DH_TrangThaiThanhToan'] === PAY_STATUS_CONFIRM;
$isCancelled = $order['DH_TrangThai'] === ORDER_STATUS_CANCEL;

$message = $_SESSION['order_message'] ?? '';
unset($_SESSION['order_message']);

$page_title = 'Thanh toán đơn hàng #' . $orderId;
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-1 bg-[#FBF8F5] text-[#2D2319]">
  <div class="max-w-5xl mx-auto px-6 py-8">

    <nav class="flex items-center gap-2 text-xs text-[#7A6F64] mb-5">
      <a href="index.php" class="hover:text-[#8C5835]">Trang chủ</a><span>/</span>
      <a href="don-hang.php" class="hover:text-[#8C5835]">Đơn hàng của tôi</a><span>/</span>
      <span class="text-[#2D2319] font-semibold">Đơn #<?= (int)$orderId ?></span>
    </nav>

    <?php if ($message !== ''): ?>
      <div class="mb-5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl p-3 text-sm"><?= e_html($message) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

      <!-- Cột trái: mã QR / trạng thái -->
      <div class="lg:col-span-3 bg-white border border-[#E7E0D8] rounded-2xl p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4 pb-3 border-b border-[#E7E0D8]">
          <h1 class="text-xl font-bold font-serif">Đơn hàng #<?= (int)$orderId ?></h1>
          <span class="text-xs font-bold px-3 py-1 rounded-full border <?= status_badge_class($isCancelled ? ORDER_STATUS_CANCEL : $order['DH_TrangThaiThanhToan']) ?>">
            <?= e_html($isCancelled ? ORDER_STATUS_CANCEL : $order['DH_TrangThaiThanhToan']) ?>
          </span>
        </div>

        <?php if ($isCancelled): ?>
          <p class="text-sm text-[#7A6F64]">Đơn hàng này đã được hủy. Bạn có thể đặt lại từ giỏ hàng.</p>

        <?php elseif ($isPaid): ?>
          <div class="text-center py-8">
            <div class="text-5xl mb-3">✅</div>
            <h2 class="text-lg font-bold mb-1">Thanh toán thành công</h2>
            <p class="text-sm text-[#7A6F64]">Shop đã nhận được tiền và sẽ chuẩn bị giao hàng cho bạn.</p>
          </div>

        <?php else: ?>
          <p class="text-sm text-[#7A6F64] mb-4">
            Mở app ngân hàng, chọn <strong>Quét mã QR</strong> và quét mã bên dưới. Số tiền và nội dung đã được điền sẵn.
          </p>

          <div class="flex flex-col sm:flex-row gap-6 items-center sm:items-start">
            <img src="<?= e_html($qrUrl) ?>" alt="Mã QR thanh toán đơn #<?= (int)$orderId ?>"
                 class="w-64 h-auto rounded-xl border border-[#E7E0D8] bg-white" loading="eager">

            <dl class="text-sm space-y-3 w-full">
              <div>
                <dt class="text-xs text-[#7A6F64]">Ngân hàng</dt>
                <dd class="font-bold"><?= e_html(PAY_BANK_ID) ?></dd>
              </div>
              <div>
                <dt class="text-xs text-[#7A6F64]">Số tài khoản</dt>
                <dd class="font-bold flex items-center gap-2">
                  <span id="acc-no"><?= e_html(PAY_ACCOUNT_NO) ?></span>
                  <button type="button" data-copy="acc-no" class="copy-btn text-[11px] text-[#8C5835] border border-[#E7E0D8] rounded px-2 py-0.5 hover:bg-[#FBF8F5]">Sao chép</button>
                </dd>
              </div>
              <div>
                <dt class="text-xs text-[#7A6F64]">Chủ tài khoản</dt>
                <dd class="font-bold"><?= e_html(PAY_ACCOUNT_NAME) ?></dd>
              </div>
              <div>
                <dt class="text-xs text-[#7A6F64]">Số tiền</dt>
                <dd class="font-black text-lg text-[#8C5835] flex items-center gap-2">
                  <span id="amount-val"><?= format_vnd($amount) ?></span>
                </dd>
              </div>
              <div>
                <dt class="text-xs text-[#7A6F64]">Nội dung chuyển khoản (bắt buộc)</dt>
                <dd class="font-bold flex items-center gap-2">
                  <span id="pay-content"><?= e_html($content) ?></span>
                  <button type="button" data-copy="pay-content" class="copy-btn text-[11px] text-[#8C5835] border border-[#E7E0D8] rounded px-2 py-0.5 hover:bg-[#FBF8F5]">Sao chép</button>
                </dd>
              </div>
            </dl>
          </div>

          <p class="text-xs text-[#7A6F64] mt-4">
            Vui lòng giữ nguyên nội dung chuyển khoản để shop đối chiếu đúng đơn của bạn.
          </p>

          <?php if ($isConfirm): ?>
            <div class="mt-5 bg-sky-50 text-sky-800 border border-sky-200 rounded-xl p-3 text-sm">
              Bạn đã báo chuyển khoản. Shop đang kiểm tra giao dịch, vui lòng chờ trong giây lát.
            </div>
          <?php else: ?>
            <form method="POST" class="mt-5 flex flex-wrap gap-3">
              <input type="hidden" name="action" value="paid_claim">
              <button type="submit" class="bg-[#8C5835] hover:bg-[#724324] text-white font-bold text-sm px-5 py-2.5 rounded-xl shadow transition">
                Tôi đã chuyển khoản
              </button>
            </form>
          <?php endif; ?>

          <form method="POST" class="mt-3" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?');">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="text-xs text-[#7A6F64] hover:text-red-600 underline bg-transparent border-0 cursor-pointer">Hủy đơn hàng</button>
          </form>
        <?php endif; ?>
      </div>

      <!-- Cột phải: tóm tắt đơn -->
      <div class="lg:col-span-2 bg-white border border-[#E7E0D8] rounded-2xl p-6 shadow-sm h-fit">
        <h2 class="font-bold font-serif mb-3 pb-2 border-b border-[#E7E0D8]">Sản phẩm trong đơn</h2>
        <ul class="space-y-3 text-sm mb-4">
          <?php foreach ($items as $it): ?>
            <li class="flex gap-3">
              <?php if ($it['image'] !== ''): ?>
                <img src="<?= e_html($it['image']) ?>" alt="" class="w-14 h-14 object-contain rounded-lg border border-[#E7E0D8] bg-[#FBF8F5] p-1 shrink-0">
              <?php endif; ?>
              <div class="flex-1 min-w-0">
                <p class="font-semibold leading-snug"><?= e_html($it['name']) ?></p>
                <p class="text-xs text-[#7A6F64]"><?= (int)$it['qty'] ?> × <?= format_vnd($it['price']) ?></p>
              </div>
              <span class="font-bold text-[#8C5835] shrink-0"><?= format_vnd($it['total']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="flex justify-between items-baseline pt-3 border-t border-[#E7E0D8]">
          <span class="font-bold text-sm">Tổng thanh toán</span>
          <span class="text-xl font-black text-[#8C5835]"><?= format_vnd($order['DH_TongTien']) ?></span>
        </div>

        <div class="mt-5 pt-4 border-t border-[#E7E0D8] text-xs text-[#7A6F64] space-y-1">
          <p><strong class="text-[#2D2319]">Người nhận:</strong> <?= e_html($order['DH_TenNguoiNhan']) ?></p>
          <p><strong class="text-[#2D2319]">Điện thoại:</strong> <?= e_html($order['DH_SoDTNhan']) ?></p>
          <p><strong class="text-[#2D2319]">Địa chỉ:</strong>
            <?= e_html($order['DH_DiaChiNhan_SoNha'] . ', ' . $order['DH_DiaChiNhan_PhuongXa'] . ', ' . $order['DH_DiaChiNhan_TP']) ?></p>
          <p><strong class="text-[#2D2319]">Ngày đặt:</strong> <?= e_html(date('d/m/Y H:i', strtotime($order['DH_NgayDat']))) ?></p>
        </div>

        <a href="don-hang.php" class="block text-center mt-5 text-xs font-bold text-[#8C5835] hover:underline">← Xem tất cả đơn hàng của tôi</a>
      </div>
    </div>
  </div>
</main>

<script>
  // Nút sao chép số tài khoản / nội dung chuyển khoản
  document.querySelectorAll('.copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = document.getElementById(btn.getAttribute('data-copy'));
      if (!el) return;
      var text = el.textContent.trim();
      var done = function () {
        var old = btn.textContent;
        btn.textContent = 'Đã chép';
        setTimeout(function () { btn.textContent = old; }, 1500);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function () {});
      }
    });
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>