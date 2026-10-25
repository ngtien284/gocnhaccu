<?php
$page_title = "Trang Chủ - Cửa Hàng Nhạc Cụ & Âm Mộc";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

// 1. TRUY VẤN SẢN PHẨM DEAL HOT (Ưu tiên sản phẩm có khuyến mãi lớn nhất từ bảng nhaccu)
$sqlDeal = "
    SELECT nc.NC_Ma AS id, nc.NC_Ten AS name,
           nc.NC_GiaBan AS original_price, nc.NC_GiaKhuyenMai AS sale_price,
           nc.NC_HinhAnh AS image, nc.NC_MoTa AS description,
           th.TH_Ten AS brand, 'nhaccu' AS product_type
    FROM nhaccu nc
    INNER JOIN thuonghieu th ON nc.TH_Ma = th.TH_Ma
    WHERE nc.NC_TrangThai = 1 AND nc.NC_GiaKhuyenMai > 0
    ORDER BY (nc.NC_GiaBan - nc.NC_GiaKhuyenMai) DESC
    LIMIT 1
";
$stmtDeal = $conn->prepare($sqlDeal);
$stmtDeal->execute();
$dealRow = $stmtDeal->fetch(PDO::FETCH_ASSOC);

// Nếu không có sản phẩm khuyến mãi, lấy sản phẩm nhạc cụ mới nhất làm Deal
if (!$dealRow) {
    $sqlDealFallback = "
        SELECT nc.NC_Ma AS id, nc.NC_Ten AS name,
               nc.NC_GiaBan AS original_price, nc.NC_GiaKhuyenMai AS sale_price,
               nc.NC_HinhAnh AS image, nc.NC_MoTa AS description,
               th.TH_Ten AS brand, 'nhaccu' AS product_type
        FROM nhaccu nc
        INNER JOIN thuonghieu th ON nc.TH_Ma = th.TH_Ma
        WHERE nc.NC_TrangThai = 1
        ORDER BY nc.NC_Ma DESC
        LIMIT 1
    ";
    $stmtDealFallback = $conn->prepare($sqlDealFallback);
    $stmtDealFallback->execute();
    $dealRow = $stmtDealFallback->fetch(PDO::FETCH_ASSOC);
}

// Chuẩn hóa dữ liệu Deal
if ($dealRow) {
    $dealImg = $dealRow['image'] ?? '';
    if ($dealImg !== '' && !preg_match('~^https?://~i', $dealImg)) {
        $dealImg = 'images/' . ltrim($dealImg, '/');
    }
    
    $salePrice = ($dealRow['sale_price'] !== null && (float)$dealRow['sale_price'] > 0)
        ? (float)$dealRow['sale_price'] 
        : (float)$dealRow['original_price'];

    $deal_product = [
        'id'             => (int)$dealRow['id'],
        'product_type'   => $dealRow['product_type'],
        'brand'          => $dealRow['brand'],
        'name'           => $dealRow['name'],
        'desc'           => !empty($dealRow['description']) ? $dealRow['description'] : 'Sản phẩm chính hãng chất lượng cao, kiểm định 55 tiêu chuẩn Luthier.',
        'price'          => $salePrice,
        'original_price' => (float)$dealRow['original_price'],
        'image'          => $dealImg ?: 'images/default.jpg'
    ];
} else {
    $deal_product = null;
}

// 2. TRUY VẤN 4 SẢN PHẨM MỚI NHẤT CHO NGHỆ THUẬT GALLERY
$sqlGallery = "
    SELECT nc.NC_Ma AS id, nc.NC_Ten AS name,
           nc.NC_GiaBan AS original_price, nc.NC_GiaKhuyenMai AS sale_price,
           nc.NC_HinhAnh AS image, th.TH_Ten AS brand,
           lnc.LNC_Ten AS category, 'nhaccu' AS product_type
    FROM nhaccu nc
    INNER JOIN thuonghieu th ON nc.TH_Ma = th.TH_Ma
    INNER JOIN loainhaccu lnc ON nc.LNC_Ma = lnc.LNC_Ma
    WHERE nc.NC_TrangThai = 1
    ORDER BY nc.NC_Ma DESC
    LIMIT 4
";
$stmtGallery = $conn->prepare($sqlGallery);
$stmtGallery->execute();
$galleryRows = $stmtGallery->fetchAll(PDO::FETCH_ASSOC);

