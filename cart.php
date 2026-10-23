<?php
// cart.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Giỏ Hàng";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/app.php';

$cart = $_SESSION['cart'] ?? [];
$total_amount = 0;
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
  
  <!-- FontAwesome Icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <style>
    :root {
      --wood-primary: #8C5835;
      --wood-dark: #754728;
      --wood-bg: #FBF8F5;
      --wood-border: #E7E0D8;
      --wood-text: #2D2319;
    }
    header a {
        text-decoration: none !important;
    }
    body {
      background-color: var(--wood-bg);
      color: var(--wood-text);
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      min-height: 100vh;
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

    .product-img {
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 10px;
    }
  </style>
</head>

<body class="py-5">
  <main class="container">

    <h1 class="fs-4 fw-bold mb-4 mt-3"><i class="fa-solid fa-cart-shopping me-2" style="color: var(--wood-primary);"></i>Giỏ hàng của bạn</h1>

    <?php if (empty($cart)): ?>
      <div class="bg-white rounded-4 p-5 text-center shadow-sm border" style="border-color: var(--wood-border) !important;">
        <i class="fa-solid fa-basket-shopping text-muted mb-3" style="font-size: 4rem;"></i>
        <h2 class="fs-5 fw-bold text-dark mb-2">Giỏ hàng của bạn đang trống</h2>
        <p class="text-muted small mb-4">Hãy lựa chọn những nhạc cụ tuyệt vời cho riêng bạn nhé!</p>
        <a href="products.php" class="btn btn-wood rounded-pill px-4 py-2 fw-semibold">Khám phá sản phẩm ngay</a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        
        <div class="col-12 col-lg-8">
          <div class="bg-white rounded-4 p-4 shadow-sm border" style="border-color: var(--wood-border) !important;">
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr class="text-secondary small">
                    <th>Sản phẩm</th>
                    <th>Đơn giá</th>
                    <th class="text-center">Số lượng</th>
                    <th class="text-end">Thành tiền</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cart as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total_amount += $subtotal;
                  ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-3">
                          <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="product-img border" />
                          <div>
                            <div class="fw-bold text-dark small mb-1"><?= htmlspecialchars($item['name']) ?></div>
                            <span class="badge bg-light text-secondary border">Mã SP: #<?= htmlspecialchars($item['id']) ?></span>
                          </div>
                        </div>
                      </td>
                      <td class="small fw-semibold"><?= number_format($item['price'], 0, ',', '.') ?> đ</td>
                      <td>
                        <div class="d-flex align-items-center justify-content-center gap-1">
                          <form action="../actions/cart_update.php" method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>" />
                            <input type="hidden" name="action" value="decrease" />
                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-circle px-2 py-0" style="width: 28px; height: 28px;">-</button>
                          </form>

                          <span class="fw-bold px-2 small"><?= $item['quantity'] ?></span>

                          <form action="../actions/cart_update.php" method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>" />
                            <input type="hidden" name="action" value="increase" />
                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-circle px-2 py-0" style="width: 28px; height: 28px;">+</button>
                          </form>
                        </div>
                      </td>
                      <td class="text-end fw-bold text-dark small"><?= number_format($subtotal, 0, ',', '.') ?> đ</td>
                      <td class="text-end">
                        <a href="../actions/cart_remove.php?id=<?= $item['id'] ?>" class="text-danger small ms-2" title="Xóa sản phẩm">
                          <i class="fa-regular fa-trash-can"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <div class="bg-white rounded-4 p-4 shadow-sm border" style="border-color: var(--wood-border) !important;">
            <h2 class="fs-5 fw-bold text-dark mb-3 pb-2 border-bottom">Tóm tắt đơn hàng</h2>
            
            <div class="d-flex justify-content-between mb-2 small text-secondary">
              <span>Tạm tính</span>
              <span><?= number_format($total_amount, 0, ',', '.') ?> đ</span>
            </div>
            <div class="d-flex justify-content-between mb-3 small text-secondary">
              <span>Phí vận chuyển</span>
              <span class="text-success fw-bold">Miễn phí</span>
            </div>

            <hr style="border-color: var(--wood-border);" />

            <div class="d-flex justify-content-between mb-4">
              <span class="fw-bold text-dark">Tổng thanh toán</span>
              <span class="fs-5 fw-bold" style="color: var(--wood-primary);"><?= number_format($total_amount, 0, ',', '.') ?> đ</span>
            </div>

            <button class="btn btn-wood w-100 rounded-pill py-2.5 fw-bold shadow-sm mb-2" onclick="alert('Cảm ơn bạn! Đơn hàng của bạn đã được ghi nhận.')">
              Tiến Hành Thanh Toán
            </button>
          </div>
        </div>

      </div>
    <?php endif; ?>

  </main>
  
</body>

</html>