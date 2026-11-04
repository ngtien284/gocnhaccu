<?php
/**
 * Hàm dùng chung cho chức năng đánh giá sản phẩm.
 * Được require ở chitietsanpham.php (trước khi xuất HTML) và danh-gia-gui.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ====== CẤU HÌNH — chỉnh cho khớp với dự án của bạn ====== */
const DG_LOGIN_URL = 'login.php';   // trang đăng nhập
const DG_PER_PAGE  = 5;                 // số đánh giá mỗi trang

/** Lấy khách hàng đang đăng nhập. Chỉnh tên khóa SESSION nếu dự án dùng tên khác. */
function dg_current_customer(): ?array
{
    $email = $_SESSION['user'] ?? null;
    $name  = $_SESSION['user_name'] ?? 'Khách hàng';

    if (!empty($email) && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO) {
        try {
            $stmt = $GLOBALS['conn']->prepare("SELECT ND_Ma, ND_Ten FROM nguoi_dung WHERE ND_Email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userRow) {
                return [
                    'id'   => (int)$userRow['ND_Ma'],
                    'name' => !empty($userRow['ND_Ten']) ? $userRow['ND_Ten'] : $name
                ];
            }
        } catch (Exception $e) {
            // Xử lý lỗi ngầm nếu có
        }
    }

    return null;
}
function dg_customer_has_purchased(PDO $conn, int $khMa, string $type, int $productId): bool
{
    // Nếu là nhạc cụ, kiểm tra qua cột NC_Ma trong chi_tiet_don_hang
    if ($type === 'nhaccu') {
        $stmt = $conn->prepare("
            SELECT 1
            FROM don_hang dh
            INNER JOIN chi_tiet_don_hang ct ON ct.DH_Ma = dh.DH_Ma
            WHERE dh.ND_Ma = :kh
              AND dh.DH_TrangThai = 'Đã giao'
              AND ct.NC_Ma = :id
            LIMIT 1
        ");
        $stmt->execute(['kh' => $khMa, 'id' => $productId]);
        return (bool)$stmt->fetchColumn();
    }
    
    // Nếu hệ thống của bạn có bảng chi tiết riêng cho phụ kiện, bạn có thể bổ sung tương ứng ở đây.
    return false;
}

function dg_has_reviewed(PDO $conn, int $khMa, string $type, int $productId): bool
{
    $stmt = $conn->prepare("
        SELECT 1 FROM danhgia
        WHERE KH_Ma = :kh AND DG_LoaiSanPham = :loai AND DG_MaSanPham = :id LIMIT 1
    ");
    $stmt->execute(['kh' => $khMa, 'loai' => $type, 'id' => $productId]);
    return (bool)$stmt->fetchColumn();
}

/** Thống kê: điểm trung bình, tổng số, phân bố 1-5 sao, % giới thiệu. */
function dg_get_summary(PDO $conn, string $type, int $productId): array
{
    $summary = ['total' => 0, 'avg' => 0.0, 'recommend' => 0, 'dist' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];

    $stmt = $conn->prepare("
        SELECT DG_SoSao, COUNT(*) AS c, SUM(DG_GioiThieu) AS r
        FROM danhgia
        WHERE DG_LoaiSanPham = :loai AND DG_MaSanPham = :id AND DG_TrangThai = 1
        GROUP BY DG_SoSao
    ");
    $stmt->execute(['loai' => $type, 'id' => $productId]);

    $sumStars = 0;
    $sumRec = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $star = (int)$row['DG_SoSao'];
        $count = (int)$row['c'];
        $summary['dist'][$star] = $count;
        $summary['total'] += $count;
        $sumStars += $star * $count;
        $sumRec += (int)$row['r'];
    }

    if ($summary['total'] > 0) {
        $summary['avg'] = round($sumStars / $summary['total'], 1);
        $summary['recommend'] = (int)round($sumRec * 100 / $summary['total']);
    }
    return $summary;
}

/** Vẽ chuỗi sao (★ đầy, ☆ rỗng). */
function dg_stars(float $rating): string
{
    $full = (int)round($rating);
    return '<span class="dg-stars" aria-label="' . $rating . ' trên 5 sao">'
        . str_repeat('★', $full) . '<span class="dg-star-off">' . str_repeat('★', 5 - $full) . '</span></span>';
}

/** "11 ngày trước", "1 tháng trước"... */
function dg_time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)        return 'vừa xong';
    if ($diff < 3600)      return floor($diff / 60) . ' phút trước';
    if ($diff < 86400)     return floor($diff / 3600) . ' giờ trước';
    if ($diff < 2592000)   return floor($diff / 86400) . ' ngày trước';
    if ($diff < 31536000)  return floor($diff / 2592000) . ' tháng trước';
    return floor($diff / 31536000) . ' năm trước';
}

function dg_csrf_token(): string
{
    if (empty($_SESSION['dg_csrf'])) {
        $_SESSION['dg_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['dg_csrf'];
}
