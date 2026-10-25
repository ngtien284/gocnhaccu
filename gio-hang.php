<?php
// gio-hang.php
session_start();

require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['gio_hang'])) {
    $_SESSION['gio_hang'] = [];
}

// XỬ LÝ CẬP NHẬT HOẶC XÓA SẢN PHẨM TRONG GIỎ HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_action'])) {
    $cartKey = $_POST['cart_key'] ?? '';
    $action = $_POST['cart_action'] ?? '';

    if ($action === 'remove_all') {
        $_SESSION['gio_hang'] = [];
    } elseif (isset($_SESSION['gio_hang'][$cartKey])) {
        if ($action === 'increase') {
            $currentStock = (int)($_SESSION['gio_hang'][$cartKey]['ton_kho'] ?? 999);
            if ($_SESSION['gio_hang'][$cartKey]['so_luong'] < $currentStock) {
                $_SESSION['gio_hang'][$cartKey]['so_luong']++;
            } else {
                $_SESSION['cart_message'] = 'Số lượng đặt đã đạt tối đa tồn kho.';
            }
        } elseif ($action === 'decrease') {
            $_SESSION['gio_hang'][$cartKey]['so_luong']--;
            if ($_SESSION['gio_hang'][$cartKey]['so_luong'] <= 0) {
                unset($_SESSION['gio_hang'][$cartKey]);
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['gio_hang'][$cartKey]);
        }
    }

    header('Location: gio-hang.php');
    exit;
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
        
        // Tạo khóa duy nhất (cartKey) để phân biệt nhạc cụ và phụ kiện có cùng ID
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
                'loai'        => $productType,
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

// XỬ LÝ KHI BẤM NÚT THANH TOÁN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    if (!$is_logged_in) {
        $_SESSION['redirect_after_login'] = 'gio-hang.php';
        header("Location: login.php");
        exit;
    } else {
        echo "<script>alert('Đặt hàng thành công! Cảm ơn bạn đã mua sắm tại GOCNHACCU.'); window.location.href='index.php';</script>";
        exit;
    }
}

// Gọi file header giao diện
$page_title = "Giỏ Hàng & Thẩm Định Đơn Hàng";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/app.php';


$cart_items = [];

// Duyệt mảng session kèm theo key ($cartKey) để làm định danh cập nhật
foreach ($_SESSION['gio_hang'] ?? [] as $key => $item) {
    $cart_items[] = [
        'cart_key' => $key,
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

// === TRUY VẤN LẤY PHỤ KIỆN MUA KÈM TỪ BẢNG nhaccuphukien ===
$cross_sell = [];
try {
    $ncIds = [];
    foreach ($_SESSION['gio_hang'] ?? [] as $item) {
        if (($item['loai'] ?? 'nhaccu') === 'nhaccu') {
            $ncIds[] = (int)$item['ma'];
        }
    }
    $ncIds = array_unique($ncIds);

    if (!empty($ncIds)) {
        $placeholders = implode(',', array_fill(0, count($ncIds), '?'));
        $sqlCrossSell = "
            SELECT DISTINCT
                pk.PK_Ma AS id,
                pk.PK_Ten AS name,
                pk.PK_GiaBan AS original_price,
                pk.PK_GiaKhuyenMai AS sale_price,
                pk.PK_HinhAnh AS image,
                pk.PK_MoTa AS description,
                th.TH_Ten AS brand
            FROM nhaccuphukien AS nk
            INNER JOIN phukien AS pk ON nk.PK_Ma = pk.PK_Ma
            LEFT JOIN thuonghieu AS th ON pk.TH_Ma = th.TH_Ma
            WHERE nk.NC_Ma IN ($placeholders) AND pk.PK_TrangThai = 1
            LIMIT 4
        ";
        $stmtCS = $conn->prepare($sqlCrossSell);
        $stmtCS->execute($ncIds);
    } else {
        $sqlCrossSell = "
            SELECT 
                pk.PK_Ma AS id,
                pk.PK_Ten AS name,
                pk.PK_GiaBan AS original_price,
                pk.PK_GiaKhuyenMai AS sale_price,
                pk.PK_HinhAnh AS image,
                pk.PK_MoTa AS description,
                th.TH_Ten AS brand
            FROM phukien AS pk
            LEFT JOIN thuonghieu AS th ON pk.TH_Ma = th.TH_Ma
            WHERE pk.PK_TrangThai = 1
            LIMIT 4
        ";
        $stmtCS = $conn->prepare($sqlCrossSell);
        $stmtCS->execute();
    }

    $rows = $stmtCS->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $img = $row['image'] ?? '';
        if ($img !== '' && !preg_match('~^https?://~i', $img)) {
            $img = 'images/' . ltrim($img, '/');
        }

        $price = ($row['sale_price'] !== null && (float)$row['sale_price'] > 0)
                 ? (float)$row['sale_price']
                 : (float)$row['original_price'];

        $cross_sell[] = [
            'id'    => (int)$row['id'],
            'name'  => $row['name'],
            'brand' => $row['brand'] ?? 'Khác',
            'price' => $price,
            'image' => $img,
            'desc'  => $row['description'] ?? ''
        ];
    }
} catch (Exception $e) {
    $cross_sell = [];
}

// Tính hóa đơn theo sản phẩm thực tế trong giỏ hàng
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += (float)$item['price'] * (int)$item['qty'];
}

