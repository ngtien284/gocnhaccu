<?php
// sanpham.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

// 1. LẤY VÀ CHUẨN HÓA THAM SỐ BỘ LỌC TỪ URL
$selectedTypes    = isset($_GET['loai']) && is_array($_GET['loai']) ? $_GET['loai'] : [];
$selectedBrands   = isset($_GET['thuonghieu']) && is_array($_GET['thuonghieu']) ? $_GET['thuonghieu'] : [];
$minPrice         = isset($_GET['gia_tu']) && $_GET['gia_tu'] !== '' ? max(0, (float)$_GET['gia_tu']) : null;
$maxPrice         = isset($_GET['gia_den']) && $_GET['gia_den'] !== '' ? max(0, (float)$_GET['gia_den']) : null;
$hideOutOfStock   = !empty($_GET['instock']);
$sortBy           = isset($_GET['sort']) ? trim($_GET['sort']) : 'moi_nhat';

// ĐÓN NHẬN TỪ KHÓA TÌM KIẾM TRỰC TIẾP TRONG TRANG
$keyword          = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$allowedTypes     = ['Guitar', 'Organ', 'Trống', 'Ukulele', 'Violin', 'Piano', 'Phụ kiện'];
$selectedTypes    = array_values(array_intersect($allowedTypes, $selectedTypes));

// Đón nhận từ khóa từ cả 2 dạng (?keyword=... hoặc ?q=...)
$keyword = '';
if (isset($_GET['keyword']) && trim($_GET['keyword']) !== '') {
    $keyword = trim($_GET['keyword']);
} elseif (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $keyword = trim($_GET['q']);
}

// Chuẩn bị điều kiện tìm kiếm
$searchClauseNC = "";
$searchClausePK = "";
$params = [];

if (!empty($keyword)) {
    // Tìm theo tên nhạc cụ hoặc mô tả
    $searchClauseNC = " AND (nc.NC_Ten LIKE :kw OR nc.NC_MoTa LIKE :kw)";
    // Tìm theo tên phụ kiện hoặc mô tả
    $searchClausePK = " AND (pk.PK_Ten LIKE :kw OR pk.PK_MoTa LIKE :kw)";
    
    // Gán giá trị vào mảng params
    $params['kw'] = "%" . $keyword . "%";
}

// TRUY VẤN BẢNG NHẠC CỤ
$sqlNhacCu = "
    SELECT nc.NC_Ma AS id, nc.NC_Ten AS name,
           nc.NC_GiaBan AS original_price, nc.NC_GiaKhuyenMai AS sale_price,
           nc.NC_SoLuongTon AS stock, nc.NC_HinhAnh AS image,
           nc.NC_MoTa AS description, th.TH_Ten AS brand,
           lnc.LNC_Ten AS category, 'nhaccu' AS product_type
    FROM nhaccu nc
    INNER JOIN thuonghieu th ON nc.TH_Ma = th.TH_Ma
    INNER JOIN loainhaccu lnc ON nc.LNC_Ma = lnc.LNC_Ma
    WHERE nc.NC_TrangThai = 1 
    " . $searchClauseNC . "
";
$stmt = $conn->prepare($sqlNhacCu);
$stmt->execute($params); // Truyền đúng mảng tham số vào đây
$nhacCuRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// TRUY VẤN BẢNG PHỤ KIỆN
$sqlPhuKien = "
    SELECT pk.PK_Ma AS id, pk.PK_Ten AS name,
           pk.PK_GiaBan AS original_price, pk.PK_GiaKhuyenMai AS sale_price,
           pk.PK_SoLuongTon AS stock, pk.PK_HinhAnh AS image,
           pk.PK_MoTa AS description, th.TH_Ten AS brand,
           'Phụ kiện' AS category, 'phukien' AS product_type
    FROM phukien pk
    INNER JOIN thuonghieu th ON pk.TH_Ma = th.TH_Ma
    WHERE pk.PK_TrangThai = 1 
    " . $searchClausePK . "
";
$stmt = $conn->prepare($sqlPhuKien);
$stmt->execute($params); // Truyền đúng mảng tham số vào đây
$phuKienRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allRows = array_merge($nhacCuRows, $phuKienRows);


// Hàm chuẩn hóa loại sản phẩm
function normalize_product_type($category) {
    $cat = mb_strtolower(trim((string)$category), 'UTF-8');
    if (strpos($cat, 'guitar') !== false) return 'Guitar';
    if (strpos($cat, 'organ') !== false || strpos($cat, 'keyboard') !== false) return 'Organ';
    if (strpos($cat, 'trống') !== false || strpos($cat, 'drum') !== false) return 'Trống';
    if (strpos($cat, 'ukulele') !== false) return 'Ukulele';
    if (strpos($cat, 'violin') !== false || strpos($cat, 'vĩ cầm') !== false) return 'Violin';
    if (strpos($cat, 'piano') !== false) return 'Piano';
    return trim((string)$category);
}

