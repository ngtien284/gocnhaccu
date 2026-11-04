<?php
// profile.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra bảo vệ: Nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Hàm kiểm tra quyền Admin
function is_admin(): bool {
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
    
    // Kiểm tra qua Email hoặc Tên tài khoản Admin hiển thị
    $email = is_string($_SESSION['user'] ?? null) ? $_SESSION['user'] : ($_SESSION['user']['email'] ?? '');
    $name  = $_SESSION['user_name'] ?? '';
    if (strpos(strtolower($email), 'admin') !== false || mb_strpos($name, 'Quản Trị') !== false) {
        return true;
    }

    return false;
}

$is_admin_user = is_admin();

$page_title = "Hồ Sơ Cá Nhân & Quản Lý Nhạc Khí | GOCNHACCU";

// Lấy thông tin user hiện tại từ Session
$user_name = $_SESSION['user_name'] ?? 'Quản Trị Viên';
$user_email = is_string($_SESSION['user'] ?? null) ? $_SESSION['user'] : ($_SESSION['user']['email'] ?? 'admin@gocnhaccu.vn');
$user_phone = $_SESSION['registered_phone'] ?? '0901234567';
$user_dob = $_SESSION['registered_dob'] ?? 'Chưa cập nhật';
$user_address = $_SESSION['registered_address'] ?? 'Đường 3/2, Q. Ninh Kiều, TP. Cần Thơ';
$user_avatar = $_SESSION['user_avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';
?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?></title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <style>
    :root {
      --wood-primary: #8C5835;
      --wood-dark: #754728;
      --wood-bg: #FBF8F5;
      --wood-border: #E7E0D8;
      --wood-text: #2D2319;
    }
    
    body {
      background-color: var(--wood-bg);
      color: var(--wood-text);
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    }

    .btn-wood {
      background-color: var(--wood-primary);
      color: #FFFFFF;
      border: none;
    }
    .btn-wood:hover {
      background-color: var(--wood-dark);
      color: #FFFFFF;
    }

    .sidebar-menu .list-group-item {
      border: none;
      background: transparent;
      color: var(--wood-text);
      font-weight: 500;
      padding: 12px 16px;
      border-radius: 8px !important;
      margin-bottom: 4px;
      transition: all 0.2s;
    }
    .sidebar-menu .list-group-item:hover, 
    .sidebar-menu .list-group-item.active {
      background-color: #EFE7DF;
      color: var(--wood-primary);
      font-weight: 600;
    }

    .card-dashboard {
      background-color: #FFFFFF;
      border: 1px solid var(--wood-border);
      border-radius: 16px;
    }
  </style>
</head>

<body>

  <!-- Gọi thanh Header chung của website -->
  <?php include 'includes/header.php'; ?>

  <main class="container py-4">
    
    <!-- Breadcrumb điều hướng -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Hồ sơ cá nhân</li>
      </ol>
    </nav>

    <div class="row g-4">
      
      <!-- CỘT BÊN TRÁI: SIDEBAR MENU QUẢN LÝ -->
      <div class="col-12 col-lg-3">
        <div class="card-dashboard p-4 shadow-sm mb-4 text-center">
          <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Avatar" class="rounded-circle shadow-sm mb-3 border border-2 border-warning" style="width: 80px; height: 80px; object-fit: cover;" />
          <h2 class="fs-6 fw-bold mb-1"><?= htmlspecialchars($user_name) ?></h2>
          <p class="text-muted small mb-2"><?= htmlspecialchars($user_email) ?></p>
          <?php if ($is_admin_user): ?>
            <span class="badge bg-warning text-dark px-2 py-1 small fw-semibold"><i class="fa-solid fa-user-shield me-1"></i> Quản Trị Viên</span>
          <?php else: ?>
            <span class="badge bg-warning text-dark px-2 py-1 small fw-semibold"><i class="fa-solid fa-crown me-1"></i> Thành Viên VIP</span>
          <?php endif; ?>
        </div>

        <div class="card-dashboard p-3 shadow-sm sidebar-menu">
          <div class="list-group list-group-flush">
            <a href="profile.php" class="list-group-item list-group-item-action active">
              <i class="fa-solid fa-user-gear me-2"></i> Thông tin & Studio
            </a>
            <a href="gio-hang.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-cart-shopping me-2"></i> Giỏ hàng của tôi
            </a>
            <a href="#" class="list-group-item list-group-item-action" onclick="alert('Tính năng đang phát triển!')">
              <i class="fa-solid fa-box-archive me-2"></i> Đơn hàng & Vận chuyển
            </a>
            <a href="#" class="list-group-item list-group-item-action" onclick="alert('Tính năng đang phát triển!')">
              <i class="fa-solid fa-shield-halved me-2"></i> Kho Serial & Bảo hành
            </a>

            <!-- MỤC CHỨC NĂNG ADMIN DÙNG MÀU CHỦ ĐẠO -->
            <?php if ($is_admin_user): ?>
              <div class="mt-3 pt-3 border-top">
                <span class="text-muted small fw-bold px-2 text-uppercase d-block mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">Quản Trị Hệ Thống</span>
              </div>
              <a href="admin_products.php?type=nhaccu" class="list-group-item list-group-item-action">
                <i class="fa-solid fa-guitar me-2" style="color: var(--wood-primary);"></i> Quản lý Nhạc Cụ
              </a>
              <a href="admin_products.php?type=phukien" class="list-group-item list-group-item-action">
                <i class="fa-solid fa-plug me-2" style="color: var(--wood-primary);"></i> Quản lý Phụ Kiện
              </a>
              <a href="admin_categories.php" class="list-group-item list-group-item-action">
                <i class="fa-solid fa-layer-group me-2" style="color: var(--wood-primary);"></i> Loại & Thương Hiệu
              </a>
            <?php endif; ?>

            <a href="logout.php" class="list-group-item list-group-item-action text-muted mt-3 border-top">
              <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất tài khoản
            </a>
          </div>
        </div>
      </div>

      <!-- CỘT BÊN PHẢI: NỘI DUNG CHI TIẾT HỒ SƠ -->
      <div class="col-12 col-lg-9">
        
        <!-- Header tiêu đề -->
        <div class="card-dashboard p-4 shadow-sm mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
          <div>
            <h1 class="fs-5 fw-bold text-dark mb-1">Hồ Sơ Cá Nhân & Quản Lý Nhạc Khí</h1>
            <p class="text-muted small mb-0">Quản lý định danh nghệ sĩ, thiết bị lưu kho và thông tin cá nhân của bạn.</p>
          </div>
          <button class="btn btn-wood btn-sm px-3 rounded-pill fw-semibold" onclick="alert('Chế độ chỉnh sửa đang cập nhật!')">
            <i class="fa-solid fa-pen-to-square me-1"></i> Chỉnh sửa nhanh
          </button>
        </div>

        <!-- KHỐI CHỨC NĂNG QUẢN TRỊ VIÊN MÀU ĐỒNG BỘ -->
        <?php if ($is_admin_user): ?>
        <div class="card-dashboard p-4 shadow-sm mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h2 class="fs-6 fw-bold text-dark mb-0">
              <i class="fa-solid fa-user-shield me-2" style="color: var(--wood-primary);"></i>Bảng Điều Khiển Quản Trị Viên
            </h2>
            <span class="badge bg-warning text-dark">Admin Console</span>
          </div>
          <p class="text-muted small mb-3">Vui lòng chọn chức năng quản lý danh mục và sản phẩm bên dưới:</p>
          
          <div class="row g-3">
            <div class="col-12 col-sm-4">
              <a href="admin_products.php?type=nhaccu" class="btn btn-wood w-100 py-2 rounded-3 text-start d-flex align-items-center justify-content-between">
                <span><i class="fa-solid fa-guitar me-2"></i> Quản lý Nhạc Cụ</span>
                <i class="fa-solid fa-chevron-right small opacity-75"></i>
              </a>
            </div>
            <div class="col-12 col-sm-4">
              <a href="admin_products.php?type=phukien" class="btn btn-wood w-100 py-2 rounded-3 text-start d-flex align-items-center justify-content-between">
                <span><i class="fa-solid fa-plug me-2"></i> Quản lý Phụ Kiện</span>
                <i class="fa-solid fa-chevron-right small opacity-75"></i>
              </a>
            </div>
            <div class="col-12 col-sm-4">
              <a href="admin_categories.php" class="btn btn-wood w-100 py-2 rounded-3 text-start d-flex align-items-center justify-content-between">
                <span><i class="fa-solid fa-layer-group me-2"></i> Loại & Thương Hiệu</span>
                <i class="fa-solid fa-chevron-right small opacity-75"></i>
              </a>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Khối Thông Tin Định Danh -->
        <div class="card-dashboard p-4 shadow-sm mb-4">
          <h2 class="fs-6 fw-bold text-dark mb-3 pb-2 border-bottom"><i class="fa-solid fa-id-card text-secondary me-2"></i>Thông Tin Định Danh Nghệ Sĩ</h2>
          
          <div class="row g-3 small">
            <div class="col-12 col-md-6">
              <span class="text-muted d-block">HỌ VÀ TÊN</span>
              <strong class="text-dark fs-6"><?= htmlspecialchars($user_name) ?></strong>
            </div>

            <div class="col-12 col-md-6">
              <span class="text-muted d-block">EMAIL TÀI KHOẢN</span>
              <strong class="text-dark"><?= htmlspecialchars($user_email) ?> <i class="fa-solid fa-circle-check text-success"></i></strong>
            </div>

            <div class="col-12 col-md-6">
              <span class="text-muted d-block">SỐ ĐIỆN THOẠI LIÊN LẠC</span>
              <strong class="text-dark"><?= htmlspecialchars($user_phone) ?></strong>
            </div>

            <div class="col-12 col-md-6">
              <span class="text-muted d-block">NGÀY SINH</span>
              <strong class="text-dark"><?= htmlspecialchars($user_dob) ?></strong>
            </div>

            <div class="col-12">
              <span class="text-muted d-block">ĐỊA CHỈ NHẬN HÀNG</span>
              <strong class="text-dark"><?= htmlspecialchars($user_address) ?></strong>
            </div>
          </div>
        </div>

        <!-- Khối Nhạc Cụ Quan Tâm / Bộ Sưu Tập -->
        <div class="card-dashboard p-4 shadow-sm mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h2 class="fs-6 fw-bold text-dark mb-0"><i class="fa-solid fa-guitar text-secondary me-2"></i>Nhạc Cụ & Thiết Bị Của Tôi</h2>
            <a href="gio-hang.php" class="small text-decoration-none fw-semibold" style="color: var(--wood-primary);">Xem giỏ hàng →</a>
          </div>

          <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border">
            <i class="fa-solid fa-bag-shopping fs-3 text-muted"></i>
            <div>
              <div class="fw-semibold text-dark small">Bạn có thể chọn thêm nhạc cụ thủ công vào giỏ hàng bất cứ lúc nào.</div>
              <span class="text-muted" style="font-size: 0.75rem;">Mỗi sản phẩm mua sắm sẽ được lưu trữ thông tin Serial & Bảo hành tại đây.</span>
            </div>
          </div>
        </div>

      </div>

    </div>
  </main>

</body>
</html>