$discount = !empty($cart_items) ? 500000 : 0;
$shipping = 0;
$total = max(0, $subtotal - $discount + $shipping);
?>

<main class="flex-1 bg-[#FBF8F5] text-[#2D2319] font-sans">
  <?php if (!empty($_SESSION['cart_message'])): ?>
    <div class="max-w-7xl mx-auto px-6 pt-4">
        <div class="bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl p-3">
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
          <?php if (!empty($cart_items)): ?>
            <form method="POST" action="">
              <input type="hidden" name="cart_action" value="remove_all">
              <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa tất cả sản phẩm trong giỏ hàng?');" class="text-xs text-[#7A6F64] hover:text-red-600 transition flex items-center gap-1 bg-transparent border-0 cursor-pointer">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Xóa tất cả
              </button>
            </form>
          <?php endif; ?>
        </div>

        <!-- LIST SẢN PHẨM GIỎ HÀNG -->
        <div class="space-y-4">
          <?php if (empty($cart_items)): ?>
            <div class="bg-white rounded-2xl border border-[#E7E0D8] p-8 text-center text-[#7A6F64]">
              <p class="mb-4">Giỏ hàng của bạn đang trống.</p>
              <a href="index.php" class="inline-block bg-[#8C5835] text-white px-5 py-2.5 rounded-xl font-bold text-xs">Tiếp tục mua sắm</a>
            </div>
          <?php else: ?>
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

                    <!-- Bottom Toolbar sản phẩm (Tăng, giảm, xóa) -->
                    <div class="flex items-center justify-between pt-3 border-t border-[#E7E0D8]/60 text-xs">
                      <div class="flex items-center gap-3">
                        <div class="flex items-center border border-[#E7E0D8] rounded-lg bg-[#FBF8F5]">
                          <!-- Nút GIẢM số lượng -->
                          <form method="POST" action="" class="inline">
                            <input type="hidden" name="cart_key" value="<?= htmlspecialchars($item['cart_key']) ?>">
                            <input type="hidden" name="cart_action" value="decrease">
                            <button type="submit" class="px-2.5 py-1 text-[#7A6F64] hover:text-[#2D2319] font-bold bg-transparent border-0 cursor-pointer">-</button>
                          </form>

                          <span class="px-3 py-1 font-bold text-[#2D2319] text-xs"><?= $item['qty'] ?></span>

                          <!-- Nút TĂNG số lượng -->
                          <form method="POST" action="" class="inline">
                            <input type="hidden" name="cart_key" value="<?= htmlspecialchars($item['cart_key']) ?>">
                            <input type="hidden" name="cart_action" value="increase">
                            <button type="submit" class="px-2.5 py-1 text-[#7A6F64] hover:text-[#2D2319] font-bold bg-transparent border-0 cursor-pointer">+</button>
                          </form>
                        </div>

                        <!-- Nút XÓA từng sản phẩm -->
                        <form method="POST" action="" class="inline">
                          <input type="hidden" name="cart_key" value="<?= htmlspecialchars($item['cart_key']) ?>">
                          <input type="hidden" name="cart_action" value="remove">
                          <button type="submit" class="text-[#7A6F64] hover:text-red-600 flex items-center gap-1 font-medium bg-transparent border-0 cursor-pointer">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Xóa
                          </button>
                        </form>
                      </div>

                      <span class="text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                        ✓ <?= htmlspecialchars($item['stock']) ?>
                      </span>
                    </div>

                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
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
              <span class="text-[9px] uppercase tracking-wider text-[#D4C7BA] block font-semibold">CẦN TƯ VẤN TRƯỚC KHU QUỆT THẺ?</span>
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
