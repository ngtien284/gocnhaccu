<?php
// promotions.php - Trang danh sách nhạc cụ khuyến mãi (giảm giá trực tiếp)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nạp cấu hình TRƯỚC khi xuất HTML (header.php sẽ in HTML ngay khi được include)
require_once __DIR__ . '/config/database.php'; // tạo biến $conn (PDO)
require_once __DIR__ . '/config/app.php';      // format_vnd(), hằng số SITE_*

// Hàm escape HTML dùng chung trong trang
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// Chuẩn hóa đường dẫn ảnh giống chitietsanpham.php
if (!function_exists('promo_image_url')) {
    function promo_image_url(?string $image): string
    {
        $image = trim((string)$image);
        if ($image === '') {
            return '';
        }
        if (preg_match('~^https?://~i', $image)) {
            return $image;
        }
        return 'images/' . ltrim($image, '/');
    }
}

/* ==========================================================================
   1. LỌC VÀ SẮP XẾP
   ========================================================================== */
$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: 0;
$sort        = (string)($_GET['sort'] ?? 'discount_desc');

// Chỉ cho phép các giá trị sắp xếp hợp lệ (tránh SQL injection / giá trị lạ)
$orderMap = [
    'discount_desc' => 'PhanTramGiam DESC, nc.NC_GiaKhuyenMai ASC',
    'price_asc'     => 'nc.NC_GiaKhuyenMai ASC',
    'price_desc'    => 'nc.NC_GiaKhuyenMai DESC',
];
if (!isset($orderMap[$sort])) {
    $sort = 'discount_desc';
}

$categories     = [];
$promo_products = [];
$max_discount   = 0;

try {
    // Danh sách loại nhạc cụ cho ô lọc
    $categories = $conn->query("SELECT LNC_Ma, LNC_Ten FROM loainhaccu ORDER BY LNC_Ten")
                       ->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
        SELECT
            nc.NC_Ma,
            nc.NC_Ten,
            nc.NC_GiaBan,
            nc.NC_GiaKhuyenMai,
            nc.NC_SoLuongTon,
            nc.NC_HinhAnh,
            th.TH_Ten,
            lnc.LNC_Ten,
            ROUND(((nc.NC_GiaBan - nc.NC_GiaKhuyenMai) / nc.NC_GiaBan) * 100) AS PhanTramGiam
        FROM nhaccu AS nc
        INNER JOIN thuonghieu AS th  ON nc.TH_Ma  = th.TH_Ma
        INNER JOIN loainhaccu AS lnc ON nc.LNC_Ma = lnc.LNC_Ma
        WHERE nc.NC_TrangThai = 1
          AND nc.NC_GiaBan > 0
          AND nc.NC_GiaKhuyenMai IS NOT NULL
          AND nc.NC_GiaKhuyenMai > 0
          AND nc.NC_GiaKhuyenMai < nc.NC_GiaBan
    ";
    $params = [];

    if ($category_id > 0) {
        $sql .= " AND nc.LNC_Ma = :category_id";
        $params['category_id'] = $category_id;
    }

    $sql .= " ORDER BY " . $orderMap[$sort];

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $promo_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($promo_products as $p) {
        $max_discount = max($max_discount, (int)$p['PhanTramGiam']);
    }
} catch (PDOException $ex) {
    error_log('Lỗi truy vấn trang khuyến mãi: ' . $ex->getMessage());
    $promo_products = [];
}

