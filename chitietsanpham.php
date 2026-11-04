<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/danhgia-helper.php';
// Nhận diện loại sản phẩm: nhạc cụ hoặc phụ kiện.
// Ưu tiên tham số ?id=... đang dùng; đồng thời hỗ trợ các tên tham số
// thường gặp để không làm hỏng liên kết từ những trang cũ.
$id = null;
$idKeys = ['id', 'NC_Ma', 'PK_Ma', 'ma', 'masp', 'maSP', 'idsp', 'idSP'];

foreach ($idKeys as $key) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $candidate = filter_var($_GET[$key], FILTER_VALIDATE_INT);
        if ($candidate !== false && $candidate > 0) {
            $id = $candidate;
            break;
        }
    }
}

// Nếu URL có PK_Ma mà không có type, hiểu đây là trang phụ kiện.
$defaultType = isset($_GET['PK_Ma']) ? 'phukien' : 'nhaccu';
$type = strtolower(trim((string)($_GET['type'] ?? $_GET['loai'] ?? $defaultType)));
if (in_array($type, ['pk', 'accessory', 'phukien'], true)) {
    $type = 'phukien';
} elseif (in_array($type, ['nc', 'instrument', 'nhaccu'], true)) {
    $type = 'nhaccu';
} else {
    $type = $defaultType;
}

if ($id === null) {
    http_response_code(400);
    exit('Không nhận được mã sản phẩm. Hãy mở trang chi tiết từ nút Chi tiết hoặc ảnh sản phẩm trong danh sách.');
}

if ($type === 'phukien') {
    // Phụ kiện có mã PK_Ma riêng, đồng thời LEFT JOIN sang bảng chitietsanpham để lấy thông số kỹ thuật chung
    $sql = "
        SELECT
            pk.PK_Ma AS NC_Ma,
            pk.PK_Ten AS NC_Ten,
            pk.PK_GiaBan AS NC_GiaBan,
            pk.PK_GiaKhuyenMai AS NC_GiaKhuyenMai,
            pk.PK_SoLuongTon AS NC_SoLuongTon,
            pk.PK_HinhAnh AS NC_HinhAnh,
            pk.PK_MoTa AS NC_MoTa,
            NULL AS NC_ThoiGianBaoHanh,
            th.TH_Ten,
            'Phụ kiện' AS LNC_Ten,
            ctsp.*
        FROM phukien AS pk
        LEFT JOIN thuonghieu AS th ON pk.TH_Ma = th.TH_Ma
        LEFT JOIN chitietsanpham AS ctsp ON ctsp.PK_Ma = pk.PK_Ma
        WHERE pk.PK_Ma = :id
          AND pk.PK_TrangThai = 1
    ";
} else {
    // Mặc định là nhạc cụ.
    $sql = "
        SELECT
            nc.*,
            th.TH_Ten,
            lnc.LNC_Ten,
            ctsp.*
        FROM nhaccu AS nc
        INNER JOIN thuonghieu AS th ON nc.TH_Ma = th.TH_Ma
        INNER JOIN loainhaccu AS lnc ON nc.LNC_Ma = lnc.LNC_Ma
        LEFT JOIN chitietsanpham AS ctsp ON ctsp.NC_Ma = nc.NC_Ma
        WHERE nc.NC_Ma = :id
          AND nc.NC_TrangThai = 1
    ";
}

$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    exit($type === 'phukien' ? 'Không tìm thấy phụ kiện.' : 'Không tìm thấy sản phẩm.');
}