// 5. ĐẾM SỐ LƯỢNG SẢN PHẨM THEO THƯƠNG HIỆU & LOẠI
$brandCounts = [];
$typeCounts  = [];

foreach ($allRows as $r) {
    $bName = trim($r['brand'] ?? 'Khác');
    $cName = ($r['product_type'] === 'phukien') ? 'Phụ kiện' : normalize_product_type($r['category']);
    
    $brandCounts[$bName] = ($brandCounts[$bName] ?? 0) + 1;
    $typeCounts[$cName]  = ($typeCounts[$cName] ?? 0) + 1;
}
ksort($brandCounts);

$availableBrands = array_keys($brandCounts);
$selectedBrands  = array_values(array_intersect($availableBrands, $selectedBrands));

// 6. LỌC CHI TIẾT DANH SÁCH SẢN PHẨM
$gallery_products = [];
foreach ($allRows as $product) {
    $image = $product['image'] ?? '';
    if ($image !== '' && !preg_match('~^https?://~i', $image)) {
        $image = 'images/' . ltrim($image, '/');
    }
    
    $category = ($product['product_type'] === 'phukien')
        ? 'Phụ kiện'
        : normalize_product_type($product['category']);
        
    $brand = trim((string)$product['brand']);
    $salePrice = ($product['sale_price'] !== null && (float)$product['sale_price'] > 0)
        ? (float)$product['sale_price'] 
        : (float)$product['original_price'];
    $stock = (int)$product['stock'];

    // Lọc "Không bao gồm hết hàng"
    if ($hideOutOfStock && $stock <= 0) continue;

    // Lọc Loại
    if ($selectedTypes && !in_array($category, $selectedTypes, true)) continue;

    // Lọc Thương hiệu
    if ($selectedBrands && !in_array($brand, $selectedBrands, true)) continue;

    // Lọc Giá
    if ($minPrice !== null && $salePrice < $minPrice) continue;
    if ($maxPrice !== null && $salePrice > $maxPrice) continue;

    $gallery_products[] = [
        'id'             => (int)$product['id'],
        'product_type'   => $product['product_type'],
        'brand'          => $brand,
        'name'           => $product['name'],
        'category'       => $category,
        'serial'         => ($product['product_type'] === 'phukien' ? '#PK-' : '#NC-') . str_pad((string)$product['id'], 4, '0', STR_PAD_LEFT),
        'price'          => $salePrice,
        'original_price' => (float)$product['original_price'],
        'action'         => 'Xem chi tiết',
        'weight'         => 'Chưa cập nhật',
        'specs'          => [$category],
        'badge'          => 'Sản phẩm trong kho',
        'stock'          => $stock,
        'image'          => $image,
        'description'    => $product['description'] ?? ''
    ];
}

// 7. SẮP XẾP SẢN PHẨM THEO TIÊU CHÍ CHỌN
usort($gallery_products, function($a, $b) use ($sortBy) {
    switch ($sortBy) {
        case 'cung_loai':
            return strcmp($a['category'], $b['category']);
        case 'gia_tang':
            if ($a['price'] == $b['price']) return 0;
            return ($a['price'] < $b['price']) ? -1 : 1;
        case 'gia_giam':
            if ($a['price'] == $b['price']) return 0;
            return ($a['price'] > $b['price']) ? -1 : 1;
        case 'moi_nhat':
        default:
            return $b['id'] - $a['id'];
    }
});

// 8. XỬ LÝ PHÂN TRANG
$per_page         = 4;
$total_products   = count($gallery_products);
$total_pages      = max(1, (int)ceil($total_products / $per_page));
$current_page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page     = max(1, min($current_page, $total_pages));
$offset           = ($current_page - 1) * $per_page;
$display_products = array_slice($gallery_products, $offset, $per_page);

$queryWithoutPage = $_GET;
unset($queryWithoutPage['page']);
$filterBaseUrl    = '?' . http_build_query($queryWithoutPage);
if ($filterBaseUrl === '?') $filterBaseUrl = '?';

// Tổng số điều kiện đang lọc
$activeFilterCount = count($selectedTypes) + count($selectedBrands) + ($minPrice !== null || $maxPrice !== null ? 1 : 0) + ($hideOutOfStock ? 1 : 0) + (!empty($keyword) ? 1 : 0);
?>