// Header in ra <html>, <head>, menu... nên phải include SAU phần xử lý ở trên
$page_title = 'Khuyến Mãi & Voucher';
require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-1">

    <!-- 2. BANNER KHUYẾN MÃI -->
    <section class="bg-gradient-to-r from-[#5F371D] via-[#8C5835] to-[#B8977E] text-white">
        <div class="max-w-7xl mx-auto px-6 py-10 text-center">
            <h1 class="text-2xl md:text-3xl font-bold font-serif mb-2">
                Săn Deal Nhạc Cụ – Giá Tốt Mỗi Ngày
            </h1>
            <p class="text-sm md:text-base text-[#EFE7DF]">
                <?php if ($max_discount > 0): ?>
                    Giảm giá trực tiếp tới <strong><?= (int)$max_discount ?>%</strong> cho nhạc cụ chính hãng. Không cần nhập mã!
                <?php else: ?>
                    Theo dõi để nhận ưu đãi nhạc cụ chính hãng mới nhất.
                <?php endif; ?>
            </p>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- 3. THANH LỌC & SẮP XẾP -->
        <form action="promotions.php" method="GET"
              class="bg-white border border-[#E7E0D8] rounded-2xl shadow-sm p-4 mb-8 grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-5">
                <label for="category_id" class="block text-xs font-bold text-[#7A6F64] mb-1">Lọc theo loại nhạc cụ</label>
                <select id="category_id" name="category_id" onchange="this.form.submit()"
                        class="w-full border border-[#E7E0D8] rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#8C5835]/30 focus:border-[#8C5835]">
                    <option value="0">Tất cả loại nhạc cụ</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['LNC_Ma'] ?>" <?= $category_id === (int)$cat['LNC_Ma'] ? 'selected' : '' ?>>
                            <?= e($cat['LNC_Ten']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-5">
                <label for="sort" class="block text-xs font-bold text-[#7A6F64] mb-1">Sắp xếp theo</label>
                <select id="sort" name="sort" onchange="this.form.submit()"
                        class="w-full border border-[#E7E0D8] rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#8C5835]/30 focus:border-[#8C5835]">
                    <option value="discount_desc" <?= $sort === 'discount_desc' ? 'selected' : '' ?>>Giảm giá nhiều nhất</option>
                    <option value="price_asc"     <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá khuyến mãi: thấp đến cao</option>
                    <option value="price_desc"    <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá khuyến mãi: cao đến thấp</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <a href="promotions.php"
                   class="block text-center w-full border border-[#E7E0D8] rounded-lg px-3 py-2 text-sm font-semibold text-[#5C5349] hover:border-[#8C5835] hover:text-[#8C5835] transition">
                    Đặt lại
                </a>
            </div>
        </form>

        <!-- 4. LƯỚI SẢN PHẨM -->
        <?php if (!empty($promo_products)): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                <?php foreach ($promo_products as $item): ?>
                    <?php
                    $detailUrl = 'chitietsanpham.php?type=nhaccu&id=' . (int)$item['NC_Ma'];
                    $imageUrl  = promo_image_url($item['NC_HinhAnh']);
                    $inStock   = (int)$item['NC_SoLuongTon'] > 0;
                    ?>
                    <article class="relative bg-white border border-[#E7E0D8] rounded-2xl shadow-sm overflow-hidden flex flex-col hover:shadow-md hover:border-[#B8977E] transition">

                        <!-- Badge % giảm -->
                        <span class="absolute top-0 right-0 z-10 bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-bl-xl shadow">
                            -<?= (int)$item['PhanTramGiam'] ?>%
                        </span>

                        <!-- Tag thương hiệu -->
                        <span class="absolute top-0 left-0 z-10 bg-[#2D2319] text-white text-[11px] px-2.5 py-1 rounded-br-xl">
                            <?= e($item['TH_Ten']) ?>
                        </span>

                        <!-- Ảnh -->
                        <a href="<?= e($detailUrl) ?>" class="block bg-[#FBF8F5] p-4">
                            <?php if ($imageUrl !== ''): ?>
                                <img src="<?= e($imageUrl) ?>" alt="<?= e($item['NC_Ten']) ?>"
                                     loading="lazy" class="w-full h-44 object-contain">
                            <?php else: ?>
                                <div class="w-full h-44 flex items-center justify-center text-xs text-[#9E948A]">
                                    Chưa có hình ảnh
                                </div>
                            <?php endif; ?>
                        </a>

                        <!-- Thông tin -->
                        <div class="p-4 flex flex-col flex-1">
                            <span class="text-xs text-[#7A6F64] mb-1"><?= e($item['LNC_Ten']) ?></span>
                            <h2 class="text-sm font-bold leading-snug mb-3 line-clamp-2 min-h-[2.5rem]">
                                <a href="<?= e($detailUrl) ?>" class="text-[#2D2319] hover:text-[#8C5835] transition">
                                    <?= e($item['NC_Ten']) ?>
                                </a>
                            </h2>

                            <div class="mt-auto">
                                <p class="text-lg font-bold text-red-600">
                                    <?= format_vnd($item['NC_GiaKhuyenMai']) ?>
                                </p>
                                <p class="text-xs text-[#9E948A] line-through mb-3">
                                    <?= format_vnd($item['NC_GiaBan']) ?>
                                </p>

                                <div class="flex items-center justify-between pt-3 border-t border-[#E7E0D8]">
                                    <?php if ($inStock): ?>
                                        <span class="text-xs font-semibold text-emerald-700">Sẵn hàng</span>
                                    <?php else: ?>
                                        <span class="text-xs font-semibold text-red-600">Hết hàng</span>
                                    <?php endif; ?>

                                    <a href="<?= e($detailUrl) ?>"
                                       class="bg-[#8C5835] hover:bg-[#754728] text-white text-xs font-bold px-4 py-1.5 rounded-full transition">
                                        Săn ngay
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center bg-white border border-[#E7E0D8] rounded-2xl shadow-sm py-16 px-6">
                <h2 class="font-bold text-[#2D2319] mb-2">Chưa có chương trình giảm giá nào trong danh mục này</h2>
                <p class="text-sm text-[#7A6F64] mb-5">
                    Vui lòng chọn danh mục khác hoặc quay lại sau để xem các ưu đãi mới nhất.
                </p>
                <a href="promotions.php"
                   class="inline-block bg-[#8C5835] hover:bg-[#754728] text-white text-sm font-bold px-5 py-2 rounded-full transition">
                    Xem tất cả khuyến mãi
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>