/* Lấy danh sách ảnh theo đúng loại và mã sản phẩm */
/* Lấy danh sách ảnh theo đúng loại và mã sản phẩm */
$stmtImages = $conn->prepare("
    SELECT HASP_DuongDan
    FROM hinhanhsanpham
    WHERE HASP_LoaiSanPham = :loai
      AND HASP_MaSanPham = :id
    ORDER BY HASP_ThuTu ASC, HASP_Ma ASC
");

$stmtImages->execute([
    'loai' => $type === 'phukien' ? 'phukien' : 'nhaccu',
    'id'   => $id
]);

$galleryImages = [];

// 1. Đưa Ảnh chính trong bảng sản phẩm vào vị trí đầu tiên (Ảnh nhỏ 1)
$mainImage = trim((string)($product['NC_HinhAnh'] ?? ''));
if ($mainImage !== '') {
    if (!preg_match('~^(https?://|/)~i', $mainImage)) {
        $mainImage = 'images/' . ltrim($mainImage, '/');
    }
    $galleryImages[] = $mainImage;
}

// 2. Nạp thêm 3 Ảnh phụ từ bảng hinhanhsanpham vào mảng (Ảnh nhỏ 2, 3, 4)
while ($row = $stmtImages->fetch(PDO::FETCH_ASSOC)) {
    $path = trim($row['HASP_DuongDan']);

    if ($path === '') {
        continue;
    }

    if (!preg_match('~^(https?://|/)~i', $path)) {
        $path = 'images/' . ltrim($path, '/');
    }

    if (!in_array($path, $galleryImages, true)) {
        $galleryImages[] = $path;
    }
}

// Ảnh hiển thị lớn mặc định là ảnh đầu tiên (ảnh chính)
$image = $galleryImages[0] ?? '';


$page_title = $product['NC_Ten'];
$isAccessory = ($type === 'phukien');
$reviewSummary = dg_get_summary($conn, $type, (int)$id);
require_once __DIR__ . '/includes/header.php';
?>
<main class="bg-[#fff] text-[#111] min-h-screen py-6">
    <div class="max-w-6xl mx-auto px-4">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-7">
            <!-- Hình ảnh sản phẩm: bám theo khi cuộn, dừng ở cuối khu vực thông tin -->
            
<div class="product-image-sticky">
    <?php if (!empty($galleryImages)): ?>
        <!-- Khung tổng: ảnh chính và toàn bộ ảnh nhỏ cùng nằm bên trong -->
        <div class="product-gallery">
            <div id="product-image-box" class="product-image-box">
                <img
                    id="product-main-image"
                    src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($product['NC_Ten'], ENT_QUOTES, 'UTF-8') ?>"
                    class="product-main-image"
                >
            </div>

            <div class="product-gallery-bottom">
                <div class="product-thumbnails">
                    <?php foreach ($galleryImages as $i => $thumb): ?>
                        <button
                            type="button"
                            class="thumbnail-btn <?= $i === 0 ? 'active' : '' ?>"
                            data-image="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') ?>"
                            aria-label="Xem ảnh <?= $i + 1 ?>"
                            aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"
                        >
                            <img
                                src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') ?>"
                                alt="Ảnh <?= $i + 1 ?> của <?= htmlspecialchars($product['NC_Ten'], ENT_QUOTES, 'UTF-8') ?>"
                                loading="lazy"
                            >
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php if (count($galleryImages) > 4): ?>
                    <button type="button" class="gallery-view-all" id="gallery-view-all">
                        <span class="gallery-arrow">▶</span>
                        <span>View All<br><?= count($galleryImages) ?></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="product-gallery no-product-image">Chưa có hình ảnh</div>
    <?php endif; ?>
</div>


            <!-- Thông tin sản phẩm lấy từ cơ sở dữ liệu -->
            <div class="product-info">
                <p class="text-sm text-gray-500 mb-1">
                    <?= htmlspecialchars($product['TH_Ten']) ?>
                </p>

                <h1 class="text-2xl font-semibold mb-3">
                    <?= htmlspecialchars($product['NC_Ten']) ?>
                </h1>

                <?php
                $price = $product['NC_GiaKhuyenMai'];
                if ($price === null || $price <= 0) {
                    $price = $product['NC_GiaBan'];
                }
                ?>

                <div class="text-sm text-gray-600 mb-3">
                    <a href="#danh-gia" class="no-underline text-gray-600">
                        <?= dg_stars($reviewSummary['avg']) ?>
                        <span class="ml-1">(<?= (int)$reviewSummary['total'] ?>)</span>
                    </a>
                    <span class="mx-2">|</span>
                    <span><?= (int)$product['NC_SoLuongTon'] > 0 ? 'Còn hàng' : 'Hết hàng' ?></span>
                </div>

                <div class="bg-[#f5f4f1] px-4 py-4 mb-3 rounded-sm">
                    <p class="text-2xl font-semibold text-[#9d8844] mb-1">
                        <?= format_vnd($price) ?>
                    </p>
                    <?php if (
                        $product['NC_GiaKhuyenMai'] !== null
                        && $product['NC_GiaKhuyenMai'] > 0
                    ): ?>
                        <p class="text-sm text-gray-500 line-through mb-3">
                            <?= format_vnd($product['NC_GiaBan']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="flex flex-wrap items-center gap-2">
    <form action="gio-hang.php" method="post" class="flex flex-wrap items-center gap-2 w-full">
    <input type="hidden" name="action" value="add">
    <!-- Bổ sung input product_type để phân biệt nhạc cụ hay phụ kiện -->
    <input type="hidden" name="product_type" value="<?= htmlspecialchars($type) ?>">
    <input type="hidden" name="product_id" value="<?= (int)$product['NC_Ma'] ?>">
    
    <label for="product-quantity" class="sr-only">Số lượng</label>
    <input
        id="product-quantity"
        type="number"
        name="so_luong"
        value="1"
        min="1"
        max="<?= max(1, (int)$product['NC_SoLuongTon']) ?>"
        class="w-16 h-10 border border-[#b49a4a] rounded px-2 bg-white"
        <?= (int)$product['NC_SoLuongTon'] <= 0 ? 'disabled' : '' ?>
    >
    <button
        type="submit"
        class="flex-1 min-w-[180px] h-10 px-5 rounded bg-[#a28d46] text-white font-semibold hover:bg-[#8e7938] disabled:opacity-50"
        <?= (int)$product['NC_SoLuongTon'] <= 0 ? 'disabled' : '' ?>
    >
        🛒 Thêm vào giỏ hàng
    </button>
</form>
                    </div>
                </div>

            <section class="bg-[#f5f4f1] rounded-sm overflow-hidden">
            <h2 class="px-4 py-3 font-semibold border-b-2 border-black">Thông tin sản phẩm</h2>
            <div class="px-4 pt-2 pb-4">
                <h3 class="font-semibold mb-1">Về sản phẩm</h3>
                <!-- Đã bỏ class whitespace-pre-line ở div này -->
                <div class="leading-6 text-sm text-gray-800">
                    <?php if (!empty($product['NC_MoTa'])): ?>
                        <?= nl2br(trim(htmlspecialchars($product['NC_MoTa']))) ?>
                    <?php else: ?>
                        Chưa có mô tả cho sản phẩm này.
                    <?php endif; ?>
                </div>
                   <div class="mt-5 border-t border-gray-300 pt-4 text-sm leading-6">
                    <p><strong>Mã sản phẩm:</strong> <?= htmlspecialchars((string)$product['NC_Ma']) ?></p>
                    <p><strong>Thương hiệu:</strong> <?= htmlspecialchars($product['TH_Ten']) ?></p>
                    <p><strong><?= $isAccessory ? 'Loại phụ kiện:' : 'Loại nhạc cụ:' ?></strong> <?= htmlspecialchars($product['LNC_Ten']) ?></p>
                    
                    <!-- Chỉ hiển thị dòng bảo hành nếu KHÔNG phải là phụ kiện (tức là nhạc cụ mới có) -->
                    <?php if (!$isAccessory && !empty($product['NC_ThoiGianBaoHanh'])): ?>
                        <p><strong>Bảo hành:</strong> <?= htmlspecialchars((string)$product['NC_ThoiGianBaoHanh']) ?> tháng</p>
                    <?php endif; ?>

                    <p><strong>Tình trạng:</strong>
                        <?php if ((int)$product['NC_SoLuongTon'] > 0): ?>
                            <span class="text-green-700">Còn hàng (<?= (int)$product['NC_SoLuongTon'] ?>)</span>
                        <?php else: ?>
                            <span class="text-red-600">Hết hàng</span>
                        <?php endif; ?>
                    </p>
                </div>

                <?php
                // Ánh xạ tên cột thông số kỹ thuật sang nhãn hiển thị.
                $specLabels = [
                    'CTSP_ChatLieu' => 'Chất liệu',
                    'CTSP_MauSac' => 'Màu sắc',
                    'CTSP_KichThuoc' => 'Kích thước',
                    'CTSP_TrongLuong' => 'Trọng lượng (kg)',
                    'CTSP_SoDay' => 'Số dây đàn',
                    'CTSP_ChatLieuDay' => 'Chất liệu dây đàn',
                    'CTSP_ChatLieuThan' => 'Chất liệu thân đàn',
                    'CTSP_ChatLieuCan' => 'Chất liệu cần đàn',
                    'CTSP_KieuDan' => 'Kiểu đàn',
                    'CTSP_CoDan' => 'Cỡ đàn',
                    'CTSP_SoPhim' => 'Số phím đàn',
                    'CTSP_SoGiaiDieu' => 'Số điệu',
                    'CTSP_SoAmSac' => 'Số âm sắc',
                    'CTSP_CongSuat' => 'Công suất (W)',
                    'CTSP_LoaiPhim' => 'Loại phím đàn',
                    'CTSP_CheDoDan' => 'Chế độ đàn',
                    'CTSP_SoTrong' => 'Số trống',
                    'CTSP_SoLa' => 'Số lá',
                    'CTSP_ChatLieuMatTrong' => 'Chất liệu mặt trống',
                    'CTSP_LoaiTrong' => 'Loại trống',
                    'CTSP_LoaiPhuKien' => 'Loại phụ kiện',
                    'CTSP_TuongThich' => 'Tương thích',
                    'CTSP_CongDung' => 'Công dụng',
                    'CTSP_QuyCach' => 'Quy cách',
                    'CTSP_MoTaKyThuat' => 'Mô tả kỹ thuật',
                ];

                $hasSpecifications = false;
                foreach ($specLabels as $column => $label) {
                    if (isset($product[$column]) && $product[$column] !== '') {
                        $hasSpecifications = true;
                        break;
                    }
                }
                ?>

                <!-- Thông số kỹ thuật hiển thị sẵn trong ô Thông tin sản phẩm -->
                <div class="mt-5 border-t border-gray-300 pt-4"> 
                    <h3 class="font-semibold mb-4">Thông số kỹ thuật</h3>
                        <?php if ($hasSpecifications): ?>
                            <dl class="space-y-3 text-sm">
                                <?php foreach ($specLabels as $column => $label): ?>
                                    <?php
                                    $value = $product[$column] ?? null;
                                    if ($value === null || $value === '') {
                                        continue;
                                    }
                                    ?>
                                    <div class="grid grid-cols-1 sm:grid-cols-[120px_minmax(0,1fr)] gap-1 sm:gap-3">
                                        <dt class="text-[#a28d46]"><?= htmlspecialchars($label) ?></dt>
                                        <dd class="text-gray-900 whitespace-pre-line"><?= nl2br(htmlspecialchars((string)$value)) ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">
                                Chưa có thông tin chi tiết cho sản phẩm này.
                            </p>
                        <?php endif; ?>
                </div>
                    </div>
                </section>
            </div>
        </div>

        <section class="mt-8 rounded-sm bg-[#f8f4e9] px-5 py-4 text-center">
            <h2 class="font-semibold text-[#a28d46] mb-1">ƯU ĐIỂM SẢN PHẨM</h2>
            <p class="text-sm font-medium">Chất lượng đáng tin cậy · Thiết kế tiện dụng · Phù hợp nhu cầu sử dụng</p>
        </section>

        <?php /* TODO: nếu có mục "Phụ kiện kèm theo", đặt nó ở đây — khu vực đánh giá nằm ngay bên dưới */ ?>

        <?php require __DIR__ . '/includes/danhgia.php'; ?>
    </div>
</main>

<style>
  
/* Khung bao quanh toàn bộ thư viện ảnh */
.product-gallery {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    border: 1px solid #b49a4a;
    background: #fff;
    padding: 0 10px 8px;
}

/* Khung ảnh chính: giảm chiều cao */
.product-image-box {
    width: 100%;
    height: 390px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: none;
    cursor: zoom-in;
}

/* Ảnh chính luôn nằm gọn trong khung */
.product-main-image {
    display: block;
    max-width: 100%;
    max-height: 100%;
    width: 100%;
    height: 100%;
    object-fit: contain;
    transform: scale(1);
    transform-origin: center center;
    transition: transform 0.12s ease-out;
}

/* Dãy ảnh nhỏ */
.product-gallery-bottom {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 4px;
    min-height: 66px;
}

/* Ảnh nhỏ vừa phải */
.product-thumbnails {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    max-width: calc(100% - 65px);
    overflow-x: auto;
}

.thumbnail-btn {
    flex: 0 0 52px;
    width: 52px;
    height: 60px;
    padding: 2px;
    border: 1px solid transparent;
    background: #fff;
    cursor: pointer;
}

.thumbnail-btn img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.thumbnail-btn.active {
    background: #f1eee5;
    border-color: #b49a4a;
}

/* Nút View All */
.gallery-view-all {
    flex: 0 0 55px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    font-size: 12px;
    text-align: center;
    color: #333;
}

.gallery-arrow {
    color: #a28d46;
    font-size: 25px;
}

/* Không để khung ảnh chiếm quá nhiều chiều ngang */
/* Giữ khung ảnh bám theo khi cuộn trang */
.product-image-sticky {
    position: sticky;
    top: 150px;
    align-self: start;

    width: 100%;
    max-width: 100%;
    min-width: 0;
}

/* Màn hình nhỏ */
@media (max-width: 600px) {
    .product-image-box {
        height: 300px;
    }

    .thumbnail-btn {
        flex-basis: 44px;
        width: 44px;
        height: 52px;
    }

    .product-gallery {
        padding: 0 5px 6px;
    }
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('product-image-box');
    const image = document.getElementById('product-main-image');
    const buttons = document.querySelectorAll('.thumbnail-btn');
    const viewAllButton = document.getElementById('gallery-view-all');
    const thumbnails = document.querySelector('.product-thumbnails');

    if (box && image) {
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                image.src = button.dataset.image;
                image.style.transform = 'scale(1)';
                image.style.transformOrigin = 'center center';
                box.classList.remove('is-zoomed');

                buttons.forEach(function (item) {
                    item.classList.remove('active');
                    item.setAttribute('aria-pressed', 'false');
                });

                button.classList.add('active');
                button.setAttribute('aria-pressed', 'true');
            });
        });

        box.addEventListener('mousemove', function (event) {
            const rect = box.getBoundingClientRect();
            const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100));
            const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100));
            image.style.transformOrigin = x + '% ' + y + '%';
            image.style.transform = 'scale(2)';
            box.classList.add('is-zoomed');
        });

        box.addEventListener('mouseleave', function () {
            image.style.transform = 'scale(1)';
            image.style.transformOrigin = 'center center';
            box.classList.remove('is-zoomed');
        });
    }

    // Nút View All cuộn qua dãy ảnh nhỏ; các ảnh vẫn nằm trong cùng khung.
    if (viewAllButton && thumbnails) {
        viewAllButton.addEventListener('click', function () {
            thumbnails.scrollTo({ left: thumbnails.scrollWidth, behavior: 'smooth' });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>