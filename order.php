<?php
// config/orders.php - Hàm dùng chung cho đơn hàng
require_once __DIR__ . '/payment.php';

const PAY_STATUS_WAITING   = 'Chờ thanh toán';
const PAY_STATUS_CONFIRM   = 'Chờ xác nhận';
const PAY_STATUS_PAID      = 'Đã thanh toán';

const ORDER_STATUS_NEW     = 'Chờ xử lý';
const ORDER_STATUS_SHIPPING = 'Đang giao';
const ORDER_STATUS_DONE    = 'Hoàn thành';
const ORDER_STATUS_CANCEL  = 'Đã hủy';

if (!function_exists('format_vnd')) {
    function format_vnd($number)
    {
        return number_format((float)$number, 0, ',', '.') . '₫';
    }
}

function e_html($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Quản trị viên: email nằm trong ADMIN_EMAILS hoặc loại người dùng có chữ admin / quản trị / quản lý
function is_admin_user(): bool
{
    $email = strtolower((string)($_SESSION['user'] ?? ''));
    if ($email === '') {
        return false;
    }
    foreach (ADMIN_EMAILS as $adminEmail) {
        if (strtolower($adminEmail) === $email) {
            return true;
        }
    }
    $role = (string)($_SESSION['role_name'] ?? '');
    return $role !== '' && preg_match('/admin|quản trị|quan tri|quản lý|quan ly/iu', $role) === 1;
}

// Lấy thông tin người dùng đang đăng nhập (theo email trong session)
function current_user_row(PDO $conn): ?array
{
    $email = (string)($_SESSION['user'] ?? '');
    if ($email === '') {
        return null;
    }
    $st = $conn->prepare("SELECT ND_Ma, ND_Ten, ND_Email, ND_SDT, ND_DiaChi FROM nguoi_dung WHERE ND_Email = ? LIMIT 1");
    $st->execute([$email]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// Bảng chi_tiet_don_hang đã có cột PK_Ma (hỗ trợ phụ kiện) chưa?
function order_has_pk_column(PDO $conn): bool
{
    static $has = null;
    if ($has === null) {
        $has = (bool)$conn->query("SHOW COLUMNS FROM chi_tiet_don_hang LIKE 'PK_Ma'")->fetch();
    }
    return $has;
}

// Tách địa chỉ "số nhà, phường, quận, tỉnh" thành các phần để lưu vào đơn hàng
function split_address(string $full): array
{
    $full = trim($full);
    if ($full === '' || $full === 'Chưa cập nhật') {
        return ['city' => 'Chưa cập nhật', 'ward' => 'Chưa cập nhật', 'street' => 'Chưa cập nhật'];
    }
    $parts = array_values(array_filter(array_map('trim', explode(',', $full)), 'strlen'));
    $n = count($parts);
    if ($n >= 4) {
        return [
            'city'   => $parts[$n - 1],
            'ward'   => $parts[$n - 2] . ', ' . $parts[$n - 3],   // quận/huyện, phường/xã
            'street' => implode(', ', array_slice($parts, 0, $n - 3)),
        ];
    }
    return ['city' => $parts[$n - 1], 'ward' => 'Chưa cập nhật', 'street' => $full];
}

function order_image_url(?string $image): string
{
    $image = trim((string)$image);
    if ($image === '') {
        return '';
    }
    return preg_match('~^https?://~i', $image) ? $image : 'images/' . ltrim($image, '/');
}

// Màu nhãn trạng thái
function status_badge_class(string $status): string
{
    switch ($status) {
        case PAY_STATUS_PAID:
        case ORDER_STATUS_DONE:
            return 'bg-emerald-50 text-emerald-800 border-emerald-200';
        case PAY_STATUS_CONFIRM:
        case ORDER_STATUS_SHIPPING:
            return 'bg-sky-50 text-sky-800 border-sky-200';
        case ORDER_STATUS_CANCEL:
            return 'bg-red-50 text-red-700 border-red-200';
        default:
            return 'bg-amber-50 text-amber-800 border-amber-200';
    }
}

// Tạo đơn hàng từ các sản phẩm đã tick trong giỏ. Ném RuntimeException kèm thông báo cho khách nếu không tạo được.
function create_order_from_cart(PDO $conn, array $cartKeys): int
{
    $user = current_user_row($conn);
    if (!$user) {
        throw new RuntimeException('Không tìm thấy tài khoản của bạn, vui lòng đăng nhập lại.');
    }
    if (empty($cartKeys)) {
        throw new RuntimeException('Vui lòng tick chọn ít nhất một sản phẩm để thanh toán.');
    }

    $hasPk = order_has_pk_column($conn);
    $lines = [];
    $total = 0.0;

    $conn->beginTransaction();
    try {
        foreach ($cartKeys as $key) {
            $item = $_SESSION['gio_hang'][$key] ?? null;
            if (!$item) {
                continue;
            }
            $qty  = max(1, (int)$item['so_luong']);
            $id   = (int)$item['ma'];
            $type = ($item['loai'] ?? 'nhaccu') === 'phukien' ? 'phukien' : 'nhaccu';

            if ($type === 'phukien') {
                if (!$hasPk) {
                    throw new RuntimeException('Giỏ hàng có phụ kiện. Cần chạy file them_phu_kien_don_hang.sql trong phpMyAdmin trước khi đặt hàng phụ kiện.');
                }
                $st = $conn->prepare("SELECT PK_Ten AS ten, PK_GiaBan AS gia_ban, PK_GiaKhuyenMai AS gia_km,
                                             PK_SoLuongTon AS ton, PK_TrangThai AS trang_thai
                                      FROM phukien WHERE PK_Ma = ? FOR UPDATE");
            } else {
                $st = $conn->prepare("SELECT NC_Ten AS ten, NC_GiaBan AS gia_ban,
                                             CASE WHEN NC_GiaKhuyenMai > 0 AND (NC_KhuyenMaiDen IS NULL OR NC_KhuyenMaiDen > NOW())
                                                  THEN NC_GiaKhuyenMai ELSE NULL END AS gia_km,
                                             NC_SoLuongTon AS ton, NC_TrangThai AS trang_thai
                                      FROM nhaccu WHERE NC_Ma = ? FOR UPDATE");
            }
            $st->execute([$id]);
            $p = $st->fetch(PDO::FETCH_ASSOC);

            if (!$p || (int)$p['trang_thai'] !== 1) {
                throw new RuntimeException('Sản phẩm "' . ($item['ten'] ?? '') . '" hiện không còn bán.');
            }
            if ((int)$p['ton'] < $qty) {
                throw new RuntimeException('Sản phẩm "' . $p['ten'] . '" chỉ còn ' . (int)$p['ton'] . ' trong kho.');
            }

            $price = ($p['gia_km'] !== null && (float)$p['gia_km'] > 0) ? (float)$p['gia_km'] : (float)$p['gia_ban'];
            $sub   = $price * $qty;
            $total += $sub;

            $lines[] = ['type' => $type, 'id' => $id, 'qty' => $qty, 'price' => $price, 'sub' => $sub];
        }

        if (empty($lines)) {
            throw new RuntimeException('Không có sản phẩm hợp lệ để đặt hàng.');
        }

        $addr = split_address((string)($user['ND_DiaChi'] ?? ''));
        $phone = trim((string)($user['ND_SDT'] ?? '')) !== '' ? $user['ND_SDT'] : 'Chưa cập nhật';

        $ins = $conn->prepare("INSERT INTO don_hang
            (ND_Ma, DH_TongTien, DH_DiaChiNhan_TP, DH_DiaChiNhan_PhuongXa, DH_DiaChiNhan_SoNha,
             DH_SoDTNhan, DH_TenNguoiNhan, DH_PhuongThucThanhToan, DH_TrangThaiThanhToan, DH_TrangThai)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'QR', ?, ?)");
        $ins->execute([
            (int)$user['ND_Ma'], $total, $addr['city'], $addr['ward'], $addr['street'],
            $phone, $user['ND_Ten'], PAY_STATUS_WAITING, ORDER_STATUS_NEW,
        ]);
        $orderId = (int)$conn->lastInsertId();

        foreach ($lines as $ln) {
            if ($ln['type'] === 'phukien') {
                $conn->prepare("INSERT INTO chi_tiet_don_hang (DH_Ma, NC_Ma, PK_Ma, CTDH_SoLuongSP, CTDH_Gia, CTDH_ThanhTien)
                                VALUES (?, NULL, ?, ?, ?, ?)")
                     ->execute([$orderId, $ln['id'], $ln['qty'], $ln['price'], $ln['sub']]);
                $conn->prepare("UPDATE phukien SET PK_SoLuongTon = PK_SoLuongTon - ? WHERE PK_Ma = ?")
                     ->execute([$ln['qty'], $ln['id']]);
            } else {
                if ($hasPk) {
                    $conn->prepare("INSERT INTO chi_tiet_don_hang (DH_Ma, NC_Ma, PK_Ma, CTDH_SoLuongSP, CTDH_Gia, CTDH_ThanhTien)
                                    VALUES (?, ?, NULL, ?, ?, ?)")
                         ->execute([$orderId, $ln['id'], $ln['qty'], $ln['price'], $ln['sub']]);
                } else {
                    $conn->prepare("INSERT INTO chi_tiet_don_hang (DH_Ma, NC_Ma, CTDH_SoLuongSP, CTDH_Gia, CTDH_ThanhTien)
                                    VALUES (?, ?, ?, ?, ?)")
                         ->execute([$orderId, $ln['id'], $ln['qty'], $ln['price'], $ln['sub']]);
                }
                $conn->prepare("UPDATE nhaccu SET NC_SoLuongTon = NC_SoLuongTon - ? WHERE NC_Ma = ?")
                     ->execute([$ln['qty'], $ln['id']]);
            }
        }

        $conn->commit();
        return $orderId;
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}

// Danh sách sản phẩm của một đơn hàng
function fetch_order_items(PDO $conn, int $orderId): array
{
    if (order_has_pk_column($conn)) {
        $sql = "SELECT c.*, nc.NC_Ten, nc.NC_HinhAnh, pk.PK_Ten, pk.PK_HinhAnh
                FROM chi_tiet_don_hang c
                LEFT JOIN nhaccu nc ON c.NC_Ma = nc.NC_Ma
                LEFT JOIN phukien pk ON c.PK_Ma = pk.PK_Ma
                WHERE c.DH_Ma = ? ORDER BY c.CTDH_Ma";
    } else {
        $sql = "SELECT c.*, nc.NC_Ten, nc.NC_HinhAnh, NULL AS PK_Ten, NULL AS PK_HinhAnh
                FROM chi_tiet_don_hang c
                LEFT JOIN nhaccu nc ON c.NC_Ma = nc.NC_Ma
                WHERE c.DH_Ma = ? ORDER BY c.CTDH_Ma";
    }
    $st = $conn->prepare($sql);
    $st->execute([$orderId]);

    $items = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $isPk = !empty($r['PK_Ma']);
        $items[] = [
            'name'  => $isPk ? ($r['PK_Ten'] ?? 'Phụ kiện') : ($r['NC_Ten'] ?? 'Sản phẩm'),
            'image' => order_image_url($isPk ? ($r['PK_HinhAnh'] ?? '') : ($r['NC_HinhAnh'] ?? '')),
            'qty'   => (int)$r['CTDH_SoLuongSP'],
            'price' => (float)$r['CTDH_Gia'],
            'total' => (float)$r['CTDH_ThanhTien'],
        ];
    }
    return $items;
}

// Hủy đơn: hoàn lại tồn kho và đánh dấu "Đã hủy" (không hủy đơn đã thanh toán, trừ khi $force)
function cancel_order(PDO $conn, int $orderId, bool $force = false): bool
{
    $conn->beginTransaction();
    try {
        $st = $conn->prepare("SELECT DH_TrangThai, DH_TrangThaiThanhToan FROM don_hang WHERE DH_Ma = ? FOR UPDATE");
        $st->execute([$orderId]);
        $o = $st->fetch(PDO::FETCH_ASSOC);
        if (!$o || $o['DH_TrangThai'] === ORDER_STATUS_CANCEL
            || (!$force && $o['DH_TrangThaiThanhToan'] === PAY_STATUS_PAID)) {
            $conn->rollBack();
            return false;
        }

        $hasPk = order_has_pk_column($conn);
        $cols = $hasPk ? 'NC_Ma, PK_Ma' : 'NC_Ma, NULL AS PK_Ma';
        $items = $conn->prepare("SELECT $cols, CTDH_SoLuongSP FROM chi_tiet_don_hang WHERE DH_Ma = ?");
        $items->execute([$orderId]);
        foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $it) {
            if (!empty($it['PK_Ma'])) {
                $conn->prepare("UPDATE phukien SET PK_SoLuongTon = PK_SoLuongTon + ? WHERE PK_Ma = ?")
                     ->execute([(int)$it['CTDH_SoLuongSP'], (int)$it['PK_Ma']]);
            } elseif (!empty($it['NC_Ma'])) {
                $conn->prepare("UPDATE nhaccu SET NC_SoLuongTon = NC_SoLuongTon + ? WHERE NC_Ma = ?")
                     ->execute([(int)$it['CTDH_SoLuongSP'], (int)$it['NC_Ma']]);
            }
        }

        $conn->prepare("UPDATE don_hang SET DH_TrangThai = ? WHERE DH_Ma = ?")
             ->execute([ORDER_STATUS_CANCEL, $orderId]);
        $conn->commit();
        return true;
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}