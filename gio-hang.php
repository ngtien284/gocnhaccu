<?php
// gio-hang.php
session_start();

require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['gio_hang'])) {
    $_SESSION['gio_hang'] = [];
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'add'
) {
    $productId = (int)($_POST['product_id'] ?? 0);
    // Nhận loại sản phẩm ('phukien' hoặc mặc định là 'nhaccu')
    $productType = strtolower(trim($_POST['product_type'] ?? 'nhaccu'));
    // Lấy số lượng người dùng chọn từ trang chi tiết (mặc định là 1 nếu không truyền)
    $requestedQty = max(1, (int)($_POST['so_luong'] ?? 1));

    // Phân nhánh câu lệnh SQL tùy theo loại sản phẩm để tránh lỗi trùng ID
    if ($productType === 'phukien') {
        $sql = "
            SELECT
                pk.PK_Ma AS NC_Ma,
                pk.PK_Ten AS NC_Ten,
                pk.PK_GiaBan AS NC_GiaBan,
                pk.PK_GiaKhuyenMai AS NC_GiaKhuyenMai,
                pk.PK_SoLuongTon AS NC_SoLuongTon,
                pk.PK_HinhAnh AS NC_HinhAnh,
                pk.PK_MoTa AS NC_MoTa,
                th.TH_Ten
            FROM phukien AS pk
            LEFT JOIN thuonghieu AS th ON pk.TH_Ma = th.TH_Ma
            WHERE pk.PK_Ma = :id
              AND pk.PK_TrangThai = 1
        ";
    } else {
        $sql = "
            SELECT
                nc.NC_Ma,
                nc.NC_Ten,
                nc.NC_GiaBan,
                nc.NC_GiaKhuyenMai,
                nc.NC_SoLuongTon,
                nc.NC_HinhAnh,
                nc.NC_MoTa,
                th.TH_Ten
            FROM nhaccu AS nc
            INNER JOIN thuonghieu AS th
                ON nc.TH_Ma = th.TH_Ma
            WHERE nc.NC_Ma = :id
              AND nc.NC_TrangThai = 1
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['cart_message'] = 'Không tìm thấy sản phẩm.';
    } elseif ((int)$product['NC_SoLuongTon'] <= 0) {
        $_SESSION['cart_message'] = 'Sản phẩm đã hết hàng.';
    } else {
        $id = (int)$product['NC_Ma'];
        
        // Tạo khóa duy nhất (cartKey) để phân biệt nhạc cụ và phụ kiện có cùng ID (ví dụ: nhaccu_1 khác phukien_1)
        $cartKey = $productType . '_' . $id;

        $currentQty = (int)($_SESSION['gio_hang'][$cartKey]['so_luong'] ?? 0);
        $newQty = $currentQty + $requestedQty;
        $stock = (int)$product['NC_SoLuongTon'];

        if ($newQty > $stock) {
            $_SESSION['cart_message'] = 'Số lượng đặt vượt quá số lượng trong kho.';
        } else {
            $image = $product['NC_HinhAnh'] ?? '';

            if ($image !== '' && !preg_match('~^https?://~i', $image)) {
                $image = 'images/' . ltrim($image, '/');
            }

            $price = (
                $product['NC_GiaKhuyenMai'] !== null
                && $product['NC_GiaKhuyenMai'] > 0
            )
                ? $product['NC_GiaKhuyenMai']
                : $product['NC_GiaBan'];

            $_SESSION['gio_hang'][$cartKey] = [
                'ma'          => $id,
                'loai'        => $productType, // Lưu thêm phân loại để dễ quản lý
                'ten'         => $product['NC_Ten'],
                'thuong_hieu' => $product['TH_Ten'] ?? '',
                'mo_ta'       => $product['NC_MoTa'] ?? '',
                'gia'         => (float)$price,
                'hinh_anh'    => $image,
                'so_luong'    => $newQty,
                'ton_kho'     => $stock
            ];

            $_SESSION['cart_message'] = 'Đã thêm sản phẩm vào giỏ hàng.';
        }
    }

    header('Location: gio-hang.php');
    exit;
}

// Kiểm tra hàm format tiền tệ nếu chưa có
if (!function_exists('format_vnd')) {
    function format_vnd($number) {
        return number_format($number, 0, ',', '.') . '₫';
    }
}

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['user']) && !empty($_SESSION['user']);