<main class="flex-1 bg-[#FBF8F5] text-[#2D2319] font-sans">
  
  <!-- HERO HEADER BANNER GALLERY -->
  <section class="bg-gradient-to-r from-[#2D2319] via-[#4A382C] to-[#5F371D] text-white py-10 px-6 border-b border-[#754728]/30">
    <div class="max-w-7xl mx-auto">
      <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
        <div class="max-w-3xl">
          <div class="flex items-center gap-2 text-xs font-bold text-[#E0D2C4] mb-2">
            <span class="bg-[#8C5835] text-white px-2.5 py-1 rounded-full text-[10px] uppercase shadow">ĐỘC QUYỀN TẠI SHOWROOM</span>
            <span>• Chụp Studio 50MP</span>
          </div>
          <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight font-serif mb-3">GÓC NHẠC CỤ</h1>
          <p class="text-xs lg:text-sm text-[#D4C7BA] leading-relaxed mb-6">
            Xem ảnh độ phân giải cao của chính <strong class="text-white underline">xác từng cây đàn</strong> trong kho, cân nặng thực tế và từng gram; cùng biên bản kiểm tra kỹ thuật <strong class="text-white underline">55-Point Inspection</strong> trước khi đặt hàng.
          </p>

          <div class="flex flex-wrap items-center gap-6 text-xs border-t border-white/15 pt-4">
            <div>
              <span class="text-lg font-black text-[#E0D2C4] block leading-tight">100%</span>
              <span class="text-[#D4C7BA] text-[11px]">Ảnh chụp đúng cây bạn nhận</span>
            </div>
            <div class="border-l border-white/15 pl-6">
              <span class="text-lg font-black text-[#E0D2C4] block leading-tight">55 Bước</span>
              <span class="text-[#D4C7BA] text-[11px]">Kiểm định bởi Luthier</span>
            </div>
            <div class="border-l border-white/15 pl-6">
              <span class="text-lg font-black text-[#E0D2C4] block leading-tight">2 Năm</span>
              <span class="text-[#D4C7BA] text-[11px]">Bảo hành tiêu chuẩn mộc</span>
            </div>
          </div>
        </div>

        <div class="bg-white/10 backdrop-blur-md text-white rounded-2xl p-6 shadow-2xl max-w-sm w-full border border-white/20">
          <div class="flex items-center gap-2 font-bold text-xs text-[#E0D2C4] mb-2">
            <i data-lucide="shield-check" class="w-5 h-5 text-emerald-400"></i>
            <span>CAM KẾT MUA SẮM TUYỆT ĐỐI</span>
          </div>
          <h3 class="font-bold text-sm mb-1">Không giao hàng "mù mờ"</h3>
          <p class="text-[11px] text-[#D4C7BA] leading-relaxed mb-4">
            Mỗi cây nhạc cụ có số serial riêng biệt sẵn sàng trong kho. Nếu không đúng hình, hoàn tiền 100%.
          </p>
          <div class="flex items-center justify-between text-xs font-bold pt-3 border-t border-white/15">
            <span class="text-emerald-400">✓ Đạt chuẩn Luthier A+</span>
            <span class="text-[#D4C7BA]">Kho sẵn sàng xuất</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MAIN CONTENT -->
  <section class="max-w-7xl mx-auto px-6 py-8">
    <div class="grid grid-cols-12 gap-8">
      
      <!-- CỘT BÊN TRÁI: BỘ LỌC SIDEBAR -->
      <aside class="col-span-12 lg:col-span-3 space-y-6">
        
        <form id="filterForm" method="GET" action="" onsubmit="event.preventDefault(); applyFilterAjax();" class="bg-white rounded-2xl border border-[#E7E0D8] p-5 shadow-sm">
          
          <!-- INPUT ẨN GIỮ LẠI TRẠNG THÁI SẮP XẾP VÀ TỪ KHÓA TÌM KIẾM -->
          <input type="hidden" name="sort" id="hiddenSortInput" value="<?= htmlspecialchars($sortBy) ?>">
          <input type="hidden" name="keyword" id="hiddenKeywordInput" value="<?= htmlspecialchars($keyword) ?>">

          <!-- TIÊU ĐỀ LỌC & NÚT XÓA TOÀN BỘ -->
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-sm text-[#2D2319] flex items-center gap-1.5">
              <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
              Lọc <?= $activeFilterCount > 0 ? "({$activeFilterCount})" : '' ?>
            </h3>
            <?php if ($activeFilterCount > 0): ?>
              <a href="?" class="ajax-filter-link text-xs font-medium text-[#A48650] hover:underline">Xoá toàn bộ</a>
            <?php endif; ?>
          </div>

          <!-- CONTAINER THẺ/TAG ĐANG LỌC -->
          <div id="active-tags-container">
            <?php if ($activeFilterCount > 0): ?>
              <div class="flex flex-wrap gap-2 mb-4 pb-3 border-b border-[#E7E0D8]">
                
                <!-- Tag Từ khóa tìm kiếm -->
                <?php if (!empty($keyword)): 
                  $q = $_GET; unset($q['keyword'], $q['page']);
                ?>
                  <span class="bg-[#F0F0F0] text-[#333] border border-gray-300 text-xs px-3 py-1 rounded-full inline-flex items-center gap-1.5 font-medium">
                    Từ khóa: "<?= htmlspecialchars($keyword) ?>"
                    <a href="?<?= htmlspecialchars(http_build_query($q)) ?>" class="ajax-filter-link w-4 h-4 rounded-full bg-gray-400 text-white flex items-center justify-center text-[10px] hover:bg-gray-600">✕</a>
                  </span>
                <?php endif; ?>

                <!-- Tag Loại -->
                <?php foreach ($selectedTypes as $type): 
                  $removeTypes = array_values(array_diff($selectedTypes, [$type])); 
                  $q = $_GET; 
                  if ($removeTypes) { $q['loai'] = $removeTypes; } else { unset($q['loai']); }
                  unset($q['page']);
                ?>
                  <span class="bg-[#F0F0F0] text-[#333] border border-gray-300 text-xs px-3 py-1 rounded-full inline-flex items-center gap-1.5 font-medium">
                    <?= htmlspecialchars($type) ?> 
                    <a href="?<?= htmlspecialchars(http_build_query($q)) ?>" class="ajax-filter-link w-4 h-4 rounded-full bg-gray-400 text-white flex items-center justify-center text-[10px] hover:bg-gray-600">✕</a>
                  </span>
                <?php endforeach; ?>

                <!-- Tag Thương hiệu -->
                <?php foreach ($selectedBrands as $brandTag): 
                  $removeBrands = array_values(array_diff($selectedBrands, [$brandTag])); 
                  $q = $_GET; 
                  if ($removeBrands) { $q['thuonghieu'] = $removeBrands; } else { unset($q['thuonghieu']); }
                  unset($q['page']);
                ?>
                  <span class="bg-[#F0F0F0] text-[#333] border border-gray-300 text-xs px-3 py-1 rounded-full inline-flex items-center gap-1.5 font-medium">
                    <?= htmlspecialchars($brandTag) ?> 
                    <a href="?<?= htmlspecialchars(http_build_query($q)) ?>" class="ajax-filter-link w-4 h-4 rounded-full bg-gray-400 text-white flex items-center justify-center text-[10px] hover:bg-gray-600">✕</a>
                  </span>
                <?php endforeach; ?>

                <!-- Tag Còn hàng -->
                <?php if ($hideOutOfStock): 
                  $q = $_GET; unset($q['instock'], $q['page']);
                ?>
                  <span class="bg-[#F0F0F0] text-[#333] border border-gray-300 text-xs px-3 py-1 rounded-full inline-flex items-center gap-1.5 font-medium">
                    Còn hàng
                    <a href="?<?= htmlspecialchars(http_build_query($q)) ?>" class="ajax-filter-link w-4 h-4 rounded-full bg-gray-400 text-white flex items-center justify-center text-[10px] hover:bg-gray-600">✕</a>
                  </span>
                <?php endif; ?>

                <!-- Tag Giá -->
                <?php if ($minPrice !== null || $maxPrice !== null): 
                  $q = $_GET; unset($q['gia_tu'], $q['gia_den'], $q['page']);
                ?>
                  <span class="bg-[#F0F0F0] text-[#333] border border-gray-300 text-xs px-3 py-1 rounded-full inline-flex items-center gap-1.5 font-medium">
                    Giá: <?= $minPrice !== null ? number_format($minPrice, 0, ',', '.') : '0' ?>đ – <?= $maxPrice !== null ? number_format($maxPrice, 0, ',', '.') : '∞' ?>đ
                    <a href="?<?= htmlspecialchars(http_build_query($q)) ?>" class="ajax-filter-link w-4 h-4 rounded-full bg-gray-400 text-white flex items-center justify-center text-[10px] hover:bg-gray-600">✕</a>
                  </span>
                <?php endif; ?>

              </div>
            <?php endif; ?>
          </div>

          <div class="space-y-3">
            
            <!-- SECTION 1: TÌNH TRẠNG -->
            <div class="border border-gray-100 rounded-lg overflow-hidden">
              <div class="bg-[#F6F6F6] px-3.5 py-2.5 flex items-center justify-between cursor-pointer select-none" onclick="toggleSection('sec-status')">
                <span class="font-bold text-xs text-[#222]">Tình trạng</span>
                <span id="icon-sec-status" class="text-xs text-gray-500 font-bold">▲</span>
              </div>
              <div id="sec-status" class="p-3.5 space-y-2 text-xs text-[#333]">
                <label class="flex items-center gap-2 cursor-pointer hover:text-black">
                  <input type="checkbox" name="instock" value="1" onchange="applyFilterAjax()" <?= $hideOutOfStock ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-300 text-[#8C5835] focus:ring-[#8C5835]">
                  <span>Không bao gồm sản phẩm hết hàng</span>
                </label>
              </div>
            </div>

            <!-- SECTION 2: THƯƠNG HIỆU -->
            <div class="border border-gray-100 rounded-lg overflow-hidden">
              <div class="bg-[#F6F6F6] px-3.5 py-2.5 flex items-center justify-between cursor-pointer select-none" onclick="toggleSection('sec-brand')">
                <span class="font-bold text-xs text-[#222]">Thương hiệu <?= count($selectedBrands) > 0 ? '('.count($selectedBrands).')' : '' ?></span>
                <span id="icon-sec-brand" class="text-xs text-gray-500 font-bold">▲</span>
              </div>
              
              <div id="sec-brand" class="p-3 space-y-3">
                <div class="relative">
                  <input type="text" id="searchBrandInput" onkeyup="filterBrandList()" placeholder="Tìm kiếm" class="w-full bg-white border border-gray-300 rounded-full pl-3 pr-8 py-1.5 text-xs text-gray-700 focus:outline-none focus:border-[#8C5835]" />
                  <svg class="w-3.5 h-3.5 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <div id="brandListContainer" class="space-y-2 max-h-60 overflow-y-auto text-xs text-[#333] pr-1">
                  <?php 
                  $index = 0;
                  foreach ($brandCounts as $bName => $bCount): 
                    $index++;
                    $isChecked = in_array($bName, $selectedBrands, true);
                  ?>
                    <label class="brand-item flex items-center justify-between cursor-pointer hover:text-black <?= $index > 8 && !$isChecked ? 'hidden-brand hidden' : '' ?>" data-name="<?= mb_strtolower($bName, 'UTF-8') ?>">
                      <div class="flex items-center gap-2">
                        <input type="checkbox" name="thuonghieu[]" value="<?= htmlspecialchars($bName) ?>" onchange="applyFilterAjax()" <?= $isChecked ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-300 text-[#8C5835] focus:ring-[#8C5835]">
                        <span class="<?= $isChecked ? 'font-bold' : '' ?>"><?= htmlspecialchars($bName) ?></span>
                      </div>
                      <span class="text-gray-400 text-[11px]">(<?= $bCount ?>)</span>
                    </label>
                  <?php endforeach; ?>
                </div>

                <?php if (count($brandCounts) > 8): ?>
                  <button type="button" id="toggleBrandBtn" onclick="toggleMoreBrands()" class="text-xs font-bold text-[#A48650] hover:underline flex items-center gap-1 pt-1">
                    + Xem Thêm <?= count($brandCounts) - 8 ?>
                  </button>
                <?php endif; ?>
              </div>
            </div>

            <!-- SECTION 3: LOẠI SẢN PHẨM -->
            <div class="border border-gray-100 rounded-lg overflow-hidden">
              <div class="bg-[#F6F6F6] px-3.5 py-2.5 flex items-center justify-between cursor-pointer select-none" onclick="toggleSection('sec-type')">
                <span class="font-bold text-xs text-[#222]">Loại sản phẩm <?= count($selectedTypes) > 0 ? '('.count($selectedTypes).')' : '' ?></span>
                <span id="icon-sec-type" class="text-xs text-gray-500 font-bold">▲</span>
              </div>
              <div id="sec-type" class="p-3.5 space-y-2 text-xs text-[#333]">
                <?php foreach ($allowedTypes as $type): 
                  $tCount = $typeCounts[$type] ?? 0;
                  $isChecked = in_array($type, $selectedTypes, true);
                ?>
                  <label class="flex items-center justify-between cursor-pointer hover:text-black">
                    <div class="flex items-center gap-2">
                      <input type="checkbox" name="loai[]" value="<?= htmlspecialchars($type) ?>" onchange="applyFilterAjax()" <?= $isChecked ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-300 text-[#8C5835] focus:ring-[#8C5835]">
                      <span class="<?= $isChecked ? 'font-bold' : '' ?>"><?= htmlspecialchars($type) ?></span>
                    </div>
                    <span class="text-gray-400 text-[11px]">(<?= $tCount ?>)</span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- SECTION 4: MỨC GIÁ (VND) -->
            <div class="border border-gray-100 rounded-lg overflow-hidden">
              <div class="bg-[#F6F6F6] px-3.5 py-2.5 flex items-center justify-between cursor-pointer select-none" onclick="toggleSection('sec-price')">
                <span class="font-bold text-xs text-[#222]">Mức giá (VND)</span>
                <span id="icon-sec-price" class="text-xs text-gray-500 font-bold">▲</span>
              </div>
              <div id="sec-price" class="p-3.5 space-y-3">
                <div class="flex items-center gap-2">
                  <input type="number" min="0" name="gia_tu" value="<?= $minPrice !== null ? htmlspecialchars((string)$minPrice) : '' ?>" placeholder="0" class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-800 focus:outline-none focus:border-[#8C5835]">
                  <span class="text-gray-400 font-bold">-</span>
                  <input type="number" min="0" name="gia_den" value="<?= $maxPrice !== null ? htmlspecialchars((string)$maxPrice) : '' ?>" placeholder="50000000" class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-800 focus:outline-none focus:border-[#8C5835]">
                </div>
                
                <button type="submit" class="w-full bg-[#A48650] hover:bg-[#8C713A] text-white font-bold text-xs py-2.5 rounded-xl transition shadow-sm">
                  Áp dụng
                </button>
              </div>
            </div>

          </div>
        </form>

        <!-- Khung hỗ trợ kỹ sư -->
        <div class="bg-gradient-to-br from-[#2D2319] to-[#4A382C] text-white rounded-2xl p-5 shadow-lg text-xs border border-[#754728]/30">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-[#8C5835] flex items-center justify-center font-bold text-white shadow">
              AT
            </div>
            <div>
              <span class="text-[10px] text-[#D4C7BA] block uppercase tracking-wider">KỸ SƯ LUTHIER</span>
              <strong class="text-sm font-bold block leading-tight">Alex Tuấn (Ext 1402)</strong>
            </div>
          </div>
          <p class="text-[11px] text-[#D4C7BA] leading-relaxed mb-4">
            Bạn cần đo trọng lượng chính xác hay test âm trực tiếp? Tôi sẵn sàng hỗ trợ!
          </p>
          <a href="tel:19008888" class="block text-center bg-[#8C5835] hover:bg-[#724324] text-white font-bold py-2.5 rounded-xl shadow transition">
            Gọi Tư Vấn Ngay
          </a>
        </div>
      </aside>

      <!-- CỘT BÊN PHẢI: CONTAINER SẢN PHẨM & PHÂN TRANG -->
      <section id="product-list-container" class="col-span-12 lg:col-span-9 transition-opacity duration-200">
        
        <!-- Top Toolbar -->
        <div class="bg-white rounded-2xl border border-[#E7E0D8] p-4 mb-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm text-xs">
          <div class="flex items-center gap-2">
            <span class="font-bold text-[#2D2319]">Hiển thị <?= count($display_products) ?> trong tổng số <?= $total_products ?> sản phẩm</span>
            <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-2.5 py-0.5 rounded-full font-bold text-[10px]">100% IN-STOCK</span>
          </div>

          <div class="flex items-center gap-3">
            <!-- Ô TÌM KIẾM TRỰC TIẾP (LIVE SEARCH AJAX) -->
            <div class="relative">
              <input type="text" id="searchInput" name="keyword" value="<?= htmlspecialchars($keyword) ?>" onkeyup="searchProductsAjax()" placeholder="Tìm tên đàn, phụ kiện..." class="bg-[#FBF8F5] border border-[#E7E0D8] rounded-xl pl-3 pr-8 py-2 text-xs text-[#2D2319] focus:outline-none focus:border-[#8C5835]"/>
              <i data-lucide="search" class="w-3.5 h-3.5 text-[#9E948A] absolute right-3 top-1/2 -translate-y-1/2"></i>
            </div>
            
            <!-- SELECT SẮP XẾP SẢN PHẨM -->
            <select id="sortSelect" onchange="changeSortOption(this.value)" class="bg-[#FBF8F5] border border-[#E7E0D8] rounded-xl px-3 py-2 text-xs font-semibold text-[#2D2319] focus:outline-none focus:border-[#8C5835]">
              <option value="cung_loai" <?= $sortBy === 'cung_loai' ? 'selected' : '' ?>>Sản phẩm cùng loại</option>
              <option value="gia_tang" <?= $sortBy === 'gia_tang' ? 'selected' : '' ?>>Giá thấp nhất</option>
              <option value="gia_giam" <?= $sortBy === 'gia_giam' ? 'selected' : '' ?>>Giá cao nhất</option>
              <option value="moi_nhat" <?= $sortBy === 'moi_nhat' ? 'selected' : '' ?>>Hàng mới</option>
            </select>
          </div>
        </div>

        <!-- GRID SẢN PHẨM -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <?php if (empty($display_products)): ?>
            <div class="col-span-full bg-white rounded-2xl border border-[#E7E0D8] p-8 text-center text-[#7A6F64]">
              <p class="font-bold mb-1 text-sm">Không tìm thấy sản phẩm phù hợp</p>
              <p class="text-xs">Vui lòng thử lại với từ khóa khác hoặc điều chỉnh bộ lọc.</p>
            </div>
          <?php else: ?>
            <?php foreach ($display_products as $item): ?>
              <div class="bg-white rounded-[20px] border border-[#E7E0D8] overflow-hidden flex flex-col hover:shadow-md transition">

                  <div class="relative bg-[#FBF8F5] p-5">
                      <?php
                          $originalPrice = (float)$item['original_price'];
                          $salePrice = (float)$item['price'];

                          $discount = ($originalPrice > $salePrice && $originalPrice > 0)
                              ? round((1 - $salePrice / $originalPrice) * 100)
                              : 0;
                      ?>

                      <?php if ($discount > 0): ?>
                          <span class="absolute top-3 left-3 z-10 bg-[#8C5835] text-white text-sm font-bold px-3 py-1 rounded-lg shadow">
                              -<?= $discount ?>%
                          </span>
                      <?php endif; ?>

                      <a href="chitietsanpham.php?type=<?= urlencode($item['product_type']) ?>&id=<?= (int)$item['id'] ?>"
                         class="block h-[300px] sm:h-[320px] bg-white focus:outline-none focus:ring-0 active:outline-none"
                         aria-label="Xem chi tiết <?= htmlspecialchars($item['name']) ?>">
                          <div class="h-full w-full flex items-center justify-center overflow-hidden cursor-pointer">
                              <img src="<?= htmlspecialchars($item['image']) ?>"
                                   alt="<?= htmlspecialchars($item['name']) ?>"
                                   class="w-full h-full object-contain transition duration-300 hover:scale-105" />
                          </div>
                      </a>
                  </div>

                  <div class="p-5 flex flex-col flex-1">
                      <span class="text-sm text-[#8C5835] mb-1">
                          <?= htmlspecialchars($item['brand']) ?>
                      </span>

                      <h3 class="text-lg font-medium text-[#2D2319] leading-snug mb-4">
                          <?= htmlspecialchars($item['name']) ?>
                      </h3>

                      <div class="flex flex-wrap items-baseline gap-2 mb-2 mt-auto">
                          <span class="text-xl font-extrabold text-[#8C5835]">
                              <?= format_vnd($item['price']) ?>
                          </span>

                          <?php if ($discount > 0): ?>
                              <span class="text-sm text-[#9E948A] line-through">
                                  <?= format_vnd($item['original_price']) ?>
                              </span>
                          <?php endif; ?>
                      </div>

                      <div class="text-sm mb-3 pb-1 border-b border-[#E7E0D8] <?= $item['stock'] > 0 ? 'text-emerald-700' : 'text-red-600' ?>">
                          <?= $item['stock'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                      </div>

                      <div class="flex gap-2">
                          <a href="chitietsanpham.php?type=<?= urlencode($item['product_type']) ?>&id=<?= (int)$item['id'] ?>"
                             class="flex-1 border border-[#E7E0D8] text-[#7A6F64] hover:bg-[#FBF8F5] text-sm font-bold py-3 rounded-2xl text-center transition">
                              Chi tiết
                          </a>

                          <form action="gio-hang.php" method="POST" class="flex-1">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_type" value="<?= htmlspecialchars($item['product_type']) ?>">
                            <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">

                            <button type="submit" <?= $item['stock'] <= 0 ? 'disabled' : '' ?>
                                    class="w-full bg-[#8C5835] hover:bg-[#724324] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-bold py-3 rounded-2xl transition shadow">
                                Đặt
                            </button>
                        </form>
                      </div>
                  </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- THANH PHÂN TRANG -->
        <?php if ($total_pages > 1): ?>
          <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#7A6F64] bg-white p-4 rounded-2xl border border-[#E7E0D8] shadow-sm">
            <span>Hiển thị trang <strong><?= $current_page ?></strong> trong tổng số <strong><?= $total_pages ?></strong> trang</span>

            <div class="flex items-center gap-2 font-bold text-xs">
              
              <?php if ($current_page > 1): ?>
                <a href="<?= htmlspecialchars($filterBaseUrl . (strpos($filterBaseUrl, '?') !== false && $filterBaseUrl !== '?' ? '&' : '') . 'page=' . ($current_page - 1)) ?>" 
                   class="ajax-filter-link px-4 py-2 bg-white border border-[#E0D2C4] hover:border-[#8C5835] hover:text-[#8C5835] text-[#2D2319] rounded-2xl transition shadow-sm">
                  ‹ Trước
                </a>
              <?php endif; ?>

              <?php
              $start = max(1, $current_page - 1);
              $end   = min($total_pages, $current_page + 1);

              if ($current_page <= 3) {
                  $start = 1;
                  $end   = min($total_pages, 3);
              }
              ?>

              <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $current_page): ?>
                  <span class="px-4 py-2 bg-[#8C5835] text-white rounded-2xl shadow-md min-w-[38px] text-center">
                    <?= $i ?>
                  </span>
                <?php else: ?>
                  <a href="<?= htmlspecialchars($filterBaseUrl . (strpos($filterBaseUrl, '?') !== false && $filterBaseUrl !== '?' ? '&' : '') . 'page=' . $i) ?>" 
                     class="ajax-filter-link px-4 py-2 bg-white border border-[#E0D2C4] hover:border-[#8C5835] text-[#2D2319] rounded-2xl transition shadow-sm min-w-[38px] text-center">
                    <?= $i ?>
                  </a>
                <?php endif; ?>
              <?php endfor; ?>

              <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?>
                  <span class="px-1 text-[#9E948A] font-bold">...</span>
                <?php endif; ?>
                
                <a href="<?= htmlspecialchars($filterBaseUrl . (strpos($filterBaseUrl, '?') !== false && $filterBaseUrl !== '?' ? '&' : '') . 'page=' . $total_pages) ?>" 
                   class="ajax-filter-link px-4 py-2 bg-white border border-[#2D2319] text-[#2D2319] hover:border-[#8C5835] rounded-2xl transition shadow-sm min-w-[42px] text-center font-bold">
                  <?= $total_pages ?>
                </a>
              <?php endif; ?>

              <?php if ($current_page < $total_pages): ?>
                <a href="<?= htmlspecialchars($filterBaseUrl . (strpos($filterBaseUrl, '?') !== false && $filterBaseUrl !== '?' ? '&' : '') . 'page=' . ($current_page + 1)) ?>" 
                   class="ajax-filter-link px-4 py-2 bg-white border border-[#E0D2C4] hover:border-[#8C5835] hover:text-[#8C5835] text-[#2D2319] rounded-2xl transition shadow-sm font-bold">
                  Sau ›
                </a>
              <?php endif; ?>

            </div>
          </div>
        <?php endif; ?>

      </section>

    </div>
  </section>

</main>

<!-- JAVASCRIPT XỬ LÝ LỌC & TÌM KIẾM AJAX KHÔNG REFRESH TRANG -->
<script>
function applyFilterAjax(targetUrl = null) {
  const form = document.getElementById('filterForm');
  let url = targetUrl;

  if (!url) {
    const formData = new FormData(form);
    url = '?' + new URLSearchParams(formData).toString();
  }

  const productContainer = document.getElementById('product-list-container');
  const tagsContainer = document.getElementById('active-tags-container');

  if (productContainer) productContainer.style.opacity = '0.4';

  fetch(url)
    .then(response => response.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');

      const newProductContent = doc.getElementById('product-list-container');
      if (productContainer && newProductContent) {
        productContainer.innerHTML = newProductContent.innerHTML;
        productContainer.style.opacity = '1';
      }

      const newTagsContent = doc.getElementById('active-tags-container');
      if (tagsContainer && newTagsContent) {
        tagsContainer.innerHTML = newTagsContent.innerHTML;
      }

      history.pushState(null, '', url);
    })
    .catch(err => {
      console.error('Lỗi khi lọc sản phẩm:', err);
      if (productContainer) productContainer.style.opacity = '1';
    });
}

function changeSortOption(sortVal) {
  document.getElementById('hiddenSortInput').value = sortVal;
  
  const currentUrl = new URL(window.location.href);
  currentUrl.searchParams.set('sort', sortVal);
  currentUrl.searchParams.delete('page');

  applyFilterAjax(currentUrl.pathname + currentUrl.search);
}

// Live search tự động tìm kiếm không reload trang
let searchTimeout;
function searchProductsAjax() {
  clearTimeout(searchTimeout);
  
  searchTimeout = setTimeout(() => {
    const keywordVal = document.getElementById('searchInput').value.trim();
    
    const hiddenKeyword = document.getElementById('hiddenKeywordInput');
    if (hiddenKeyword) {
      hiddenKeyword.value = keywordVal;
    }

    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    
    if (keywordVal) {
      formData.set('keyword', keywordVal);
    } else {
      formData.delete('keyword');
    }
    
    formData.delete('page'); // Reset về trang 1

    const url = '?' + new URLSearchParams(formData).toString();
    applyFilterAjax(url);
  }, 300);
}

document.addEventListener('click', function(e) {
  const link = e.target.closest('.ajax-filter-link');
  if (link) {
    e.preventDefault();
    const href = link.getAttribute('href');
    applyFilterAjax(href);
  }
});

window.addEventListener('popstate', function() {
  applyFilterAjax(location.href);
});

function filterBrandList() {
  const query = document.getElementById('searchBrandInput').value.toLowerCase().trim();
  const items = document.querySelectorAll('.brand-item');
  
  items.forEach(item => {
    const name = item.getAttribute('data-name');
    if (name.includes(query)) {
      item.classList.remove('hidden');
    } else {
      item.classList.add('hidden');
    }
  });
}

let isBrandExpanded = false;
function toggleMoreBrands() {
  const hiddenItems = document.querySelectorAll('.hidden-brand');
  const btn = document.getElementById('toggleBrandBtn');
  
  isBrandExpanded = !isBrandExpanded;
  hiddenItems.forEach(item => {
    if (isBrandExpanded) {
      item.classList.remove('hidden');
    } else {
      item.classList.add('hidden');
    }
  });

  btn.innerHTML = isBrandExpanded ? '- Thu gọn' : '+ Xem Thêm ' + hiddenItems.length;
}

function toggleSection(id) {
  const sec = document.getElementById(id);
  const icon = document.getElementById('icon-' + id);
  if (sec.classList.contains('hidden')) {
    sec.classList.remove('hidden');
    if (icon) icon.innerText = '▲';
  } else {
    sec.classList.add('hidden');
    if (icon) icon.innerText = '▼';
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