$gallery_products = [];
foreach ($galleryRows as $row) {
    $img = $row['image'] ?? '';
    if ($img !== '' && !preg_match('~^https?://~i', $img)) {
        $img = 'images/' . ltrim($img, '/');
    }
    
    $price = ($row['sale_price'] !== null && (float)$row['sale_price'] > 0)
        ? (float)$row['sale_price'] 
        : (float)$row['original_price'];

    $gallery_products[] = [
        'id'           => (int)$row['id'],
        'product_type' => $row['product_type'],
        'serial'       => '#NC-' . str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT),
        'name'         => $row['name'],
        'price'        => $price,
        'tag'          => 'Kiểm định Luthier 55 tiêu chí',
        'image'        => $img ?: 'images/default.jpg'
    ];
}
?>

<main class="flex-1 bg-[#FBF8F5] text-[#2D2319]">
  
  <!-- 1. Hero Banner: Tone Nâu Ấm Áp -->
  <section class="max-w-7xl mx-auto px-6 py-8">
    <div class="bg-gradient-to-r from-[#2D2319] via-[#4A382C] to-[#5F371D] rounded-3xl text-white p-10 lg:p-14 relative overflow-hidden flex flex-col lg:flex-row items-center justify-between gap-10 shadow-xl border border-[#754728]/30">
      
      <div class="absolute -right-20 -top-20 w-96 h-96 bg-[#8C5835]/30 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-xl z-10">
        <div class="inline-flex items-center gap-2 bg-[#8C5835]/40 border border-[#B8977E]/40 text-[#EFE7DF] text-xs font-semibold px-3.5 py-1.5 rounded-full mb-6">
          <i data-lucide="sparkles" class="w-3.5 h-3.5 text-[#E0D2C4]"></i>
          <span class="tracking-wide">BỘ SƯU TẬP ACOUSTIC NGUYÊN BẢN <?= date('Y') ?></span>
        </div>
        
        <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight mb-4 font-serif">
          Linh Hồn Của Gỗ — <br>
          <span class="text-[#E0D2C4] italic font-normal">Thanh Âm Tinh Xảo</span>
        </h1>
        
        <p class="text-[#D4C7BA] text-sm leading-relaxed mb-8">
          Tuyển chọn trực tiếp từ các xưởng chế tác thủ công danh tiếng thế giới. Mỗi thớ gỗ vân sam, mun đen hay cẩm lai được ủ sấy tự nhiên qua nhiều năm để mang đến dải tần thoát âm thuần khiết nhất.
        </p>

        <div class="flex flex-wrap items-center gap-4">
          <a href="sanpham.php" class="bg-[#8C5835] hover:bg-[#724324] text-white font-semibold text-xs px-6 py-3.5 rounded-xl transition shadow-lg shadow-[#8C5835]/30 flex items-center gap-2">
            <span>Khám Phá Toàn Bộ Nhạc Cụ</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
          </a>
          
          <a href="khuyen-mai.php" class="bg-white/10 hover:bg-white/20 text-[#EFE7DF] font-semibold text-xs px-5 py-3.5 rounded-xl transition border border-white/15 flex items-center gap-2 backdrop-blur-sm">
            <i data-lucide="gift" class="w-4 h-4 text-[#E0D2C4]"></i>
            <span>Xem Gói Quà Tặng Luthier</span>
          </a>
        </div>
      </div>

      <!-- Hero Featured Card -->
      <?php if (!empty($gallery_products[0])): ?>
      <div class="relative z-10 w-full lg:w-96 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 p-5 shadow-2xl">
        <div class="relative h-72 rounded-xl overflow-hidden bg-[#2D2319]/40 mb-4">
          <img src="<?= htmlspecialchars($gallery_products[0]['image']) ?>" alt="<?= htmlspecialchars($gallery_products[0]['name']) ?>" class="w-full h-full object-cover"/>
          <span class="absolute top-3 left-3 bg-[#8C5835] text-white text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded shadow">
            Đàn Độc Bản Tại Showroom
          </span>
        </div>
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-bold text-base text-white line-clamp-1"><?= htmlspecialchars($gallery_products[0]['name']) ?></h3>
            <p class="text-xs text-[#D4C7BA]">Serial <?= htmlspecialchars($gallery_products[0]['serial']) ?></p>
          </div>
          <div class="text-right shrink-0">
            <span class="text-[11px] text-[#B8977E] block">Giá phân phối</span>
            <span class="text-[#E0D2C4] font-bold text-base"><?= format_vnd($gallery_products[0]['price']) ?></span>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </section>

  <!-- 2. 4 Cam Kết Vàng -->
  <section class="max-w-7xl mx-auto px-6 py-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-[#E7E0D8] flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-[#EFE7DF] text-[#8C5835] flex items-center justify-center shrink-0">
          <i data-lucide="shield-check" class="w-6 h-6"></i>
        </div>
        <div>
          <h4 class="font-bold text-xs text-[#2D2319]">100% Chính Hãng</h4>
          <p class="text-[11px] text-[#7A6F64]">Đầy đủ C/O, C/Q hải quan quốc tế</p>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-[#E7E0D8] flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-[#EFE7DF] text-[#8C5835] flex items-center justify-center shrink-0">
          <i data-lucide="wrench" class="w-6 h-6"></i>
        </div>
        <div>
          <h4 class="font-bold text-xs text-[#2D2319]">Kiểm Định 55 Điểm</h4>
          <p class="text-[11px] text-[#7A6F64]">Cân chỉnh Action & độ phẳng phím</p>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-[#E7E0D8] flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-[#EFE7DF] text-[#8C5835] flex items-center justify-center shrink-0">
          <i data-lucide="truck" class="w-6 h-6"></i>
        </div>
        <div>
          <h4 class="font-bold text-xs text-[#2D2319]">Giao Hàng Chống Sốc</h4>
          <p class="text-[11px] text-[#7A6F64]">Đóng thùng mút 5 lớp chuyên dụng</p>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-[#E7E0D8] flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-xl bg-[#EFE7DF] text-[#8C5835] flex items-center justify-center shrink-0">
          <i data-lucide="headphones" class="w-6 h-6"></i>
        </div>
        <div>
          <h4 class="font-bold text-xs text-[#2D2319]">Studio Thử Âm RT60</h4>
          <p class="text-[11px] text-[#7A6F64]">Buồng cách âm tiêu chuẩn phòng thu</p>
        </div>
      </div>
    </div>
  </section>

  <!-- 3. DEAL CHỚP NHOÁNG (Lấy từ CSDL) -->
  <?php if ($deal_product): ?>
  <section class="max-w-7xl mx-auto px-6 py-6">
    <div class="bg-white rounded-2xl border border-[#E7E0D8] p-6 shadow-sm relative">
      <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-[#E7E0D8] pb-4 mb-4">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-white bg-[#8C5835] px-2.5 py-1 rounded-full">⚡ DEAL CHỚP NHOÁNG CỤC SỐC (24H)</span>
          <h2 class="text-lg font-bold text-[#2D2319] font-serif mt-2">Sản phẩm kỹ thuật phòng thu hot nhất hôm nay</h2>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold text-[#7A6F64] mt-3 md:mt-0">
          <span>KẾT THÚC SAU:</span>
          <span class="bg-[#2D2319] text-white px-2 py-1 rounded">05</span> :
          <span class="bg-[#2D2319] text-white px-2 py-1 rounded">42</span> :
          <span class="bg-[#2D2319] text-white px-2 py-1 rounded">09</span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
        <div class="md:col-span-4 bg-[#FBF8F5] border border-[#E7E0D8] rounded-xl p-4 relative flex items-center justify-center aspect-square">
          <?php if ($deal_product['original_price'] > $deal_product['price']): ?>
            <span class="absolute top-3 left-3 bg-[#8C5835] text-white text-[10px] font-bold px-2.5 py-1 rounded-full z-10">
              TIẾT KIỆM <?= format_vnd($deal_product['original_price'] - $deal_product['price']) ?>
            </span>
          <?php endif; ?>
          <img src="<?= htmlspecialchars($deal_product['image']) ?>" alt="<?= htmlspecialchars($deal_product['name']) ?>" class="w-full h-full object-contain rounded-lg"/>
        </div>

        <div class="md:col-span-8 flex flex-col justify-between">
          <div>
            <div class="flex items-center gap-2 text-xs text-[#7A6F64] mb-1">
              <span class="font-bold text-[#8C5835] uppercase"><?= htmlspecialchars($deal_product['brand']) ?></span>
              <span>•</span>
              <div class="flex text-amber-600">
                <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
                <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
                <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
                <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
                <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
              </div>
            </div>
            
            <a href="chitietsanpham.php?type=<?= urlencode($deal_product['product_type']) ?>&id=<?= $deal_product['id'] ?>">
              <h3 class="text-base font-bold text-[#2D2319] mb-2 font-serif hover:text-[#8C5835] transition">
                <?= htmlspecialchars($deal_product['name']) ?>
              </h3>
            </a>

            <p class="text-xs text-[#7A6F64] leading-relaxed mb-4 line-clamp-3">
              <?= htmlspecialchars($deal_product['desc']) ?>
            </p>
          </div>

          <div class="flex flex-wrap items-center justify-between pt-4 border-t border-[#E7E0D8] gap-4">
            <div>
              <div class="flex items-baseline gap-2">
                <span class="text-xl font-black text-[#8C5835]"><?= format_vnd($deal_product['price']) ?></span>
                <?php if ($deal_product['original_price'] > $deal_product['price']): ?>
                  <span class="text-xs text-[#9E948A] line-through"><?= format_vnd($deal_product['original_price']) ?></span>
                <?php endif; ?>
              </div>
              <span class="text-[11px] text-[#7A6F64] block mt-0.5">Hỗ trợ trả góp 0% qua thẻ tín dụng</span>
            </div>

            <div class="flex items-center gap-2">
              <form action="gio-hang.php" method="POST">
                <input type="hidden" name="action" value="add"/>
                <input type="hidden" name="product_type" value="<?= htmlspecialchars($deal_product['product_type']) ?>"/>
                <input type="hidden" name="product_id" value="<?= $deal_product['id'] ?>"/>
                <button type="submit" class="bg-[#8C5835] hover:bg-[#724324] text-white text-xs font-bold px-5 py-3 rounded-xl transition shadow flex items-center gap-1.5">
                  <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                  <span>Thêm vào giỏ</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- 4. GUITAR GALLERY PREVIEW (Lấy dữ liệu thật từ DB) -->
  <section class="max-w-7xl mx-auto px-6 py-6">
    <div class="bg-white rounded-2xl border border-[#E7E0D8] p-6 shadow-sm">
      <div class="flex items-center justify-between mb-6 pb-4 border-b border-[#E7E0D8]">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-[#8C5835]">SƯU TẬP ĐỘC BẢN LUTHIER</span>
          <h2 class="text-lg font-bold text-[#2D2319] font-serif">Guitar Gallery™ - Chọn Đúng Cây Đàn Bạn Muốn</h2>
          <p class="text-xs text-[#7A6F64]">Mỗi cây đàn chụp chi tiết từng góc cạnh & vân gỗ thực tế giúp bạn an tâm tuyệt đối.</p>
        </div>
        <a href="sanpham.php" class="text-xs font-bold text-white bg-[#8C5835] hover:bg-[#724324] px-4 py-2.5 rounded-xl transition shadow">
          Xem Tất Cả Sản Phẩm
        </a>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($gallery_products as $gp): ?>
          <div class="border border-[#E7E0D8] rounded-2xl p-4 bg-white hover:border-[#8C5835]/50 hover:shadow-md transition flex flex-col justify-between group">
            <div>
              <a href="chitietsanpham.php?type=<?= urlencode($gp['product_type']) ?>&id=<?= $gp['id'] ?>" class="block">
                <div class="relative rounded-xl bg-[#FBF8F5] aspect-square overflow-hidden mb-3 border border-[#E7E0D8]">
                  <span class="absolute top-2.5 left-2.5 bg-[#2D2319] text-white text-[10px] px-2 py-0.5 rounded font-mono font-bold shadow">
                    Serial: <?= $gp['serial'] ?>
                  </span>
                  <img src="<?= htmlspecialchars($gp['image']) ?>" alt="<?= htmlspecialchars($gp['name']) ?>" class="w-full h-full object-contain p-2 group-hover:scale-105 transition duration-500"/>
                </div>
              </a>

              <span class="text-[10px] font-bold uppercase text-[#8C5835] block mb-1">NHẠC CỤ CHÍNH HÃNG</span>
              <a href="chitietsanpham.php?type=<?= urlencode($gp['product_type']) ?>&id=<?= $gp['id'] ?>">
                <h3 class="font-bold text-xs text-[#2D2319] mb-1 line-clamp-2 group-hover:text-[#8C5835] transition"><?= htmlspecialchars($gp['name']) ?></h3>
              </a>
              <p class="text-[11px] text-[#8C5835] font-medium mb-3"><?= $gp['tag'] ?></p>
            </div>

            <div class="pt-3 border-t border-[#E7E0D8] flex items-center justify-between">
              <div>
                <span class="text-sm font-black text-[#8C5835]"><?= format_vnd($gp['price']) ?></span>
                <span class="text-[10px] text-[#7A6F64] block">Bảo hành 2 năm</span>
              </div>
              <form action="gio-hang.php" method="POST">
                <input type="hidden" name="action" value="add"/>
                <input type="hidden" name="product_type" value="<?= htmlspecialchars($gp['product_type']) ?>"/>
                <input type="hidden" name="product_id" value="<?= $gp['id'] ?>"/>
                <button type="submit" class="bg-[#2D2319] hover:bg-[#8C5835] text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow">
                  Chọn Mua
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- 5. DANH MỤC LỌC NHANH SẢN PHẨM -->
  <section class="max-w-7xl mx-auto px-6 py-6">
    <div class="bg-white rounded-2xl border border-[#E7E0D8] p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4 pb-3 border-b border-[#E7E0D8]">
        <h2 class="text-lg font-bold text-[#2D2319] font-serif">Danh Mục Nhạc Cụ & Phụ Kiện</h2>
        <a href="sanpham.php" class="text-xs font-bold text-[#8C5835] hover:text-[#724324]">Xem tất cả danh mục ›</a>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-center">
        <a href="sanpham.php?loai[]=Guitar" class="p-4 border border-[#E7E0D8] rounded-2xl hover:border-[#8C5835] hover:bg-[#FBF8F5] transition group">
          <div class="h-14 flex items-center justify-center mb-2 bg-[#FBF8F5] rounded-xl text-[#8C5835] group-hover:bg-[#8C5835] group-hover:text-white transition">
            <i data-lucide="music" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-xs text-[#2D2319] block">Guitar</span>
        </a>

        <a href="sanpham.php?loai[]=Organ" class="p-4 border border-[#E7E0D8] rounded-2xl hover:border-[#8C5835] hover:bg-[#FBF8F5] transition group">
          <div class="h-14 flex items-center justify-center mb-2 bg-[#FBF8F5] rounded-xl text-[#8C5835] group-hover:bg-[#8C5835] group-hover:text-white transition">
            <i data-lucide="disc" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-xs text-[#2D2319] block">Organ / Keyboard</span>
        </a>

        <a href="sanpham.php?loai[]=Trống" class="p-4 border border-[#E7E0D8] rounded-2xl hover:border-[#8C5835] hover:bg-[#FBF8F5] transition group">
          <div class="h-14 flex items-center justify-center mb-2 bg-[#FBF8F5] rounded-xl text-[#8C5835] group-hover:bg-[#8C5835] group-hover:text-white transition">
            <i data-lucide="activity" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-xs text-[#2D2319] block">Trống</span>
        </a>

        <a href="sanpham.php?loai[]=Piano" class="p-4 border border-[#E7E0D8] rounded-2xl hover:border-[#8C5835] hover:bg-[#FBF8F5] transition group">
          <div class="h-14 flex items-center justify-center mb-2 bg-[#FBF8F5] rounded-xl text-[#8C5835] group-hover:bg-[#8C5835] group-hover:text-white transition">
            <i data-lucide="sliders" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-xs text-[#2D2319] block">Piano</span>
        </a>

        <a href="sanpham.php?loai[]=Violin" class="p-4 border border-[#E7E0D8] rounded-2xl hover:border-[#8C5835] hover:bg-[#FBF8F5] transition group">
          <div class="h-14 flex items-center justify-center mb-2 bg-[#FBF8F5] rounded-xl text-[#8C5835] group-hover:bg-[#8C5835] group-hover:text-white transition">
            <i data-lucide="volume-2" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-xs text-[#2D2319] block">Violin</span>
        </a>

        <a href="sanpham.php?loai[]=Phụ+kiện" class="p-4 border border-[#E7E0D8] rounded-2xl hover:border-[#8C5835] hover:bg-[#FBF8F5] transition group">
          <div class="h-14 flex items-center justify-center mb-2 bg-[#FBF8F5] rounded-xl text-[#8C5835] group-hover:bg-[#8C5835] group-hover:text-white transition">
            <i data-lucide="mic" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-xs text-[#2D2319] block">Phụ kiện</span>
        </a>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