// XỬ LÝ KHI BẤM NÚT THANH TOÁN (Phải đặt trước khi gọi header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    if (!$is_logged_in) {
        // Lưu lại trang giỏ hàng để sau khi đăng nhập xong nhảy ngược về đây
        $_SESSION['redirect_after_login'] = 'gio-hang.php';
        header("Location: dang-nhap.php");
        exit;
    } else {
        // Xử lý đặt hàng thành công
        echo "<script>alert('Đặt hàng thành công! Cảm ơn bạn đã mua sắm tại Harmony.'); window.location.href='index.php';</script>";
        exit;
    }
}

// Gọi file header giao diện sau khi đã xử lý xong chuyển hướng (header redirect)
$page_title = "Giỏ Hàng & Thẩm Định Đơn Hàng";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/app.php';


$cart_items = [];

foreach ($_SESSION['gio_hang'] ?? [] as $item) {
    $cart_items[] = [
        'id'       => $item['ma'],
        'brand'    => $item['thuong_hieu'] ?? '',
        'name'     => $item['ten'],
        'detail'   => $item['mo_ta'] ?? '',
        'price'    => $item['gia'],
        'qty'      => $item['so_luong'],
        'stock'    => ((int)$item['ton_kho'] > 0)
                      ? 'Còn hàng'
                      : 'Hết hàng',
        'image'    => $item['hinh_anh'],
    ];
}
  // Tính hóa đơn theo sản phẩm thực tế trong giỏ hàng
$subtotal = 0;

foreach ($cart_items as $item) {
    $subtotal += (float)$item['price'] * (int)$item['qty'];
}

// Giữ mức giảm giá 500.000đ như giao diện cũ
$discount = !empty($cart_items) ? 500000 : 0;

// Phí vận chuyển miễn phí
$shipping = 0;

// Tổng thanh toán không được âm
$total = max(0, $subtotal - $discount + $shipping);
?>

<main class="flex-1 bg-[#FBF8F5] text-[#2D2319] font-sans">
  <?php if (!empty($_SESSION['cart_message'])): ?>
    <div class="max-w-7xl mx-auto px-6 pt-4">
        <div class="bg-emerald-50 text-emerald-800
                    border border-emerald-200 rounded-xl p-3">
            <?= htmlspecialchars($_SESSION['cart_message']) ?>
        </div>
    </div>
    <?php unset($_SESSION['cart_message']); ?>
