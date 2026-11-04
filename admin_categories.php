<?php
declare(strict_types=1);

/**
 * admin_categories.php
 * Quản lý phân loại cho cả NHẠC CỤ và PHỤ KIỆN
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

/* ============================================================
 * CẤU HÌNH
 * ============================================================ */
const AC_LOGIN_URL  = 'login.php';
const AC_IMAGE_DIR  = __DIR__ . '/images/';
const AC_MAX_UPLOAD = 5 * 1024 * 1024;

function ac_is_admin(): bool
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

function ac_csrf(): string
{
    if (empty($_SESSION['ap_csrf'])) {
        $_SESSION['ap_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ap_csrf'];
}

function ac_check_csrf($token): bool
{
    return is_string($token) && !empty($_SESSION['ap_csrf']) && hash_equals($_SESSION['ap_csrf'], $token);
}

function ac_flash(string $kind, string $msg): void
{
    $_SESSION['ac_flash'] = ['kind' => $kind, 'msg' => $msg];
}

function ac_redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function ac_slug(string $name): string
{
    $name = str_replace(['đ', 'Đ'], 'd', $name);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $ascii = strtolower($ascii === false ? '' : $ascii);
    $ascii = trim((string)preg_replace('/[^a-z0-9]+/', '-', $ascii), '-');
    return $ascii !== '' ? substr($ascii, 0, 40) : 'logo';
}

function ac_upload_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Tải ảnh lên thất bại (mã lỗi ' . (int)$file['error'] . ').'];
    if ($file['size'] > AC_MAX_UPLOAD) return [null, 'Ảnh vượt quá 5 MB.'];

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
    if ($ext === null) return [null, 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.'];

    if (!is_dir(AC_IMAGE_DIR) && !mkdir(AC_IMAGE_DIR, 0755, true)) return [null, 'Không tạo được thư mục images/.'];

    $name = ac_slug(pathinfo((string)$file['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], AC_IMAGE_DIR . $name)) return [null, 'Không lưu được file ảnh vào thư mục images/.'];

    return [$name, null];
}

function ac_image_url(?string $name): string
{
    $name = (string)$name;
    if ($name === '') return '';
    return preg_match('~^https?://~i', $name) ? $name : 'images/' . ltrim($name, '/');
}

/* ============================================================
 * XỬ LÝ YÊU CẦU
 * ============================================================ */
if (!ac_is_admin()) {
    ac_redirect(AC_LOGIN_URL);
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$self = 'admin_categories.php';
$tab  = (string)($_POST['tab'] ?? $_GET['tab'] ?? 'loai');
if (!in_array($tab, ['loai', 'thuonghieu', 'phukien'], true)) $tab = 'loai';

$errors = [];
$form   = null;
$editId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ac_check_csrf($_POST['csrf'] ?? '')) {
        ac_flash('err', 'Phiên làm việc đã hết hạn, vui lòng thử lại.');
        ac_redirect("$self?tab=$tab");
    }

    $do = (string)($_POST['do'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;

    try {
        if ($do === 'loai_save') {
            $ten  = trim((string)($_POST['ten'] ?? ''));
            $mota = trim((string)($_POST['mota'] ?? ''));
            $form = ['ten' => $ten, 'mota' => $mota];
            $editId = $id;

            if ($ten === '') $errors[] = 'Vui lòng nhập tên loại nhạc cụ.';
            elseif (mb_strlen($ten) > 100) $errors[] = 'Tên loại không được dài quá 100 ký tự.';
            else {
                $st = $conn->prepare('SELECT 1 FROM loainhaccu WHERE LNC_Ten = :t AND LNC_Ma <> :id');
                $st->execute(['t' => $ten, 'id' => $id ?? 0]);
                if ($st->fetchColumn()) $errors[] = 'Đã có loại nhạc cụ tên "' . $ten . '".';
            }

            if (!$errors) {
                if ($id) {
                    $conn->prepare('UPDATE loainhaccu SET LNC_Ten = :t, LNC_MoTa = :m WHERE LNC_Ma = :id')
                        ->execute(['t' => $ten, 'm' => $mota !== '' ? $mota : null, 'id' => $id]);
                    ac_flash('ok', 'Đã cập nhật loại "' . $ten . '".');
                } else {
                    $conn->prepare('INSERT INTO loainhaccu (LNC_Ten, LNC_MoTa) VALUES (:t, :m)')
                        ->execute(['t' => $ten, 'm' => $mota !== '' ? $mota : null]);
                    ac_flash('ok', 'Đã thêm loại "' . $ten . '".');
                }
                ac_redirect("$self?tab=loai");
            }
        }

        if ($do === 'loai_delete' && $id) {
            $st = $conn->prepare('SELECT COUNT(*) FROM nhaccu WHERE LNC_Ma = :id');
            $st->execute(['id' => $id]);
            if ((int)$st->fetchColumn() > 0) {
                ac_flash('err', 'Không xóa được: vẫn còn nhạc cụ thuộc loại này. Hãy chuyển các nhạc cụ sang loại khác trước.');
            } else {
                $conn->prepare('DELETE FROM loainhaccu WHERE LNC_Ma = :id')->execute(['id' => $id]);
                ac_flash('ok', 'Đã xóa loại nhạc cụ.');
            }
            ac_redirect("$self?tab=loai");
        }

        if ($do === 'th_save') {
            $ten  = trim((string)($_POST['ten'] ?? ''));
            $logo = trim((string)($_POST['logo'] ?? ''));
            $mota = trim((string)($_POST['mota'] ?? ''));
            $editId = $id;

            if ($logo !== '' && !preg_match('~^https?://~i', $logo)) {
                $logo = basename(str_replace('\\', '/', $logo));
            }
            [$uploaded, $upErr] = ac_upload_image($_FILES['anh'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if ($upErr) $errors[] = $upErr;
            if ($uploaded) $logo = $uploaded;
            $form = ['ten' => $ten, 'logo' => $logo, 'mota' => $mota];

            if ($ten === '') $errors[] = 'Vui lòng nhập tên thương hiệu.';
            elseif (mb_strlen($ten) > 100) $errors[] = 'Tên thương hiệu không được dài quá 100 ký tự.';
            else {
                $st = $conn->prepare('SELECT 1 FROM thuonghieu WHERE TH_Ten = :t AND TH_Ma <> :id');
                $st->execute(['t' => $ten, 'id' => $id ?? 0]);
                if ($st->fetchColumn()) $errors[] = 'Đã có thương hiệu tên "' . $ten . '".';
            }
            if (mb_strlen($logo) > 255) $errors[] = 'Đường dẫn logo quá dài (tối đa 255 ký tự).';

            if (!$errors) {
                $params = ['t' => $ten, 'l' => $logo !== '' ? $logo : null, 'm' => $mota !== '' ? $mota : null];
                if ($id) {
                    $conn->prepare('UPDATE thuonghieu SET TH_Ten = :t, TH_Logo = :l, TH_MoTa = :m WHERE TH_Ma = :id')
                        ->execute($params + ['id' => $id]);
                    ac_flash('ok', 'Đã cập nhật thương hiệu "' . $ten . '".');
                } else {
                    $conn->prepare('INSERT INTO thuonghieu (TH_Ten, TH_Logo, TH_MoTa) VALUES (:t, :l, :m)')->execute($params);
                    ac_flash('ok', 'Đã thêm thương hiệu "' . $ten . '".');
                }
                ac_redirect("$self?tab=thuonghieu");
            }
        }

        if ($do === 'th_delete' && $id) {
            $st = $conn->prepare('SELECT (SELECT COUNT(*) FROM nhaccu WHERE TH_Ma = :a) + (SELECT COUNT(*) FROM phukien WHERE TH_Ma = :b)');
            $st->execute(['a' => $id, 'b' => $id]);
            if ((int)$st->fetchColumn() > 0) {
                ac_flash('err', 'Không xóa được: vẫn còn nhạc cụ hoặc phụ kiện thuộc thương hiệu này.');
            } else {
                $conn->prepare('DELETE FROM thuonghieu WHERE TH_Ma = :id')->execute(['id' => $id]);
                ac_flash('ok', 'Đã xóa thương hiệu.');
            }
            ac_redirect("$self?tab=thuonghieu");
        }

        if ($do === 'pk_rename') {
            $old = trim((string)($_POST['old'] ?? ''));
            $new = trim((string)($_POST['new'] ?? ''));
            if ($old === '') {
                ac_flash('err', 'Thiếu tên loại cần đổi.');
            } elseif ($new === '' || mb_strlen($new) > 100) {
                ac_flash('err', 'Tên loại phụ kiện mới phải có từ 1 đến 100 ký tự.');
            } elseif ($new === $old) {
                ac_flash('ok', 'Tên loại không thay đổi.');
            } else {
                $st = $conn->prepare('UPDATE chitietsanpham SET CTSP_LoaiPhuKien = :n WHERE PK_Ma IS NOT NULL AND CTSP_LoaiPhuKien = :o');
                $st->execute(['n' => $new, 'o' => $old]);
                ac_flash('ok', 'Đã đổi "' . $old . '" thành "' . $new . '" cho ' . $st->rowCount() . ' phụ kiện.');
            }
            ac_redirect("$self?tab=phukien");
        }

        if ($do === 'pk_clear') {
            $old = trim((string)($_POST['old'] ?? ''));
            if ($old !== '') {
                $st = $conn->prepare('UPDATE chitietsanpham SET CTSP_LoaiPhuKien = NULL WHERE PK_Ma IS NOT NULL AND CTSP_LoaiPhuKien = :o');
                $st->execute(['o' => $old]);
                ac_flash('ok', 'Đã bỏ loại "' . $old . '" khỏi ' . $st->rowCount() . ' phụ kiện.');
            }
            ac_redirect("$self?tab=phukien");
        }
    } catch (Throwable $ex) {
        error_log('[admin_categories] ' . $ex->getMessage());
        $errors[] = 'Có lỗi hệ thống khi lưu dữ liệu. Vui lòng thử lại.';
    }
}

if ($form === null && isset($_GET['edit']) && $tab !== 'phukien') {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
    if ($editId) {
        if ($tab === 'loai') {
            $st = $conn->prepare('SELECT LNC_Ten AS ten, LNC_MoTa AS mota FROM loainhaccu WHERE LNC_Ma = :id');
        } else {
            $st = $conn->prepare('SELECT TH_Ten AS ten, TH_Logo AS logo, TH_MoTa AS mota FROM thuonghieu WHERE TH_Ma = :id');
        }
        $st->execute(['id' => $editId]);
        $form = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$form) {
            ac_flash('err', 'Không tìm thấy mục cần sửa.');
            ac_redirect("$self?tab=$tab");
        }
    }
}
if ($form === null) {
    $form = ['ten' => '', 'logo' => '', 'mota' => ''];
}

/* ---------- dữ liệu cho giao diện ---------- */
$flash = $_SESSION['ac_flash'] ?? null;
unset($_SESSION['ac_flash']);

if ($tab === 'loai') {
    // Sắp xếp theo Mã loại (LNC_Ma ASC)
    $rows = $conn->query('SELECT l.LNC_Ma AS id, l.LNC_Ten AS ten, l.LNC_MoTa AS mota, COUNT(n.NC_Ma) AS sl
                          FROM loainhaccu l LEFT JOIN nhaccu n ON n.LNC_Ma = l.LNC_Ma
                          GROUP BY l.LNC_Ma, l.LNC_Ten, l.LNC_MoTa ORDER BY l.LNC_Ma ASC')->fetchAll(PDO::FETCH_ASSOC);
} elseif ($tab === 'thuonghieu') {
    // Sắp xếp theo Mã thương hiệu (TH_Ma ASC)
    $rows = $conn->query('SELECT t.TH_Ma AS id, t.TH_Ten AS ten, t.TH_Logo AS logo, t.TH_MoTa AS mota,
                                 (SELECT COUNT(*) FROM nhaccu n WHERE n.TH_Ma = t.TH_Ma) AS sl_nc,
                                 (SELECT COUNT(*) FROM phukien p WHERE p.TH_Ma = t.TH_Ma) AS sl_pk
                          FROM thuonghieu t ORDER BY t.TH_Ma ASC')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $rows = $conn->query("SELECT CTSP_LoaiPhuKien AS ten, COUNT(*) AS sl
                          FROM chitietsanpham
                          WHERE PK_Ma IS NOT NULL AND CTSP_LoaiPhuKien IS NOT NULL AND CTSP_LoaiPhuKien <> ''
                          GROUP BY CTSP_LoaiPhuKien ORDER BY CTSP_LoaiPhuKien")->fetchAll(PDO::FETCH_ASSOC);
    $untyped = (int)$conn->query("SELECT COUNT(*) FROM phukien p
                                  LEFT JOIN chitietsanpham c ON c.PK_Ma = p.PK_Ma
                                  WHERE c.CTSP_LoaiPhuKien IS NULL OR c.CTSP_LoaiPhuKien = ''")->fetchColumn();
}

$csrf = ac_csrf();
$inputCls = 'w-full h-10 border border-[#cbbf8c] rounded px-3 bg-white focus:outline-none focus:ring-2 focus:ring-[#a28d46] text-base';
$areaCls  = 'w-full border border-[#cbbf8c] rounded px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-[#a28d46] text-base';
$tabs = ['loai' => 'Loại nhạc cụ', 'thuonghieu' => 'Thương hiệu', 'phukien' => 'Loại phụ kiện'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tabs[$tab]) ?> – Quản trị</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f5f4f1] text-[#111] min-h-screen text-base">

<header class="bg-white border-b border-[#b49a4a]">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center gap-x-6 gap-y-2">
        <a href="admin_products.php" class="font-semibold text-lg text-[#8e7938]">Quản trị sản phẩm</a>
        <nav class="flex flex-wrap items-center gap-1 text-base">
            <a href="admin_products.php?type=nhaccu" class="px-3 py-1.5 rounded hover:bg-[#f5f4f1]">Nhạc cụ</a>
            <a href="admin_products.php?type=phukien" class="px-3 py-1.5 rounded hover:bg-[#f5f4f1]">Phụ kiện</a>
            <a href="<?= e($self) ?>" class="px-3 py-1.5 rounded bg-[#a28d46] text-white">Loại &amp; thương hiệu</a>
        </nav>
        <a href="index.php" class="ml-auto text-base text-gray-600 hover:underline">← Về trang web</a>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-6">

    <h1 class="text-2xl font-semibold mb-4">Loại &amp; thương hiệu</h1>

    <div class="flex flex-wrap gap-1 mb-5 border-b border-[#e4dcc0]">
        <?php foreach ($tabs as $k => $label): ?>
            <a href="<?= e($self) ?>?tab=<?= e($k) ?>"
               class="px-4 py-2 -mb-px border-b-2 <?= $tab === $k ? 'border-[#a28d46] font-semibold text-[#8e7938]' : 'border-transparent hover:text-[#8e7938]' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($flash): ?>
        <div class="mb-4 px-4 py-3 rounded text-base <?= $flash['kind'] === 'ok' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
            <?= e($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="mb-4 px-4 py-3 rounded text-base bg-red-50 text-red-800 border border-red-200">
            <p class="font-semibold mb-1">Chưa lưu được, vui lòng kiểm tra lại:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                <?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

<?php if ($tab === 'loai'): ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white border border-[#e4dcc0] rounded overflow-x-auto">
            <table class="w-full text-base">
                <thead class="bg-[#f8f4e9] text-left">
                    <tr>
                        <th class="px-4 py-3 w-16">Mã</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Loại nhạc cụ</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Số nhạc cụ</th>
                        <th class="px-4 py-3 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">Chưa có loại nhạc cụ nào. Dùng biểu mẫu bên cạnh để thêm loại đầu tiên.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr class="border-t border-[#eee7cf] align-top">
                        <td class="px-4 py-3 text-gray-500 font-medium"><?= (int)$r['id'] ?></td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-900"><?= e($r['ten']) ?></span>
                            <?php if (!empty($r['mota'])): ?><br><span class="text-gray-500 text-sm"><?= e($r['mota']) ?></span><?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="admin_products.php?type=nhaccu&amp;lnc=<?= (int)$r['id'] ?>" class="text-[#8e7938] font-medium hover:underline"><?= (int)$r['sl'] ?></a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end items-center gap-2 whitespace-nowrap">
                                <a href="<?= e($self) ?>?tab=loai&amp;edit=<?= (int)$r['id'] ?>" class="px-3 py-1.5 rounded border border-[#a28d46] text-[#8e7938] font-medium hover:bg-[#f8f4e9]">Sửa</a>
                                <form method="post" data-confirm="Xóa loại &quot;<?= e($r['ten']) ?>&quot;?">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="tab" value="loai">
                                    <input type="hidden" name="do" value="loai_delete">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button class="px-3 py-1.5 rounded border border-red-300 text-red-700 font-medium hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                            <?= (int)$r['sl'] > 0 ? 'disabled title="Còn nhạc cụ thuộc loại này"' : '' ?>>Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="post" class="bg-white border border-[#e4dcc0] rounded p-5 space-y-4 self-start">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="tab" value="loai">
            <input type="hidden" name="do" value="loai_save">
            <input type="hidden" name="id" value="<?= (int)$editId ?>">
            <h2 class="font-semibold text-lg border-b-2 border-black pb-2"><?= $editId ? 'Sửa loại nhạc cụ' : 'Thêm loại nhạc cụ' ?></h2>
            <div>
                <label for="ten" class="block font-medium mb-1">Tên loại *</label>
                <input id="ten" name="ten" maxlength="100" required value="<?= e($form['ten']) ?>" class="<?= $inputCls ?>">
            </div>
            <div>
                <label for="mota" class="block font-medium mb-1">Mô tả</label>
                <textarea id="mota" name="mota" rows="3" class="<?= $areaCls ?>"><?= e($form['mota']) ?></textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button class="flex-1 h-10 rounded bg-[#a28d46] text-white font-semibold hover:bg-[#8e7938]"><?= $editId ? 'Lưu thay đổi' : 'Thêm loại' ?></button>
                <?php if ($editId): ?><a href="<?= e($self) ?>?tab=loai" class="h-10 px-4 inline-flex items-center rounded border border-gray-300 hover:bg-gray-50 font-medium">Hủy</a><?php endif; ?>
            </div>
            <p class="text-xs text-gray-500">Trang sanpham.php đang lọc theo danh sách loại cố định trong mã nguồn, nên loại mới thêm ở đây chưa xuất hiện ở bộ lọc của trang đó.</p>
        </form>
    </div>

<?php elseif ($tab === 'thuonghieu'): ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white border border-[#e4dcc0] rounded overflow-x-auto">
            <table class="w-full text-base">
                <thead class="bg-[#f8f4e9] text-left">
                    <tr>
                        <th class="px-4 py-3 w-16">Mã</th>
                        <th class="px-4 py-3 text-center">Thương hiệu</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Nhạc cụ</th>
<th class="px-4 py-3 text-center whitespace-nowrap">Phụ kiện</th>
                        <th class="px-4 py-3 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">Chưa có thương hiệu nào. Dùng biểu mẫu bên cạnh để thêm thương hiệu đầu tiên.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r):
                    $used = (int)$r['sl_nc'] + (int)$r['sl_pk'];
                    $logo = ac_image_url($r['logo']);
                    ?>
                    <tr class="border-t border-[#eee7cf] align-top">
                        <td class="px-4 py-3 text-gray-500 font-medium"><?= (int)$r['id'] ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 shrink-0 border border-[#e4dcc0] bg-white flex items-center justify-center overflow-hidden">
                                    <?php if ($logo !== ''): ?><img src="<?= e($logo) ?>" alt="" class="max-w-full max-h-full object-contain"><?php endif; ?>
                                </div>
                                <div>
                                    <span class="font-semibold text-gray-900"><?= e($r['ten']) ?></span>
                                    <?php if (!empty($r['mota'])): ?><br><span class="text-gray-500 text-sm"><?= e($r['mota']) ?></span><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="admin_products.php?type=nhaccu&amp;th=<?= (int)$r['id'] ?>" class="text-[#8e7938] font-medium hover:underline"><?= (int)$r['sl_nc'] ?></a>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="admin_products.php?type=phukien&amp;th=<?= (int)$r['id'] ?>" class="text-[#8e7938] font-medium hover:underline"><?= (int)$r['sl_pk'] ?></a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end items-center gap-2 whitespace-nowrap">
                                <a href="<?= e($self) ?>?tab=thuonghieu&amp;edit=<?= (int)$r['id'] ?>" class="px-3 py-1.5 rounded border border-[#a28d46] text-[#8e7938] font-medium hover:bg-[#f8f4e9]">Sửa</a>
                                <form method="post" data-confirm="Xóa thương hiệu &quot;<?= e($r['ten']) ?>&quot;?">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="tab" value="thuonghieu">
                                    <input type="hidden" name="do" value="th_delete">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button class="px-3 py-1.5 rounded border border-red-300 text-red-700 font-medium hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                            <?= $used > 0 ? 'disabled title="Còn sản phẩm thuộc thương hiệu này"' : '' ?>>Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="post" enctype="multipart/form-data" class="bg-white border border-[#e4dcc0] rounded p-5 space-y-4 self-start">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="tab" value="thuonghieu">
            <input type="hidden" name="do" value="th_save">
            <input type="hidden" name="id" value="<?= (int)$editId ?>">
            <h2 class="font-semibold text-lg border-b-2 border-black pb-2"><?= $editId ? 'Sửa thương hiệu' : 'Thêm thương hiệu' ?></h2>
            <p class="text-xs text-gray-500 -mt-2">Thương hiệu dùng chung cho cả nhạc cụ và phụ kiện.</p>
            <div>
                <label for="ten" class="block font-medium mb-1">Tên thương hiệu *</label>
                <input id="ten" name="ten" maxlength="100" required value="<?= e($form['ten']) ?>" class="<?= $inputCls ?>">
            </div>
            <div>
                <label for="anh" class="block font-medium mb-1">Tải logo mới</label>
                <input id="anh" name="anh" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-base">
            </div>
            <div>
                <label for="logo" class="block font-medium mb-1">Hoặc tên file trong images/</label>
                <input id="logo" name="logo" maxlength="255" value="<?= e($form['logo']) ?>" placeholder="vd: yamaha.png" class="<?= $inputCls ?>">
            </div>
            <div>
                <label for="mota" class="block font-medium mb-1">Mô tả</label>
                <textarea id="mota" name="mota" rows="3" class="<?= $areaCls ?>"><?= e($form['mota']) ?></textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button class="flex-1 h-10 rounded bg-[#a28d46] text-white font-semibold hover:bg-[#8e7938]"><?= $editId ? 'Lưu thay đổi' : 'Thêm thương hiệu' ?></button>
                <?php if ($editId): ?><a href="<?= e($self) ?>?tab=thuonghieu" class="h-10 px-4 inline-flex items-center rounded border border-gray-300 hover:bg-gray-50 font-medium">Hủy</a><?php endif; ?>
            </div>
        </form>
    </div>

<?php else: ?>

    <div class="bg-white border border-[#e4dcc0] rounded p-5 mb-4 text-base leading-6">
        <p>Loại phụ kiện được lấy từ mục <strong>Loại phụ kiện</strong> trong thông số kỹ thuật của từng phụ kiện. Tại đây bạn có thể đổi tên một loại cho tất cả phụ kiện đang dùng nó (đổi sang tên đã có là gộp hai loại), hoặc bỏ loại khỏi các phụ kiện đó.</p>
        <p class="mt-1">Để tạo loại mới, nhập tên loại khi <a href="admin_products.php?type=phukien" class="text-[#8e7938] underline">thêm hoặc sửa một phụ kiện</a>.</p>
        <?php if ($untyped > 0): ?>
            <p class="mt-2 text-gray-600">Có <strong><?= $untyped ?></strong> phụ kiện chưa được gán loại.</p>
        <?php endif; ?>
    </div>

    <div class="bg-white border border-[#e4dcc0] rounded overflow-x-auto">
        <table class="w-full text-base">
            <thead class="bg-[#f8f4e9] text-left">
                <tr>
                    <th class="px-4 py-3 ">Loại phụ kiện</th>
                    <th class="px-4 py-3 text-center whitespace-nowrap">Số phụ kiện</th>
                    <th class="px-4 py-3 text-center">Đổi tên</th>
                    <th class="px-4 py-3 text-center w-28">Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">Chưa có phụ kiện nào được gán loại. Nhập "Loại phụ kiện" khi thêm hoặc sửa phụ kiện để loại xuất hiện ở đây.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $r): ?>
                <tr class="border-t border-[#eee7cf] align-middle">
                    <td class="px-4 py-3 font-semibold text-gray-900"><?= e($r['ten']) ?></td>
                    <td class="px-4 py-3 text-center font-medium"><?= (int)$r['sl'] ?></td>
                    <td class="px-4 py-3">
                        <form method="post" class="flex gap-2" id="rename-<?= $i ?>">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="tab" value="phukien">
                            <input type="hidden" name="do" value="pk_rename">
                            <input type="hidden" name="old" value="<?= e($r['ten']) ?>">
                            <label for="new-<?= $i ?>" class="sr-only">Tên mới cho <?= e($r['ten']) ?></label>
                            <input id="new-<?= $i ?>" name="new" maxlength="100" required value="<?= e($r['ten']) ?>" class="<?= $inputCls ?> max-w-xs">
                            <button class="h-10 px-4 rounded border border-[#a28d46] text-[#8e7938] font-medium hover:bg-[#f8f4e9] whitespace-nowrap">Đổi tên</button>
                        </form>
                    </td>
                    <td class="px-4 py-3">
                        <form method="post" class="flex justify-end" data-confirm="Bỏ loại &quot;<?= e($r['ten']) ?>&quot; khỏi <?= (int)$r['sl'] ?> phụ kiện?">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <input type="hidden" name="tab" value="phukien">
                            <input type="hidden" name="do" value="pk_clear">
                            <input type="hidden" name="old" value="<?= e($r['ten']) ?>">
                            <button class="px-3 py-1.5 rounded border border-red-300 text-red-700 font-medium hover:bg-red-50 whitespace-nowrap">Bỏ loại</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
</main>

<script>
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
        if (!confirm(f.dataset.confirm)) ev.preventDefault();
    });
});
</script>
</body>
</html>