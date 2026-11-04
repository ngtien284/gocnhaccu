<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/danhgia-helper.php';

function dg_back(string $msg, bool $ok, string $back): never
{
    $_SESSION['dg_flash'] = ['ok' => $ok, 'msg' => $msg];
    // Chỉ cho quay lại đường dẫn nội bộ để tránh open redirect.
    if ($back === '' || $back[0] !== '/' || str_starts_with($back, '//')) {
        $back = 'index.php';
    }
    header('Location: ' . preg_replace('/#.*$/', '', $back) . '#danh-gia');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Phương thức không hợp lệ.');
}

$back = (string)($_POST['back'] ?? '');

if (!hash_equals($_SESSION['dg_csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
    dg_back('Phiên làm việc hết hạn, vui lòng thử lại.', false, $back);
}

$customer = dg_current_customer();
if (!$customer) {
    dg_back('Bạn cần đăng nhập để đánh giá.', false, $back);
}

$type      = ($_POST['type'] ?? '') === 'phukien' ? 'phukien' : 'nhaccu';
$productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
$stars     = filter_var($_POST['so_sao'] ?? null, FILTER_VALIDATE_INT);
$title     = trim((string)($_POST['tieu_de'] ?? ''));
$content   = trim((string)($_POST['noi_dung'] ?? ''));
$name      = trim((string)($_POST['ten_hien_thi'] ?? ''));
$region    = trim((string)($_POST['khu_vuc'] ?? ''));
$recommend = isset($_POST['gioi_thieu']) ? 1 : 0;

if (!$productId || $productId < 1 || !$stars || $stars < 1 || $stars > 5
    || $title === '' || mb_strlen($title) > 150
    || mb_strlen($content) < 10 || mb_strlen($content) > 3000
    || $name === '' || mb_strlen($name) > 100 || mb_strlen($region) > 100) {
    dg_back('Thông tin đánh giá chưa hợp lệ, vui lòng kiểm tra lại.', false, $back);
}

if (!dg_customer_has_purchased($conn, $customer['id'], $type, $productId)) {
    dg_back('Chỉ khách hàng đã mua sản phẩm này mới có thể đánh giá.', false, $back);
}
if (dg_has_reviewed($conn, $customer['id'], $type, $productId)) {
    dg_back('Bạn đã đánh giá sản phẩm này rồi.', false, $back);
}

$stmt = $conn->prepare("
    INSERT INTO danhgia
        (DG_LoaiSanPham, DG_MaSanPham, KH_Ma, DG_SoSao, DG_TieuDe, DG_NoiDung,
         DG_GioiThieu, DG_TenHienThi, DG_KhuVuc, DG_TrangThai)
    VALUES (:loai, :id, :kh, :sao, :tieude, :noidung, :gt, :ten, :kv, 1)
");
$stmt->execute([
    'loai' => $type, 'id' => $productId, 'kh' => $customer['id'], 'sao' => $stars,
    'tieude' => $title, 'noidung' => $content, 'gt' => $recommend,
    'ten' => $name, 'kv' => $region !== '' ? $region : null,
]);

dg_back('Cảm ơn bạn! Đánh giá đã được đăng.', true, $back);
