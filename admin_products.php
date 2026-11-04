<?php
declare(strict_types=1);

/**
 * admin_products.php
 * Thêm / sửa / xóa / ẩn-hiện sản phẩm, dùng chung cho NHẠC CỤ (nhaccu) và PHỤ KIỆN (phukien).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

/* ============================================================
 * CẤU HÌNH
 * ============================================================ */
const AP_LOGIN_URL  = 'login.php';            // Đã sửa đường dẫn trang đăng nhập
const AP_IMAGE_DIR  = __DIR__ . '/images/';
const AP_PER_PAGE   = 12;
const AP_MAX_UPLOAD = 5 * 1024 * 1024;

/** Kiểm tra quyền admin */
function ap_is_admin(): bool
{
    $candidates = [
        $_SESSION['user']['LND_Ma'] ?? null,
        $_SESSION['user']['role'] ?? null,
        $_SESSION['LND_Ma'] ?? null,
        $_SESSION['role'] ?? null,
        $_SESSION['is_admin'] ?? null,
    ];
    foreach ($candidates as $v) {
        if ($v === null) continue;
        if ($v === 1 || $v === '1' || $v === true) return true;
        if (is_string($v) && strtolower($v) === 'admin') return true;
    }

    $email = is_string($_SESSION['user'] ?? null) ? $_SESSION['user'] : ($_SESSION['user']['email'] ?? '');
    $name  = $_SESSION['user_name'] ?? '';
    if (strpos(strtolower($email), 'admin') !== false || mb_strpos($name, 'Quản Trị') !== false) {
        return true;
    }

    return false;
}

/* ============================================================
 * HÀM TIỆN ÍCH
 * ============================================================ */
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function ap_csrf(): string
{
    if (empty($_SESSION['ap_csrf'])) {
        $_SESSION['ap_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ap_csrf'];
}

function ap_check_csrf($token): bool
{
    return is_string($token) && !empty($_SESSION['ap_csrf']) && hash_equals($_SESSION['ap_csrf'], $token);
}

function ap_flash(string $kind, string $msg): void
{
    $_SESSION['ap_flash'] = ['kind' => $kind, 'msg' => $msg];
}

function ap_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function ap_money_out($v): string
{
    return number_format((float)$v, 0, ',', '.') . ' ₫';
}

function ap_money_in($raw)
{
    $raw = trim((string)$raw);
    if ($raw === '') return null;
    $digits = preg_replace('/[\s.,₫đd]/iu', '', $raw);
    if ($digits === '' || !ctype_digit($digits) || strlen($digits) > 13) return false;
    return (int)$digits;
}

function ap_image_url(?string $name): string
{
    $name = (string)$name;
    if ($name === '') return '';
    return preg_match('~^https?://~i', $name) ? $name : 'images/' . ltrim($name, '/');
}

function ap_slug(string $name): string
{
    $name = str_replace(['đ', 'Đ'], 'd', $name);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $ascii = strtolower($ascii === false ? '' : $ascii);
    $ascii = trim((string)preg_replace('/[^a-z0-9]+/', '-', $ascii), '-');
    return $ascii !== '' ? substr($ascii, 0, 40) : 'sp';
}

function ap_upload_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Tải ảnh lên thất bại (mã lỗi ' . (int)$file['error'] . ').'];
    if ($file['size'] > AP_MAX_UPLOAD) return [null, 'Ảnh vượt quá 5 MB.'];

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
    if ($ext === null) return [null, 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.'];

    if (!is_dir(AP_IMAGE_DIR) && !mkdir(AP_IMAGE_DIR, 0755, true)) return [null, 'Không tạo được thư mục images/.'];

    $base = ap_slug(pathinfo((string)$file['name'], PATHINFO_FILENAME));
    $name = $base . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], AP_IMAGE_DIR . $name)) return [null, 'Không lưu được file ảnh vào thư mục images/.'];

    return [$name, null];
}

function ap_cfg(string $type): array
{
    return $type === 'phukien'
        ? ['type' => 'phukien', 'table' => 'phukien', 'pk' => 'PK_Ma', 'p' => 'PK', 'label' => 'phụ kiện', 'Label' => 'Phụ kiện']
        : ['type' => 'nhaccu',  'table' => 'nhaccu',  'pk' => 'NC_Ma', 'p' => 'NC', 'label' => 'nhạc cụ',  'Label' => 'Nhạc cụ'];
}

function ap_spec_groups(): array
{
    return [
        'Thông số chung' => [
            'types' => ['nhaccu', 'phukien'],
            'fields' => [
                'CTSP_ChatLieu'     => ['Chất liệu', 'text', 150],
                'CTSP_MauSac'       => ['Màu sắc', 'text', 50],
                'CTSP_KichThuoc'    => ['Kích thước', 'text', 100],
                'CTSP_TrongLuong'   => ['Trọng lượng (kg)', 'dec', 0],
                'CTSP_MoTaKyThuat'  => ['Mô tả kỹ thuật', 'area', 0],
            ],
        ],
        'Đàn dây (Guitar, Violin, Ukulele…)' => [
            'types' => ['nhaccu'],
            'fields' => [
                'CTSP_SoDay'        => ['Số dây đàn', 'int', 0],
                'CTSP_ChatLieuDay'  => ['Chất liệu dây đàn', 'text', 100],
                'CTSP_ChatLieuThan' => ['Chất liệu thân đàn', 'text', 100],
                'CTSP_ChatLieuCan'  => ['Chất liệu cần đàn', 'text', 100],
                'CTSP_KieuDan'      => ['Kiểu đàn', 'text', 100],
                'CTSP_CoDan'        => ['Cỡ đàn', 'text', 50],
            ],
        ],
        'Piano, Organ, Keyboard' => [
            'types' => ['nhaccu'],
            'fields' => [
                'CTSP_SoPhim'       => ['Số phím đàn', 'int', 0],
                'CTSP_LoaiPhim'     => ['Loại phím đàn', 'text', 100],
                'CTSP_CheDoDan'     => ['Chế độ đàn', 'text', 100],
                'CTSP_SoGiaiDieu'   => ['Số điệu', 'int', 0],
                'CTSP_SoAmSac'      => ['Số âm sắc', 'int', 0],
                'CTSP_CongSuat'     => ['Công suất (W)', 'int', 0],
            ],
        ],
        'Trống' => [
            'types' => ['nhaccu'],
            'fields' => [
                'CTSP_LoaiTrong'          => ['Loại trống', 'text', 100],
                'CTSP_SoTrong'            => ['Số trống', 'int', 0],
                'CTSP_SoLa'               => ['Số lá', 'int', 0],
                'CTSP_ChatLieuMatTrong'   => ['Chất liệu mặt trống', 'text', 100],
            ],
        ],
        'Phụ kiện' => [
            'types' => ['phukien'],
            'fields' => [
                'CTSP_LoaiPhuKien'  => ['Loại phụ kiện', 'text', 100],
                'CTSP_TuongThich'   => ['Tương thích', 'text', 255],
                'CTSP_CongDung'     => ['Công dụng', 'text', 255],
                'CTSP_QuyCach'      => ['Quy cách', 'text', 100],
            ],
        ],
    ];
}