<?php endif; ?>
  <!-- Breadcrumb & Top Indicator -->
  <section class="max-w-7xl mx-auto px-6 pt-5 pb-3">
    <div class="flex items-center justify-between text-xs text-[#7A6F64]">
      <div class="flex items-center gap-2">
        <a href="index.php" class="hover:text-[#8C5835]">Trang chủ</a>
        <span>/</span>
        <span class="text-[#2D2319] font-semibold">Giỏ hàng của bạn (<?= count($cart_items) ?> món thiết bị)</span>
      </div>
      <div class="flex items-center gap-1.5 text-emerald-800 font-semibold bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
        <span>Mã hóa thanh toán cấp Studio 256-Bit SSL</span>
      </div>
    </div>
  </section>

  <!-- BANNER TỰ ĐỘNG ĐỦ ĐIỀU KIỆN FREESHIP -->
  <section class="max-w-7xl mx-auto px-6 mb-6">
    <div class="bg-gradient-to-r from-[#2D2319] via-[#4A382C] to-[#5F371D] rounded-2xl text-white p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-md border border-[#754728]/30">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-[#8C5835] flex items-center justify-center shrink-0 shadow">
          <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
        </div>
        <div>
          <h2 class="text-sm font-extrabold text-white">Đơn hàng của bạn đã đủ điều kiện nhận Miễn Phí Vận Chuyển Hỏa Tốc & Bảo Hành 2 Năm Sweetwater Toàn Diện!</h2>
          <p class="text-xs text-[#D4C7BA]">Bao gồm dịch vụ kiểm tra cổng tín hiệu và cấu hình tương thích bởi Kỹ Sư Chuyên Trách trước khi niêm phong thùng.</p>
        </div>
      </div>
      <span class="bg-white/10 border border-white/20 text-[#E0D2C4] text-xs font-bold px-3 py-1.5 rounded-xl whitespace-nowrap">
        ⏱ DỰ KIẾN GIAO: NGÀY MAI
      </span>
    </div>
  </section>

  <!-- KHỐI CHÍNH: GIỎ HÀNG (LỚN) + TÓM TẮT ĐƠN HÀNG (PHẢI) -->
  <section class="max-w-7xl mx-auto px-6 mb-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- CỘT BÊN TRÁI: DANH SÁCH THIẾT BỊ ĐÃ CHỌN (8 CỘT) -->
      <div class="lg:col-span-8 space-y-6">
        
        <div class="flex items-center justify-between pb-2 border-b border-[#E7E0D8]">
          <h2 class="text-xl font-bold font-serif text-[#2D2319] flex items-center gap-2">
            Thiết Bị Đã Chọn
            <span class="bg-[#8C5835] text-white text-xs px-2.5 py-0.5 rounded-full font-sans font-bold"><?= count($cart_items) ?></span>
          </h2>
          <button class="text-xs text-[#7A6F64] hover:text-red-600 transition flex items-center gap-1">
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Xóa tất cả
          </button>
        </div>

        <!-- LIST SẢN PHẨM GIỎ HÀNG -->
        <div class="space-y-4">
          <?php foreach ($cart_items as $item): ?>
            <div class="bg-white rounded-2xl border border-[#E7E0D8] p-5 shadow-sm hover:border-[#8C5835]/40 transition">
              <div class="flex flex-col sm:flex-row items-start gap-4">
                
                <!-- Ảnh thiết bị -->
                <div class="w-24 h-24 rounded-xl bg-[#FBF8F5] border border-[#E7E0D8] p-2 shrink-0 flex items-center justify-center">
                  <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full h-full object-contain"/>
                </div>

                <!-- Chi tiết sản phẩm -->
                <div class="flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <span class="text-[10px] font-bold text-[#8C5835] uppercase tracking-wider block mb-0.5"><?= htmlspecialchars($item['brand']) ?></span>
                      <h3 class="font-bold text-sm text-[#2D2319] leading-snug"><?= htmlspecialchars($item['name']) ?></h3>
                    </div>
                    <span class="text-base font-black text-[#8C5835] shrink-0"><?= format_vnd($item['price']) ?></span>
                  </div>

                  <p class="text-xs text-[#7A6F64] leading-relaxed mt-1 mb-2"><?= htmlspecialchars($item['detail']) ?></p>

                  <!-- Badge quà tặng hoặc chi tiết đặc biệt -->
                  <?php if (!empty($item['gift'])): ?>
                    <div class="bg-[#EFE7DF] border border-[#E0D2C4] rounded-lg p-2 text-[11px] font-bold text-[#8C5835] mb-2 flex items-center gap-1.5">
                      <i data-lucide="gift" class="w-3.5 h-3.5 shrink-0"></i>
                      <span><?= htmlspecialchars($item['gift']) ?></span>
                    </div>
                    <p class="text-[10px] text-[#7A6F64] mb-2"><?= htmlspecialchars($item['sub_detail']) ?></p>
                  <?php endif; ?>

                  <!-- Addon Sales Engineer tư vấn (nếu có) -->
                  <?php if (!empty($item['has_addon'])): ?>
                    <div class="bg-[#FBF8F5] border border-[#E7E0D8] rounded-xl p-3 mb-3 text-xs">
                      <label class="flex items-start gap-2.5 font-bold text-[#2D2319] cursor-pointer">
                        <input type="checkbox" checked class="rounded text-[#8C5835] focus:ring-[#8C5835] mt-0.5"/>
                        <span><?= htmlspecialchars($item['addon_text']) ?></span>
                      </label>
                      <p class="text-[11px] text-[#7A6F64] ml-6 mt-1 leading-relaxed">
                        <?= htmlspecialchars($item['addon_desc']) ?>
                      </p>
                    </div>
                  <?php endif; ?>

                  <!-- Bottom Toolbar sản phẩm -->
                  <div class="flex items-center justify-between pt-3 border-t border-[#E7E0D8]/60 text-xs">
                    <div class="flex items-center gap-3">
                      <div class="flex items-center border border-[#E7E0D8] rounded-lg bg-[#FBF8F5]">
                        <button class="px-2.5 py-1 text-[#7A6F64] hover:text-[#2D2319] font-bold">-</button>
                        <span class="px-3 py-1 font-bold text-[#2D2319] text-xs"><?= $item['qty'] ?></span>
                        <button class="px-2.5 py-1 text-[#7A6F64] hover:text-[#2D2319] font-bold">+</button>
                      </div>
                      <button class="text-[#7A6F64] hover:text-[#8C5835] flex items-center gap-1 font-medium">
                        <i data-lucide="heart" class="w-3.5 h-3.5"></i> Lưu lại sau
                      </button>
                      <button class="text-[#7A6F64] hover:text-red-600 flex items-center gap-1 font-medium">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Xóa
                      </button>
                    </div>

                    <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                      ✓ <?= htmlspecialchars($item['stock']) ?>
                    </span>
                  </div>

                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- CARD KHUNG SALES ENGINEER HỖ TRỢ TRỰC TIẾP GIỎ HÀNG -->
        <div class="bg-gradient-to-r from-[#2D2319] via-[#4A382C] to-[#5F371D] text-white rounded-2xl p-5 shadow-lg border border-[#754728]/30 flex flex-col sm:flex-row items-center gap-5">
          <div class="relative shrink-0">
            <div class="w-16 h-16 rounded-2xl overflow-hidden border-2 border-[#B8977E]/50 shadow">
              <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80" alt="Minh Tuấn" class="w-full h-full object-cover"/>
            </div>
            <span class="absolute -bottom-1 -right-1 bg-emerald-500 w-4 h-4 rounded-full border-2 border-[#2D2319]" title="Đang online"></span>
          </div>

          <div class="flex-1 text-xs">
            <div class="flex items-center gap-2 mb-1">
              <span class="bg-[#8C5835] text-white text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-full">YOUR ASSIGNED SALES ENGINEER</span>
              <span class="text-emerald-400 font-semibold text-[11px]">🟢 Đang online sẵn sàng hỗ trợ</span>
            </div>
            <h4 class="text-base font-bold text-white font-serif mb-1">Minh Tuấn <span class="text-xs font-normal text-[#D4C7BA]">| Ext: 1420</span></h4>
            <p class="text-[#D4C7BA] leading-relaxed italic mb-3">
              "Chào bạn! Tôi phụ trách đơn hàng phòng thu này của bạn. Tôi nhận thấy bạn kết hợp Shure SM7B cùng Apollo Twin X USB. Tôi sẽ đích thân kiểm tra độ kháng trở trùng khớp và cân chỉnh tín hiệu dây cáp Mogami trước khi đóng gói xuất kho nhé!"
            </p>

            <div class="flex flex-wrap items-center gap-3">
              <a href="tel:19008888" class="bg-white/10 hover:bg-white/20 text-[#EFE7DF] border border-white/20 px-3.5 py-1.5 rounded-xl font-bold flex items-center gap-1.5 transition">
                <i data-lucide="phone-call" class="w-3.5 h-3.5"></i> Call máy lẻ 1420
              </a>
              <a href="#" class="bg-[#8C5835] hover:bg-[#724324] text-white px-3.5 py-1.5 rounded-xl font-bold flex items-center gap-1.5 shadow transition">
                <i data-lucide="message-square" class="w-3.5 h-3.5"></i> Nhắn tin trực tiếp với Minh Tuấn
              </a>
            </div>
          </div>
        </div>

        <!-- 3 KHỐI CAM KẾT PHÍA DƯỚI GIỎ HÀNG -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div class="bg-white p-4 rounded-2xl border border-[#E7E0D8] shadow-sm flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-[#EFE7DF] text-[#8C5835] flex items-center justify-center shrink-0 font-bold">
              <i data-lucide="headphones" class="w-5 h-5"></i>
            </div>
            <div>
              <h5 class="font-bold text-[#2D2319]">SweetCare Support</h5>
              <p class="text-[11px] text-[#7A6F64] mt-0.5">Hỗ trợ kỹ thuật cấu hình DAW miễn phí trọn đời sản phẩm.</p>
            </div>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-[#E7E0D8] shadow-sm flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-[#EFE7DF] text-[#8C5835] flex items-center justify-center shrink-0 font-bold">
              <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
            </div>
            <div>
              <h5 class="font-bold text-[#2D2319]">30-Day Hassle-Free</h5>
              <p class="text-[11px] text-[#7A6F64] mt-0.5">Đổi trả 30 ngày an tâm không sản phẩm nếu chưa ưng ý âm sắc.</p>
            </div>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-[#E7E0D8] shadow-sm flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-[#EFE7DF] text-[#8C5835] flex items-center justify-center shrink-0 font-bold">
              <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
            <div>
              <h5 class="font-bold text-[#2D2319]">Total 2-Year Warranty</h5>
              <p class="text-[11px] text-[#7A6F64] mt-0.5">Sweetwater bảo hành thêm 1 năm sau khi hết bảo hành của hãng.</p>
            </div>
          </div>
        </div>

      </div>

      <!-- CỘT BÊN PHẢI: TÓM TẮT ĐƠN HÀNG (4 CỘT) -->
      <div class="lg:col-span-4">
        <div class="bg-white rounded-2xl border border-[#E7E0D8] p-6 shadow-sm sticky top-24">
          
          <h3 class="font-bold text-base text-[#2D2319] pb-3 border-b border-[#E7E0D8] mb-4 font-serif">Tóm Tắt Đơn Hàng</h3>

          <!-- Chi tiết đơn giá -->
          <div class="space-y-3 text-xs text-[#7A6F64] mb-4 pb-4 border-b border-[#E7E0D8]">
            <div class="flex justify-between items-center">
              <span>Tạm tính (<?= count($cart_items) ?> sản phẩm):</span>
              <strong class="text-[#2D2319] font-bold text-sm"><?= format_vnd($subtotal) ?></strong>
            </div>
            <div class="flex justify-between items-center">
              <span>Phí vận chuyển bảo hiểm:</span>
              <span class="text-emerald-800 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">0₫ MIỄN PHÍ</span>
            </div>
            <div class="flex justify-between items-center">
              <span>Thuế GTGT (VAT 8% đã bao gồm):</span>
              <span class="text-[#2D2319] font-semibold">Đã gồm VAT</span>
            </div>
          </div>

          <!-- Áp dụng Mã giảm giá -->
          <div class="mb-5 bg-[#FBF8F5] border border-[#E7E0D8] p-3 rounded-xl">
            <div class="flex items-center justify-between text-xs font-bold text-[#2D2319] mb-2">
              <span class="flex items-center gap-1"><i data-lucide="ticket" class="w-3.5 h-3.5 text-[#8C5835]"></i> MÃ GIẢM GIÁ PHÒNG THU</span>
              <span class="text-[#8C5835]">-500.000₫</span>
            </div>
            <div class="flex gap-2 mb-1">
              <input type="text" value="PROSTUDIO-2024" class="w-full bg-white border border-[#E7E0D8] rounded-lg px-3 py-1.5 text-xs font-mono font-bold text-[#2D2319] focus:outline-none focus:border-[#8C5835]"/>
              <button class="bg-[#8C5835] hover:bg-[#724324] text-white text-xs font-bold px-3 py-1.5 rounded-lg transition shrink-0">Đã Áp Dụng</button>
            </div>
          </div>

          <!-- Tổng tiền lớn -->
          <div class="pt-2 pb-4 border-b border-[#E7E0D8] mb-4">
            <div class="flex items-baseline justify-between mb-1">
              <span class="font-extrabold text-sm text-[#2D2319]">Tổng Thanh Toán:</span>
              <span class="text-2xl font-black text-[#8C5835]"><?= format_vnd($total) ?></span>
            </div>
            <p class="text-[10px] text-[#9E948A] text-right">Tiết kiệm 500.000₫ + Gói Plugins 32.500.000₫</p>
          </div>

          <!-- Trả góp Sweetwater Card -->
          <div class="bg-[#EFE7DF]/70 border border-[#E0D2C4] rounded-xl p-3 mb-5 text-xs">
            <div class="flex items-center justify-between font-bold text-[#2D2319] mb-1">
              <span class="flex items-center gap-1.5"><i data-lucide="credit-card" class="w-4 h-4 text-[#8C5835]"></i> SWEETWATER CARD TRẢ GÓP 0%</span>
            </div>
            <p class="text-[11px] text-[#7A6F64]">
              Chỉ từ <strong class="text-[#8C5835] font-bold text-xs">1.850.000₫/tháng</strong> trong vòng 24 tháng không lãi suất.
            </p>
            <a href="#" class="text-[10px] font-bold text-[#8C5835] hover:underline block mt-1">Xem chi tiết điều kiện phê duyệt hồ sơ ›</a>
          </div>

          <!-- Nút Đặt hàng chính -->
          <form method="POST" action="">
            <input type="hidden" name="action" value="checkout">
            <button type="submit" class="w-full bg-[#8C5835] hover:bg-[#724324] text-white font-bold text-sm py-3.5 rounded-xl shadow-lg shadow-[#8C5835]/30 transition flex items-center justify-center gap-2 mb-3">
              <i data-lucide="lock" class="w-4 h-4"></i>
              <span>TIẾN HÀNH THANH TOÁN AN TOÀN</span>
            </button>
          </form>

          <!-- Nút thanh toán QR/PDF phụ -->
          <div class="space-y-2 mb-5">
            <button class="w-full bg-white border border-[#E7E0D8] hover:bg-[#FBF8F5] text-[#2D2319] font-bold text-xs py-2.5 rounded-xl transition flex items-center justify-center gap-2">
              <i data-lucide="qr-code" class="w-4 h-4 text-[#8C5835]"></i>
              <span>Thanh Toán Qua Chuyển Khoản QR-Code Studio</span>
            </button>
            <button class="w-full text-center text-[11px] text-[#7A6F64] hover:text-[#8C5835] font-semibold py-1">
              📄 Yêu cầu gửi báo giá PDF cho doanh nghiệp
            </button>
          </div>

          <!-- Cam kết nhỏ footer side -->
          <div class="pt-4 border-t border-[#E7E0D8] text-center text-[10px] text-[#7A6F64] space-y-2">
            <p>Hàng chính hãng 100% nguyên seal từ nhà phân phối ủy quyền.</p>
            
            <!-- Khung tổng đài nhanh -->
            <a href="tel:18008888" class="bg-gradient-to-r from-[#2D2319] to-[#4A382C] text-white p-3 rounded-xl block shadow transition hover:opacity-95">
              <span class="text-[9px] uppercase tracking-wider text-[#D4C7BA] block font-semibold">CẦN TƯ VẤN TRƯỚC KHU QUẸT THẺ?</span>
              <strong class="text-sm font-bold block text-[#E0D2C4] mt-0.5">1800 8888 (Miễn cước)</strong>
            </a>
          </div>

        </div>
      </div>

    </div>
  </section>

  <!-- PHỤ KIỆN THƯỜNG ĐƯỢC MUA CÙNG BỘ NÀY -->
  <section class="max-w-7xl mx-auto px-6 py-6 mb-12">
    <div class="bg-white rounded-2xl border border-[#E7E0D8] p-6 shadow-sm">
      <div class="flex items-center justify-between mb-6 pb-3 border-b border-[#E7E0D8]">
        <div>
          <h3 class="text-lg font-bold font-serif text-[#2D2319]">Phụ Kiện Thường Được Mua Cùng Bộ Này</h3>
          <p class="text-xs text-[#7A6F64]">Kỹ sư âm thanh của bạn gợi ý các thiết bị hoàn thiện chuỗi tín hiệu vocal hoàn hảo.</p>
        </div>
        <div class="flex items-center gap-2">
          <button class="w-8 h-8 rounded-full border border-[#E7E0D8] hover:bg-[#FBF8F5] text-[#7A6F64] flex items-center justify-center transition">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
          </button>
          <button class="w-8 h-8 rounded-full border border-[#E7E0D8] hover:bg-[#FBF8F5] text-[#7A6F64] flex items-center justify-center transition">
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($cross_sell as $cs): ?>
          <div class="border border-[#E7E0D8] rounded-2xl p-4 bg-white hover:border-[#8C5835]/50 hover:shadow-md transition flex flex-col justify-between group">
            <div>
              <div class="bg-[#FBF8F5] aspect-[4/3] rounded-xl overflow-hidden mb-3 border border-[#E7E0D8] flex items-center justify-center p-2">
                <img src="<?= htmlspecialchars($cs['image']) ?>" alt="<?= htmlspecialchars($cs['name']) ?>" class="w-full h-full object-contain group-hover:scale-105 transition duration-300"/>
              </div>
              <span class="text-[10px] font-bold uppercase text-[#8C5835] block mb-0.5"><?= htmlspecialchars($cs['brand']) ?></span>
              <h4 class="font-bold text-xs text-[#2D2319] mb-1 line-clamp-2 group-hover:text-[#8C5835] transition"><?= htmlspecialchars($cs['name']) ?></h4>
              <p class="text-[11px] text-[#7A6F64] leading-relaxed mb-3 line-clamp-2"><?= htmlspecialchars($cs['desc']) ?></p>
            </div>

            <div class="pt-3 border-t border-[#E7E0D8] flex items-center justify-between">
              <span class="text-sm font-black text-[#8C5835]"><?= format_vnd($cs['price']) ?></span>
              <button class="bg-[#FBF8F5] hover:bg-[#8C5835] hover:text-white border border-[#E7E0D8] text-[#2D2319] text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1 shadow-sm">
                <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                <span>Thêm Vào Giỏ</span>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>