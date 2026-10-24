<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

// Nhận diện loại sản phẩm: nhạc cụ hoặc phụ kiện.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$type = strtolower(trim((string)($_GET['type'] ?? 'nhaccu')));

if (!$id || $id < 1) {
    exit('Mã sản phẩm không hợp lệ.');
}

if ($type === 'phukien') {
    // Phụ kiện có mã PK_Ma riêng, không được truy vấn nhầm sang bảng nhaccu.
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
            'Phụ kiện' AS LNC_Ten
        FROM phukien AS pk
        LEFT JOIN thuonghieu AS th ON pk.TH_Ma = th.TH_Ma
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

// Xử lý đường dẫn ảnh
$image = $product['NC_HinhAnh'] ?? '';

if ($image !== '' && !preg_match('~^https?://~i', $image)) {
    $image = 'images/' . ltrim($image, '/');
}

$page_title = $product['NC_Ten'];
$isAccessory = ($type === 'phukien');
require_once __DIR__ . '/includes/header.php';
?>
<main class="bg-[#fff] text-[#111] min-h-screen py-6">
    <div class="max-w-6xl mx-auto px-4">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-7">
            <!-- Hình ảnh sản phẩm: bám theo khi cuộn, dừng ở cuối khu vực thông tin -->
            <div class="product-image-sticky">
                <?php if ($image !== ''): ?>
                    <div id="product-image-box"
     class="product-image-box relative h-[360px] md:h-[450px] overflow-hidden border border-[#b49a4a] bg-white flex items-center justify-center p-1">
                        <img
                            id="product-main-image"
                            src="<?= htmlspecialchars($image) ?>"
                            alt="<?= htmlspecialchars($product['NC_Ten']) ?>"
                            class="product-main-image w-full h-full object-contain"
                        >
                    </div>
                <?php else: ?>
                    <div class="h-80 flex items-center justify-center bg-gray-100">
                        Chưa có hình ảnh
                    </div>
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
                    <span class="text-yellow-500">☆☆☆☆☆</span>
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
                    <div class="px-4 py-4">
                    <h3 class="font-semibold mb-3">Về sản phẩm</h3>
                    <div class="leading-6 text-sm text-gray-800 whitespace-pre-line">
                        <?php if (!empty($product['NC_MoTa'])): ?>
                            <?= nl2br(htmlspecialchars($product['NC_MoTa'])) ?>
                        <?php else: ?>
                            Chưa có mô tả cho sản phẩm này.
                        <?php endif; ?>
                    </div>

                    <div class="mt-5 border-t border-gray-300 pt-4 text-sm leading-6">
                        <p><strong>Mã sản phẩm:</strong> <?= htmlspecialchars((string)$product['NC_Ma']) ?></p>
                        <p><strong>Thương hiệu:</strong> <?= htmlspecialchars($product['TH_Ten']) ?></p>
                        <p><strong><?= $isAccessory ? 'Loại phụ kiện:' : 'Loại nhạc cụ:' ?></strong> <?= htmlspecialchars($product['LNC_Ten']) ?></p>
                        <p><strong>Bảo hành:</strong> <?= htmlspecialchars((string)$product['NC_ThoiGianBaoHanh']) ?> tháng</p>
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
    </div>
</main>

<style>
    /* Giữ ảnh theo màn hình khi cuộn trên máy tính.
       Ảnh chỉ bám trong hàng sản phẩm nên sẽ dừng trước phần bên dưới. */
    @media (min-width: 1024px) {
        .product-image-sticky {
            position: sticky;
            top: 24px;
            align-self: start;
        }
    }

    .product-image-box {
        cursor: zoom-in;
    }

    .product-main-image {
        transition: transform 0.12s ease-out;
        transform: scale(1);
        transform-origin: center center;
        will-change: transform;
    }

    .product-image-box.is-zoomed {
        cursor: zoom-out;
    }

    @media (hover: hover) and (pointer: fine) {
        .product-image-box {
            touch-action: pan-y;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('product-image-box');
    const image = document.getElementById('product-main-image');
    if (!box || !image) return;

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

});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>