function ap_spec_fields_for(string $type): array
{
    $out = [];
    foreach (ap_spec_groups() as $g) {
        if (in_array($type, $g['types'], true)) {
            $out += $g['fields'];
        }
    }
    return $out;
}

   function ap_load_form(PDO $conn, array $cfg, int $id): ?array
{
    $st = $conn->prepare("SELECT * FROM {$cfg['table']} WHERE {$cfg['pk']} = :id");
    $st->execute(['id' => $id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) return null;

    $p = $cfg['p'];
    $stSub = $conn->prepare("SELECT HASP_DuongDan FROM hinhanhsanpham WHERE HASP_LoaiSanPham = :loai AND HASP_MaSanPham = :id AND HASP_AnhChinh = 0 ORDER BY HASP_ThuTu ASC");
    $stSub->execute(['loai' => $cfg['type'], 'id' => $id]);
    $subImages = $stSub->fetchAll(PDO::FETCH_COLUMN);

    $form = [
        'ten'       => $r["{$p}_Ten"],
        'th'        => $r['TH_Ma'],
        'lnc'       => $r['LNC_Ma'] ?? '',
        'giaban'    => (string)(int)$r["{$p}_GiaBan"],
        'giakm'     => $r["{$p}_GiaKhuyenMai"] !== null ? (string)(int)$r["{$p}_GiaKhuyenMai"] : '',
        'ton'       => $r["{$p}_SoLuongTon"],
        'baohanh'   => $r['NC_ThoiGianBaoHanh'] ?? 12,
        'trangthai' => $r["{$p}_TrangThai"],
        'hinhanh'   => $r["{$p}_HinhAnh"] ?? '',
        'mota'      => $r["{$p}_MoTa"] ?? '',
        'spec'      => [],
        'link'      => [],
        'gallery'   => array_pad($subImages, 3, ''), 
    ];

    $st = $conn->prepare("SELECT * FROM chitietsanpham WHERE {$cfg['pk']} = :id");
    $st->execute(['id' => $id]);
    $ct = $st->fetch(PDO::FETCH_ASSOC) ?: [];
    foreach (ap_spec_fields_for($cfg['type']) as $col => $_) {
        $form['spec'][$col] = $ct[$col] ?? '';
    }

    if ($cfg['type'] === 'nhaccu') {
        $st = $conn->prepare('SELECT PK_Ma FROM nhaccuphukien WHERE NC_Ma = :id');
    } else {
        $st = $conn->prepare('SELECT NC_Ma FROM nhaccuphukien WHERE PK_Ma = :id');
    }
    $st->execute(['id' => $id]);
    $form['link'] = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

    return $form;
}

function ap_blank_form(string $type): array
{
    $spec = [];
    foreach (ap_spec_fields_for($type) as $col => $_) $spec[$col] = '';
    return [
        'ten' => '', 'th' => '', 'lnc' => '', 'giaban' => '', 'giakm' => '', 'ton' => 0,
        'baohanh' => 12, 'trangthai' => 1, 'hinhanh' => '', 'mota' => '', 'spec' => $spec, 'link' => [],
        'gallery' => ['', '', ''], // Mảng chứa 3 ảnh phụ
    ];
}

function ap_save(PDO $conn, array $cfg, ?int $id, array $in, array $files, array &$form): array
{   
$galleryPaths = [];
for ($i = 0; $i < 3; $i++) {
    $subText = trim((string)($_POST['hinhanh_phu'][$i] ?? ''));
    if ($subText !== '' && !preg_match('~^https?://~i', $subText)) {
        $subText = basename(str_replace('\\', '/', $subText));
    }
    
    $subFile = isset($_FILES['anh_phu']['name'][$i]) && $_FILES['anh_phu']['name'][$i] !== '' ? [
        'name'     => $_FILES['anh_phu']['name'][$i],
        'type'     => $_FILES['anh_phu']['type'][$i],
        'tmp_name' => $_FILES['anh_phu']['tmp_name'][$i],
        'error'    => $_FILES['anh_phu']['error'][$i],
        'size'     => $_FILES['anh_phu']['size'][$i],
    ] : ['error' => UPLOAD_ERR_NO_FILE];

    [$uploadedSub, $upSubErr] = ap_upload_image($subFile);
    if ($upSubErr) $errors[] = "Ảnh phụ " . ($i + 1) . ": " . $upSubErr;
    
    $galleryPaths[$i] = $uploadedSub ?: $subText;
}

$form['gallery'] = $galleryPaths;
    $errors = [];
    $type = $cfg['type'];
    $p = $cfg['p'];

    $form = [
        'ten'       => trim((string)($in['ten'] ?? '')),
        'th'        => (string)($in['th'] ?? ''),
        'lnc'       => (string)($in['lnc'] ?? ''),
        'giaban'    => trim((string)($in['giaban'] ?? '')),
        'giakm'     => trim((string)($in['giakm'] ?? '')),
        'ton'       => trim((string)($in['ton'] ?? '0')),
        'baohanh'   => trim((string)($in['baohanh'] ?? '12')),
        'trangthai' => !empty($in['trangthai']) ? 1 : 0,
        'hinhanh'   => trim((string)($in['hinhanh'] ?? '')),
        'mota'      => trim((string)($in['mota'] ?? '')),
        'spec'      => [],
        'link'      => array_values(array_unique(array_filter(array_map('intval', (array)($in['link'] ?? [])), fn($v) => $v > 0))),
    ];

    if ($form['ten'] === '') $errors[] = 'Vui lòng nhập tên ' . $cfg['label'] . '.';
    elseif (mb_strlen($form['ten']) > 150) $errors[] = 'Tên không được dài quá 150 ký tự.';

    $thId = filter_var($form['th'], FILTER_VALIDATE_INT);
    if (!$thId) {
        $errors[] = 'Vui lòng chọn thương hiệu.';
    } else {
        $st = $conn->prepare('SELECT 1 FROM thuonghieu WHERE TH_Ma = :id');
        $st->execute(['id' => $thId]);
        if (!$st->fetchColumn()) $errors[] = 'Thương hiệu không tồn tại.';
    }

    $lncId = null;
    if ($type === 'nhaccu') {
        $lncId = filter_var($form['lnc'], FILTER_VALIDATE_INT);
        if (!$lncId) {
            $errors[] = 'Vui lòng chọn loại nhạc cụ.';
        } else {
            $st = $conn->prepare('SELECT 1 FROM loainhaccu WHERE LNC_Ma = :id');
            $st->execute(['id' => $lncId]);
            if (!$st->fetchColumn()) $errors[] = 'Loại nhạc cụ không tồn tại.';
        }
    }

    $giaBan = ap_money_in($form['giaban']);
    if ($giaBan === null || $giaBan === false || $giaBan <= 0) $errors[] = 'Giá bán phải là số lớn hơn 0.';

    $giaKm = ap_money_in($form['giakm']);
    if ($giaKm === false) {
        $errors[] = 'Giá khuyến mãi không hợp lệ.';
    } elseif ($giaKm !== null && is_int($giaBan) && ($giaKm <= 0 || $giaKm >= $giaBan)) {
        $errors[] = 'Giá khuyến mãi phải lớn hơn 0 và nhỏ hơn giá bán (hoặc để trống).';
    }

    $ton = filter_var($form['ton'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
    if ($ton === false) $errors[] = 'Số lượng tồn phải là số nguyên không âm.';

    $baoHanh = 12;
    if ($type === 'nhaccu') {
        $baoHanh = filter_var($form['baohanh'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 600]]);
        if ($baoHanh === false) $errors[] = 'Thời gian bảo hành phải là số tháng không âm.';
    }

    $image = $form['hinhanh'];
    if ($image !== '' && !preg_match('~^https?://~i', $image)) {
        $image = basename(str_replace('\\', '/', $image));
        $form['hinhanh'] = $image;
    }
    [$uploaded, $upErr] = ap_upload_image($_FILES['anh'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
    if ($upErr) $errors[] = $upErr;
    if ($uploaded) {
        $image = $uploaded;
        $form['hinhanh'] = $uploaded;
    }

    $specValues = [];
    foreach (ap_spec_fields_for($type) as $col => [$label, $kind, $max]) {
        $raw = trim((string)($in['spec'][$col] ?? ''));
        $form['spec'][$col] = $raw;
        if ($raw === '') { $specValues[$col] = null; continue; }

        if ($kind === 'int') {
            $v = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
            if ($v === false) { $errors[] = "$label phải là số nguyên không âm."; continue; }
            $specValues[$col] = $v;
        } elseif ($kind === 'dec') {
            $norm = str_replace(',', '.', $raw);
            if (!is_numeric($norm) || (float)$norm < 0 || (float)$norm > 999999.99) { $errors[] = "$label phải là số không âm."; continue; }
            $specValues[$col] = round((float)$norm, 2);
        } else {
            if ($max > 0 && mb_strlen($raw) > $max) { $errors[] = "$label không được dài quá $max ký tự."; continue; }
            $specValues[$col] = $raw;
        }
    }

    if ($errors) return [$errors, $id];

    $conn->beginTransaction();
    try {
        if ($type === 'nhaccu') {
            $data = [
                'lnc' => $lncId, 'th' => $thId, 'ten' => $form['ten'], 'gb' => $giaBan, 'gkm' => $giaKm,
                'ton' => $ton, 'anh' => $image !== '' ? $image : null, 'mota' => $form['mota'] !== '' ? $form['mota'] : null,
                'bh' => $baoHanh, 'tt' => $form['trangthai'],
            ];
            if ($id) {
                $conn->prepare('UPDATE nhaccu SET LNC_Ma=:lnc, TH_Ma=:th, NC_Ten=:ten, NC_GiaBan=:gb, NC_GiaKhuyenMai=:gkm,
                    NC_SoLuongTon=:ton, NC_HinhAnh=:anh, NC_MoTa=:mota, NC_ThoiGianBaoHanh=:bh, NC_TrangThai=:tt WHERE NC_Ma=:id')
                    ->execute($data + ['id' => $id]);
            } else {
                $conn->prepare('INSERT INTO nhaccu (LNC_Ma, TH_Ma, NC_Ten, NC_GiaBan, NC_GiaKhuyenMai, NC_SoLuongTon, NC_HinhAnh, NC_MoTa, NC_ThoiGianBaoHanh, NC_TrangThai)
                    VALUES (:lnc,:th,:ten,:gb,:gkm,:ton,:anh,:mota,:bh,:tt)')->execute($data);
                $id = (int)$conn->lastInsertId();
            }
        } else {
            $data = [
                'th' => $thId, 'ten' => $form['ten'], 'gb' => $giaBan, 'gkm' => $giaKm, 'ton' => $ton,
                'anh' => $image !== '' ? $image : null, 'mota' => $form['mota'] !== '' ? $form['mota'] : null, 'tt' => $form['trangthai'],
            ];
            if ($id) {
                $conn->prepare('UPDATE phukien SET TH_Ma=:th, PK_Ten=:ten, PK_GiaBan=:gb, PK_GiaKhuyenMai=:gkm,
                    PK_SoLuongTon=:ton, PK_HinhAnh=:anh, PK_MoTa=:mota, PK_TrangThai=:tt WHERE PK_Ma=:id')
                    ->execute($data + ['id' => $id]);
            } else {
                $conn->prepare('INSERT INTO phukien (TH_Ma, PK_Ten, PK_GiaBan, PK_GiaKhuyenMai, PK_SoLuongTon, PK_HinhAnh, PK_MoTa, PK_TrangThai)
                    VALUES (:th,:ten,:gb,:gkm,:ton,:anh,:mota,:tt)')->execute($data);
                $id = (int)$conn->lastInsertId();
            }
        }

        $key = $cfg['pk'];
        $st = $conn->prepare("SELECT CTSP_Ma FROM chitietsanpham WHERE $key = :id");
        $st->execute(['id' => $id]);
        $ctId = $st->fetchColumn();
        $hasValue = (bool)array_filter($specValues, fn($v) => $v !== null);

        if ($ctId) {
            $sets = [];
            foreach ($specValues as $col => $_) $sets[] = "$col = :$col";
            if ($sets) {
                $conn->prepare('UPDATE chitietsanpham SET ' . implode(', ', $sets) . ' WHERE CTSP_Ma = :ct')
                    ->execute($specValues + ['ct' => $ctId]);
            }
        } elseif ($hasValue) {
            $cols = array_keys($specValues);
            $conn->prepare("INSERT INTO chitietsanpham ($key, " . implode(', ', $cols) . ') VALUES (:id, :' . implode(', :', $cols) . ')')
                ->execute($specValues + ['id' => $id]);
        }

        if ($type === 'nhaccu') {
            [$meCol, $otherCol, $otherTable, $otherKey] = ['NC_Ma', 'PK_Ma', 'phukien', 'PK_Ma'];
        } else {
            [$meCol, $otherCol, $otherTable, $otherKey] = ['PK_Ma', 'NC_Ma', 'nhaccu', 'NC_Ma'];
        }
        $wanted = [];
        if ($form['link']) {
            $in_ = implode(',', array_fill(0, count($form['link']), '?'));
            $st = $conn->prepare("SELECT $otherKey FROM $otherTable WHERE $otherKey IN ($in_)");
            $st->execute($form['link']);
            $wanted = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        }
        $st = $conn->prepare("SELECT $otherCol FROM nhaccuphukien WHERE $meCol = :id");
        $st->execute(['id' => $id]);
        $current = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

        $del = $conn->prepare("DELETE FROM nhaccuphukien WHERE $meCol = :me AND $otherCol = :o");
        foreach (array_diff($current, $wanted) as $o) $del->execute(['me' => $id, 'o' => $o]);
        $ins = $conn->prepare("INSERT INTO nhaccuphukien ($meCol, $otherCol) VALUES (:me, :o)");
        foreach (array_diff($wanted, $current) as $o) $ins->execute(['me' => $id, 'o' => $o]);
        // 1. Xóa danh sách ảnh phụ cũ của sản phẩm này trong bảng hinhanhsanpham
        $stDelSub = $conn->prepare("DELETE FROM hinhanhsanpham WHERE HASP_LoaiSanPham = :loai AND HASP_MaSanPham = :id AND HASP_AnhChinh = 0");
        $stDelSub->execute(['loai' => $type, 'id' => $id]);

        // 2. Thêm lại danh sách ảnh phụ mới
        $stInsSub = $conn->prepare("INSERT INTO hinhanhsanpham (HASP_LoaiSanPham, HASP_MaSanPham, HASP_DuongDan, HASP_AnhChinh, HASP_ThuTu) VALUES (:loai, :id, :path, 0, :thutu)");
        foreach ($galleryPaths as $idx => $subPath) {
            if ($subPath !== '') {
                $stInsSub->execute([
                    'loai'  => $type,
                    'id'    => $id,
                    'path'  => $subPath,
                    'thutu' => $idx + 1
                ]);
            }
        }
        $conn->commit();
    } catch (Throwable $ex) {
        if ($conn->inTransaction()) $conn->rollBack();
        throw $ex;
    }

    return [[], $id];
}

function ap_delete(PDO $conn, array $cfg, int $id): void
{
    $conn->beginTransaction();
    try {
        $conn->prepare('DELETE FROM hinhanhsanpham WHERE HASP_LoaiSanPham = :t AND HASP_MaSanPham = :id')->execute(['t' => $cfg['type'], 'id' => $id]);
        $conn->prepare('DELETE FROM danhgia WHERE DG_LoaiSanPham = :t AND DG_MaSanPham = :id')->execute(['t' => $cfg['type'], 'id' => $id]);
        $conn->prepare("DELETE FROM nhaccuphukien WHERE {$cfg['pk']} = :id")->execute(['id' => $id]);
        $conn->prepare("DELETE FROM chitietsanpham WHERE {$cfg['pk']} = :id")->execute(['id' => $id]);
        $conn->prepare("DELETE FROM {$cfg['table']} WHERE {$cfg['pk']} = :id")->execute(['id' => $id]);
        $conn->commit();
    } catch (Throwable $ex) {
        if ($conn->inTransaction()) $conn->rollBack();
        throw $ex;
    }
}

/* ============================================================
 * XỬ LÝ YÊU CẦU
 * ============================================================ */
if (!ap_is_admin()) {
    ap_redirect(AP_LOGIN_URL);
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$type = (($_POST['type'] ?? $_GET['type'] ?? 'nhaccu') === 'phukien') ? 'phukien' : 'nhaccu';
$cfg  = ap_cfg($type);
$p    = $cfg['p'];
$self = 'admin_products.php';

$view   = 'list';
$errors = [];
$editId = null;
$form   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ap_check_csrf($_POST['csrf'] ?? '')) {
        ap_flash('err', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
        ap_redirect("$self?type=$type");
    }

    $do = (string)($_POST['do'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;

    try {
        if ($do === 'delete' && $id) {
            try {
                ap_delete($conn, $cfg, $id);
                ap_flash('ok', 'Đã xóa ' . $cfg['label'] . '.');
            } catch (PDOException $ex) {
                error_log('[admin_products] delete: ' . $ex->getMessage());
                ap_flash('err', 'Không xóa được vì sản phẩm đang được dùng ở nơi khác. Hãy chọn "Ẩn" để ngừng bán.');
            }
            ap_redirect("$self?type=$type");
        }

        if ($do === 'toggle' && $id) {
            $conn->prepare("UPDATE {$cfg['table']} SET {$p}_TrangThai = 1 - {$p}_TrangThai WHERE {$cfg['pk']} = :id")->execute(['id' => $id]);
            ap_flash('ok', 'Đã đổi trạng thái hiển thị.');
            $back = (string)($_POST['back'] ?? '');
            ap_redirect($self . '?' . ($back !== '' ? ltrim($back, '?') : "type=$type"));
        }

        if ($do === 'save') {
            if ($id) {
                $exists = $conn->prepare("SELECT 1 FROM {$cfg['table']} WHERE {$cfg['pk']} = :id");
                $exists->execute(['id' => $id]);
                if (!$exists->fetchColumn()) {
                    ap_flash('err', 'Không tìm thấy ' . $cfg['label'] . ' cần sửa.');
                    ap_redirect("$self?type=$type");
                }
            }
           $form = [];
            [$errors, $newId] = ap_save($conn, $cfg, $id, $_POST, $_FILES, $form);

           
            if (!$errors) {

                ap_flash(
                    'ok',
                    ($id ? 'Đã cập nhật ' : 'Đã thêm ') .
                    $cfg['label'] . ' "' . $form['ten'] . '".'
                );

                // Lấy mã sản phẩm vừa lưu
                $savedId = (int)($id ?: $newId);

                // Lấy bộ lọc và loại sản phẩm từ trang danh sách trước đó
                $back = (string)($_POST['back'] ?? '');
                parse_str($back, $backParams);

                $returnParams = [
                    'type' => $type
                ];

                // Giữ lại các điều kiện lọc
                $kw = trim((string)($backParams['q'] ?? ''));
                $fCat = filter_var($backParams['lnc'] ?? null, FILTER_VALIDATE_INT);
                $fBrand = filter_var($backParams['th'] ?? null, FILTER_VALIDATE_INT);
                $fState = (string)($backParams['tt'] ?? '');

                $where = [];
                $args = [];

                if ($kw !== '') {
                    $where[] = "t.{$p}_Ten LIKE :kw";
                    $args['kw'] = '%' . $kw . '%';
                    $returnParams['q'] = $kw;
                }

                if ($fBrand) {
                    $where[] = 't.TH_Ma = :th';
                    $args['th'] = $fBrand;
                    $returnParams['th'] = $fBrand;
                }

                if ($type === 'nhaccu' && $fCat) {
                    $where[] = 't.LNC_Ma = :lnc';
                    $args['lnc'] = $fCat;
                    $returnParams['lnc'] = $fCat;
                }

                if ($fState === '1' || $fState === '0') {
                    $where[] = "t.{$p}_TrangThai = :tt";
                    $args['tt'] = (int)$fState;
                    $returnParams['tt'] = $fState;
                }

                $whereSql = $where ? ' AND ' . implode(' AND ', $where) : '';

                // Kiểm tra sản phẩm vừa lưu có còn thuộc bộ lọc cũ không
                $check = $conn->prepare(
                    "SELECT COUNT(*)
                    FROM {$cfg['table']} t
                    WHERE t.{$cfg['pk']} = :savedId $whereSql"
                );

                $check->execute($args + ['savedId' => $savedId]);

                // Nếu không còn phù hợp bộ lọc thì bỏ bộ lọc
                if (!(int)$check->fetchColumn()) {
                    $whereSql = '';
                    $args = [];
                    $returnParams = ['type' => $type];
                }

                // Đếm số sản phẩm đứng trước sản phẩm vừa lưu
                $st = $conn->prepare(
                    "SELECT COUNT(*)
                    FROM {$cfg['table']} t
                    WHERE t.{$cfg['pk']} < :savedId $whereSql"
                );

                $st->execute($args + ['savedId' => $savedId]);

                $position = (int)$st->fetchColumn() + 1;

                // Tính chính xác trang chứa sản phẩm vừa lưu
                $targetPage = max(
                    1,
                    (int)ceil($position / AP_PER_PAGE)
                );

                $returnParams['page'] = $targetPage;

                // Quay lại trang chứa sản phẩm
                ap_redirect($self . '?' . http_build_query($returnParams));
            }

            $view = 'form';
            $editId = $id;
        }
            } catch (Throwable $ex) {
                error_log('[admin_products] ' . $ex->getMessage());
                $errors[] = 'Có lỗi hệ thống khi lưu dữ liệu. Vui lòng thử lại.';
                $view = 'form';
                $editId = $id;
                $form = $form ?: ap_blank_form($type);
            }
}

if ($view === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET' && in_array($_GET['action'] ?? '', ['new', 'edit'], true)) {
    $view = 'form';
    if ($_GET['action'] === 'edit') {
        $editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
        $form = $editId ? ap_load_form($conn, $cfg, $editId) : null;
        if (!$form) {
            ap_flash('err', 'Không tìm thấy ' . $cfg['label'] . '.');
            ap_redirect("$self?type=$type");
        }
    } else {
        $form = ap_blank_form($type);
    }
}

/* ---------- dữ liệu cho giao diện ---------- */
$flash = $_SESSION['ap_flash'] ?? null;
unset($_SESSION['ap_flash']);

// Sắp xếp thương hiệu và loại tăng dần theo Mã số
$brands = $conn->query('SELECT TH_Ma, TH_Ten FROM thuonghieu ORDER BY TH_Ma ASC')->fetchAll(PDO::FETCH_ASSOC);
$cats   = $conn->query('SELECT LNC_Ma, LNC_Ten FROM loainhaccu ORDER BY LNC_Ma ASC')->fetchAll(PDO::FETCH_ASSOC);

if ($view === 'list') {
    $kw     = trim((string)($_GET['q'] ?? ''));
    $fCat   = filter_input(INPUT_GET, 'lnc', FILTER_VALIDATE_INT) ?: null;
    $fBrand = filter_input(INPUT_GET, 'th', FILTER_VALIDATE_INT) ?: null;
    $fState = (string)($_GET['tt'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));

    $where = [];
    $args  = [];
    if ($kw !== '') { $where[] = "t.{$p}_Ten LIKE :kw"; $args['kw'] = '%' . $kw . '%'; }
    if ($fBrand)    { $where[] = 't.TH_Ma = :th';       $args['th'] = $fBrand; }
    if ($type === 'nhaccu' && $fCat) { $where[] = 't.LNC_Ma = :lnc'; $args['lnc'] = $fCat; }
    if ($fState === '1' || $fState === '0') { $where[] = "t.{$p}_TrangThai = :tt"; $args['tt'] = (int)$fState; }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $st = $conn->prepare("SELECT COUNT(*) FROM {$cfg['table']} t $whereSql");
    $st->execute($args);
    $total = (int)$st->fetchColumn();
    $pages = max(1, (int)ceil($total / AP_PER_PAGE));
    $page  = min($page, $pages);

    $catSelect = $type === 'nhaccu' ? 'lnc.LNC_Ten AS loai,' : "'Phụ kiện' AS loai,";
    $catJoin   = $type === 'nhaccu' ? 'LEFT JOIN loainhaccu lnc ON t.LNC_Ma = lnc.LNC_Ma' : '';
    $sql = "SELECT t.{$cfg['pk']} AS id, t.{$p}_Ten AS ten, t.{$p}_GiaBan AS gia, t.{$p}_GiaKhuyenMai AS gkm,
                   t.{$p}_SoLuongTon AS ton, t.{$p}_HinhAnh AS anh, t.{$p}_TrangThai AS tt,
                   $catSelect th.TH_Ten AS thuonghieu
            FROM {$cfg['table']} t
            LEFT JOIN thuonghieu th ON t.TH_Ma = th.TH_Ma
            $catJoin
            $whereSql
            ORDER BY t.{$cfg['pk']} ASC
            LIMIT :lim OFFSET :off";
    $st = $conn->prepare($sql);
    foreach ($args as $k => $v) $st->bindValue(':' . $k, $v);
    $st->bindValue(':lim', AP_PER_PAGE, PDO::PARAM_INT);
    $st->bindValue(':off', ($page - 1) * AP_PER_PAGE, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $baseQuery = array_filter(['type' => $type, 'q' => $kw, 'lnc' => $fCat, 'th' => $fBrand, 'tt' => $fState], fn($v) => $v !== null && $v !== '');
} else {
    if ($type === 'nhaccu') {
        $linkOptions = $conn->query('SELECT PK_Ma AS id, PK_Ten AS ten FROM phukien ORDER BY PK_Ma ASC')->fetchAll(PDO::FETCH_ASSOC);
        $linkTitle = 'Phụ kiện đi kèm / khuyên dùng';
    } else {
        $linkOptions = $conn->query('SELECT NC_Ma AS id, NC_Ten AS ten FROM nhaccu ORDER BY NC_Ma ASC')->fetchAll(PDO::FETCH_ASSOC);
        $linkTitle = 'Nhạc cụ phù hợp với phụ kiện này';
    }
    $accTypes = $conn->query("SELECT DISTINCT CTSP_LoaiPhuKien FROM chitietsanpham WHERE PK_Ma IS NOT NULL AND CTSP_LoaiPhuKien IS NOT NULL AND CTSP_LoaiPhuKien <> '' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
}

$csrf = ap_csrf();
$inputCls = 'w-full h-10 border border-[#cbbf8c] rounded px-3 bg-white focus:outline-none focus:ring-2 focus:ring-[#a28d46] text-base';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý <?= e($cfg['label']) ?> – Quản trị</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f5f4f1] text-[#111] min-h-screen text-base">

<header class="bg-white border-b border-[#b49a4a]">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center gap-x-6 gap-y-2">
        <a href="<?= e($self) ?>" class="font-semibold text-lg text-[#8e7938] whitespace-nowrap">Quản trị sản phẩm</a>
        <nav class="flex flex-wrap items-center gap-1 text-base">
            <a href="<?= e($self) ?>?type=nhaccu"
               class="whitespace-nowrap px-3 py-1.5 rounded <?= $type === 'nhaccu' ? 'bg-[#a28d46] text-white' : 'hover:bg-[#f5f4f1]' ?>">Nhạc cụ</a>
            <a href="<?= e($self) ?>?type=phukien"
               class="whitespace-nowrap px-3 py-1.5 rounded <?= $type === 'phukien' ? 'bg-[#a28d46] text-white' : 'hover:bg-[#f5f4f1]' ?>">Phụ kiện</a>
            <a href="admin_categories.php" class="whitespace-nowrap px-3 py-1.5 rounded hover:bg-[#f5f4f1]">Loại &amp; thương hiệu</a>
        </nav>
        <a href="index.php" class="ml-auto text-base text-gray-600 hover:underline whitespace-nowrap">← Về trang web</a>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-6">

<?php if ($flash): ?>
    <div class="mb-4 px-4 py-3 rounded text-base <?= $flash['kind'] === 'ok' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <?= e($flash['msg']) ?>
    </div>
<?php endif; ?>

<?php if ($view === 'list'): ?>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h1 class="text-2xl font-semibold"><?= e($cfg['Label']) ?> <span class="text-base font-normal text-gray-500">(<?= $total ?>)</span></h1>
    <a href="<?= e($self) ?>?type=<?= e($type) ?>&amp;action=new&amp;back=<?= e(http_build_query($baseQuery + ['page' => $page])) ?>"           class="h-10 px-5 inline-flex items-center rounded bg-[#a28d46] text-white font-semibold hover:bg-[#8e7938]">Thêm <?= e($cfg['label']) ?></a>
    </div>

    <form method="get" class="bg-white border border-[#e4dcc0] rounded p-3 mb-4 flex flex-wrap gap-2 items-center">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="search" name="q" value="<?= e($kw) ?>" placeholder="Tìm theo tên…" class="<?= $inputCls ?> sm:w-64">
        <?php if ($type === 'nhaccu'): ?>
            <select name="lnc" class="<?= $inputCls ?> sm:w-44">
                <option value="">Mọi loại</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= (int)$c['LNC_Ma'] ?>" <?= $fCat === (int)$c['LNC_Ma'] ? 'selected' : '' ?>><?= e($c['LNC_Ten']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <select name="th" class="<?= $inputCls ?> sm:w-44">
            <option value="">Mọi thương hiệu</option>
            <?php foreach ($brands as $b): ?>
                <option value="<?= (int)$b['TH_Ma'] ?>" <?= $fBrand === (int)$b['TH_Ma'] ? 'selected' : '' ?>><?= e($b['TH_Ten']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tt" class="<?= $inputCls ?> sm:w-40">
            <option value="">Mọi trạng thái</option>
            <option value="1" <?= $fState === '1' ? 'selected' : '' ?>>Đang hiển thị</option>
            <option value="0" <?= $fState === '0' ? 'selected' : '' ?>>Đang ẩn</option>
        </select>
        <button class="h-10 px-4 rounded border border-[#a28d46] text-[#8e7938] font-medium hover:bg-[#f8f4e9]">Lọc</button>
    </form>

    <div class="bg-white border border-[#e4dcc0] rounded overflow-x-auto">
        <table class="w-full text-base">
            <thead class="bg-[#f8f4e9] text-left">
                <tr>
                    <th class="px-4 py-3 w-16">Mã</th>
                    <th class="px-4 py-3">Sản phẩm</th>
                    <th class="px-4 py-3 text-center whitespace-nowrap">Loại / Thương hiệu</th>
                    <th class="px-4 py-3 text-center">Giá</th>
                    <th class="px-4 py-3 text-right">Tồn</th>
                    <th class="px-4 py-3 text-center whitespace-nowrap">Trạng thái</th>
                    <th class="px-4 py-3 text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">
                    Chưa có <?= e($cfg['label']) ?> nào khớp. Bấm "Thêm <?= e($cfg['label']) ?>" để tạo mới.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $index => $r):
                $img = ap_image_url($r['anh']);
                $hasSale = $r['gkm'] !== null && (float)$r['gkm'] > 0;
            ?>
                <tr class="border-t border-[#eee7cf] align-middle">
                    <td class="px-4 py-3 text-gray-500 font-medium">
                     <?= (($page - 1) * AP_PER_PAGE) + $index + 1 ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 shrink-0 border border-[#e4dcc0] bg-white flex items-center justify-center overflow-hidden">
                                <?php if ($img !== ''): ?><img src="<?= e($img) ?>" alt="" class="max-w-full max-h-full object-contain"><?php endif; ?>
                            </div>
                            <span class="font-semibold text-gray-900"><?= e($r['ten']) ?></span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap"><?= e($r['loai'] ?? '') ?><br><span class="text-gray-500 text-sm"><?= e($r['thuonghieu'] ?? '') ?></span></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <?php if ($hasSale): ?>
                            <span class="font-semibold text-[#9d8844]"><?= ap_money_out($r['gkm']) ?></span><br>
                            <span class="text-gray-400 text-sm line-through"><?= ap_money_out($r['gia']) ?></span>
                        <?php else: ?>
                            <span class="font-semibold text-[#9d8844]"><?= ap_money_out($r['gia']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right font-medium <?= (int)$r['ton'] <= 0 ? 'text-red-600' : '' ?>"><?= (int)$r['ton'] ?></td>
                    <td class="px-4 py-3 text-center">
                        <?php if ((int)$r['tt'] === 1): ?>
                            <span class="text-green-700 font-medium">Hiển thị</span>
                        <?php else: ?>
                            <span class="text-gray-500">Đang ẩn</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end items-center gap-2 whitespace-nowrap">
                           <a href="<?= e($self) ?>?type=<?= e($type) ?>&amp;action=edit&amp;id=<?= (int)$r['id'] ?>&amp;back=<?= e(http_build_query($baseQuery + ['page' => $page])) ?>"
                            class="px-3 py-1.5 rounded border border-[#a28d46] text-[#8e7938] font-medium hover:bg-[#f8f4e9]">
                            Sửa
                            </a>
                            <form method="post" class="inline">
                                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                <input type="hidden" name="type" value="<?= e($type) ?>">
                                <input type="hidden" name="do" value="toggle">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="back" value="<?= e($_GET['back'] ?? '') ?>">
                                <button class="px-3 py-1.5 rounded border border-gray-300 font-medium hover:bg-gray-50"><?= (int)$r['tt'] === 1 ? 'Ẩn' : 'Hiện' ?></button>
                            </form>
                            <form method="post" class="inline" data-confirm="Xóa hẳn &quot;<?= e($r['ten']) ?>&quot;? Thao tác này không thể hoàn tác.">
                                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                <input type="hidden" name="type" value="<?= e($type) ?>">
                                <input type="hidden" name="do" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="px-3 py-1.5 rounded border border-red-300 text-red-700 font-medium hover:bg-red-50">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="mt-4 flex flex-wrap gap-1 text-base">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="<?= e($self . '?' . http_build_query($baseQuery + ['page' => $i])) ?>"
                   class="px-3.5 py-1.5 rounded border <?= $i === $page ? 'bg-[#a28d46] text-white border-[#a28d46]' : 'bg-white border-[#e4dcc0] hover:bg-[#f8f4e9]' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>

<?php else:
    $isEdit = (bool)$editId;
    $currentImg = ap_image_url($form['hinhanh']);
    ?>

    <div class="mb-4">
        <a href="<?= e($self) ?>?type=<?= e($type) ?>" class="text-base text-gray-600 hover:underline">← Quay lại danh sách <?= e($cfg['label']) ?></a>
        <h1 class="text-2xl font-semibold mt-1"><?= $isEdit ? 'Sửa' : 'Thêm' ?> <?= e($cfg['label']) ?></h1>
    </div>

    <?php if ($errors): ?>
        <div class="mb-4 px-4 py-3 rounded text-base bg-red-50 text-red-800 border border-red-200">
            <p class="font-semibold mb-1">Chưa lưu được, vui lòng kiểm tra lại:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                <?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="hidden" name="do" value="save">
        <input type="hidden" name="id" value="<?= (int)$editId ?>">
        <input type="hidden" name="back"
        value="<?= e($_POST['back'] ?? $_GET['back'] ?? '') ?>">
        <div class="lg:col-span-2 space-y-6">

            <section class="bg-white border border-[#e4dcc0] rounded p-5 space-y-4">
                <h2 class="font-semibold text-lg border-b-2 border-black pb-2">Thông tin chính</h2>

                <div>
                    <label for="ten" class="block font-medium mb-1">Tên <?= e($cfg['label']) ?> *</label>
                    <input id="ten" name="ten" maxlength="150" required value="<?= e($form['ten']) ?>" class="<?= $inputCls ?>">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="th" class="block font-medium mb-1">Thương hiệu *</label>
                        <select id="th" name="th" required class="<?= $inputCls ?>">
                            <option value="">— Chọn —</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= (int)$b['TH_Ma'] ?>" <?= (string)$form['th'] === (string)$b['TH_Ma'] ? 'selected' : '' ?>><?= e($b['TH_Ten']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($type === 'nhaccu'): ?>
                    <div>
                        <label for="lnc" class="block font-medium mb-1">Loại nhạc cụ *</label>
                        <select id="lnc" name="lnc" required class="<?= $inputCls ?>">
                            <option value="">— Chọn —</option>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?= (int)$c['LNC_Ma'] ?>" <?= (string)$form['lnc'] === (string)$c['LNC_Ma'] ? 'selected' : '' ?>><?= e($c['LNC_Ten']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-<?= $type === 'nhaccu' ? '4' : '3' ?> gap-4">
                    <div>
                        <label for="giaban" class="block font-medium mb-1">Giá bán (₫) *</label>
                        <input id="giaban" name="giaban" inputmode="numeric" required value="<?= e($form['giaban']) ?>" placeholder="3500000" class="<?= $inputCls ?>">
                    </div>
                    <div>
                        <label for="giakm" class="block font-medium mb-1">Giá khuyến mãi (₫)</label>
                        <input id="giakm" name="giakm" inputmode="numeric" value="<?= e($form['giakm']) ?>" placeholder="Để trống nếu không giảm" class="<?= $inputCls ?>">
                    </div>
                    <div>
                        <label for="ton" class="block font-medium mb-1">Số lượng tồn *</label>
                        <input id="ton" name="ton" type="number" min="0" required value="<?= e($form['ton']) ?>" class="<?= $inputCls ?>">
                    </div>
                    <?php if ($type === 'nhaccu'): ?>
                    <div>
                        <label for="baohanh" class="block font-medium mb-1">Bảo hành (tháng)</label>
                        <input id="baohanh" name="baohanh" type="number" min="0" value="<?= e($form['baohanh']) ?>" class="<?= $inputCls ?>">
                    </div>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="mota" class="block font-medium mb-1">Mô tả</label>
                    <textarea id="mota" name="mota" rows="5" class="w-full border border-[#cbbf8c] rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#a28d46] text-base"><?= e($form['mota']) ?></textarea>
                </div>

                <label class="inline-flex items-center gap-2 text-base font-medium">
                    <input type="checkbox" name="trangthai" value="1" <?= (int)$form['trangthai'] === 1 ? 'checked' : '' ?>>
                    Hiển thị trên website
                </label>
            </section>

            <section class="bg-white border border-[#e4dcc0] rounded p-5 space-y-5">
                <div class="border-b-2 border-black pb-2">
                    <h2 class="font-semibold text-lg">Thông số kỹ thuật</h2>
                    <p class="text-xs text-gray-500 mt-1">Chỉ điền các mục phù hợp, mục để trống sẽ không hiển thị ở trang chi tiết.</p>
                </div>
                <?php foreach (ap_spec_groups() as $gName => $g):
                    if (!in_array($type, $g['types'], true)) continue; ?>
                    <fieldset>
                        <legend class="text-base font-semibold text-[#8e7938] mb-2"><?= e($gName) ?></legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach ($g['fields'] as $col => [$label, $kind, $max]):
                                $val = $form['spec'][$col] ?? ''; ?>
                                <div class="<?= $kind === 'area' ? 'sm:col-span-2' : '' ?>">
                                    <label for="<?= e($col) ?>" class="block font-medium mb-1"><?= e($label) ?></label>
                                    <?php if ($kind === 'area'): ?>
                                        <textarea id="<?= e($col) ?>" name="spec[<?= e($col) ?>]" rows="3" class="w-full border border-[#cbbf8c] rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#a28d46] text-base"><?= e($val) ?></textarea>
                                    <?php elseif ($col === 'CTSP_LoaiPhuKien'): ?>
                                        <input id="<?= e($col) ?>" name="spec[<?= e($col) ?>]" list="acc-types" maxlength="<?= (int)$max ?>" value="<?= e($val) ?>" class="<?= $inputCls ?>">
                                        <datalist id="acc-types">
                                            <?php foreach ($accTypes as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?>
                                        </datalist>
                                    <?php else: ?>
                                        <input id="<?= e($col) ?>" name="spec[<?= e($col) ?>]"
                                               <?= $kind === 'int' ? 'type="number" min="0" step="1"' : ($kind === 'dec' ? 'inputmode="decimal"' : 'type="text"') ?>
                                               <?= $max ? 'maxlength="' . (int)$max . '"' : '' ?>
                                               value="<?= e($val) ?>" class="<?= $inputCls ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            </section>
        </div>

       <aside class="space-y-6">
    <section class="bg-white border border-[#e4dcc0] rounded p-5 space-y-4">
        <h2 class="font-semibold text-lg border-b-2 border-black pb-2">Hình ảnh sản phẩm</h2>
        
        <!-- Ô nhập Ảnh chính -->
        <div class="border-b border-gray-200 pb-4 space-y-2">
            <label class="block font-semibold text-[#8e7938]">1. Ảnh chính</label>
            <div class="h-44 border border-[#b49a4a] bg-white flex items-center justify-center overflow-hidden mb-2">
                <img id="preview" src="<?= e($currentImg) ?>" alt="" class="max-w-full max-h-full object-contain <?= $currentImg === '' ? 'hidden' : '' ?>">
                <span id="preview-empty" class="text-sm text-gray-500 <?= $currentImg !== '' ? 'hidden' : '' ?>">Chưa có ảnh chính</span>
            </div>
            <label for="anh" class="block text-xs font-medium text-gray-600">Tải ảnh mới từ máy tính</label>
            <input id="anh" name="anh" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-xs">
            <input id="hinhanh" name="hinhanh" maxlength="255" value="<?= e($form['hinhanh']) ?>" placeholder="Tên file (vd: 1.jpg) hoặc URL" class="<?= $inputCls ?> text-sm h-9">
        </div>

        <!-- Các ô nhập 3 Ảnh phụ -->
        <div class="space-y-3">
            <label class="block font-semibold text-[#8e7938]">2. Ảnh phụ</label>
            <?php for ($i = 0; $i < 3; $i++): 
                $subVal = $form['gallery'][$i] ?? '';
            ?>
                <div class="p-2.5 border border-gray-200 rounded bg-[#fcfcf9] space-y-1.5">
                    <span class="text-xs font-semibold text-gray-700">Ảnh phụ <?= $i + 1 ?></span>
                    <input type="file" name="anh_phu[]" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-xs">
                    <input type="text" name="hinhanh_phu[]" value="<?= e($subVal) ?>" placeholder="Tên file hoặc URL ảnh phụ <?= $i + 1 ?>" class="<?= $inputCls ?> text-sm h-8">
                </div>
            <?php endfor; ?>
        </div>
    </section>

            <section class="bg-white border border-[#e4dcc0] rounded p-5">
                <h2 class="font-semibold text-lg border-b-2 border-black pb-2 mb-3"><?= e($linkTitle) ?></h2>
                <?php if (!$linkOptions): ?>
                    <p class="text-base text-gray-500">Chưa có dữ liệu để liên kết.</p>
                <?php else: ?>
                    <div class="max-h-56 overflow-y-auto space-y-2 text-base">
                        <?php foreach ($linkOptions as $o): ?>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" name="link[]" value="<?= (int)$o['id'] ?>" class="mt-1" <?= in_array((int)$o['id'], $form['link'], true) ? 'checked' : '' ?>>
                                <span><?= e($o['ten']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 h-11 rounded bg-[#a28d46] text-white font-semibold hover:bg-[#8e7938]">
                    <?= $isEdit ? 'Lưu thay đổi' : 'Thêm ' . e($cfg['label']) ?>
                </button>
            <a href="<?= e($self . '?' . ($_POST['back'] ?? $_GET['back'] ?? "type=$type")) ?>"
            class="h-11 px-4 inline-flex items-center rounded border border-gray-300 bg-white hover:bg-gray-50 font-medium">
                Hủy
            </a>
                 </div>
             </aside>
     </form>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
        if (!confirm(f.dataset.confirm)) ev.preventDefault();
    });
});

(function () {
    var input = document.getElementById('anh');
    var img = document.getElementById('preview');
    var empty = document.getElementById('preview-empty');
    if (!input || !img) return;
    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) return;
        img.src = URL.createObjectURL(file);
        img.classList.remove('hidden');
        if (empty) empty.classList.add('hidden');
    });
})();
</script>
</body>
</html>