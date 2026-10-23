<?php
// login.php
$page_title = "Đăng Nhập Tài Khoản | GOCNHACCU";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['loginUsername'] ?? '');
    $password = trim($_POST['loginPassword'] ?? '');
    
    if (!empty($username) && !empty($password)) {
        
        $login_success = false;
        $logged_in_name = '';

        if ($username === "admin@harmony.com" && $password === "Abc@12345") {
            $login_success = true;
            $logged_in_name = "Quản trị viên GOCNHACCU";
        } 
        elseif (isset($_SESSION['users']) && is_array($_SESSION['users'])) {
            foreach ($_SESSION['users'] as $user) {
                if ($user['email'] === $username && $user['password'] === $password) {
                    $login_success = true;
                    $logged_in_name = $user['name'];
                    
                    $_SESSION['registered_email'] = $user['email'];
                    $_SESSION['registered_password'] = $user['password'];
                    $_SESSION['registered_name'] = $user['name'];
                    $_SESSION['registered_phone'] = $user['phone'];
                    $_SESSION['registered_dob'] = $user['dob'];
                    $_SESSION['registered_address'] = $user['address'];
                    break;
                }
            }
        }

        if ($login_success) {
            $_SESSION['user'] = $username;
            $_SESSION['user_name'] = $logged_in_name;
            $_SESSION['user_avatar'] = "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80";
            
            header("Location: index.php");
            exit;
        } else {
            $error_message = 'Tên tài khoản (Email) hoặc mật khẩu không chính xác! Vui lòng kiểm tra lại.';
        }

    } else {
        $error_message = 'Vui lòng nhập đầy đủ email và mật khẩu!';
    }
}
?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?></title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
    .form-control:focus {
      border-color: var(--wood-primary);
      box-shadow: 0 0 0 0.25rem rgba(140, 88, 53, 0.2);
    }
  </style>
</head>

<body class="d-flex align-items-center justify-content-center py-5">
  <main class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-9 col-lg-6 col-xl-5">
        
        <div class="bg-white rounded-4 p-4 p-md-5 shadow-sm border" style="border-color: var(--wood-border) !important;">
          <div class="text-center mb-4">
            <a href="index.php" class="text-decoration-none">
              <span class="fs-3 fw-bold font-serif" style="color: var(--wood-text);">GOCNHACCU</span>
            </a>
            <h2 class="fs-5 fw-bolder text-dark mt-2">
              Đăng nhập tài khoản
            </h2>
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger py-2 text-center small mb-4" role="alert">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>

          <!-- Bỏ trống giá trị value, sử dụng autocomplete="off" để ngăn trình duyệt điền sẵn -->
          <form class="login-form" method="POST" action="">
            <div class="row mb-3">
              <div class="col-12 mb-3">
                <label for="loginUsername" class="form-label small fw-bold text-secondary">Email tài khoản</label>
                <input class="form-control rounded-pill px-3 py-2 text-sm" type="email" id="loginUsername" name="loginUsername"
                  placeholder="Nhập email của bạn" required autocomplete="off" value="" />
              </div>

              <div class="col-12 mb-3">
                <label for="loginPassword" class="form-label small fw-bold text-secondary">Mật khẩu</label>
                <div class="position-relative">
                  <input class="form-control rounded-pill px-3 py-2 pe-5 text-sm" type="password" id="loginPassword" name="loginPassword"
                    placeholder="Nhập mật khẩu" required autocomplete="off" value="" />
                  <i class="password-toggle fa-solid fa-eye position-absolute top-50 end-0 translate-middle-y me-3 cursor-pointer text-muted"
                    data-target="loginPassword" role="button"></i>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-12 mb-2">
                <button type="submit" class="btn btn-wood w-100 rounded-pill py-2.5 fw-bold shadow-sm">
                  Đăng nhập
                </button>
              </div>
              <div class="col-12 text-center mt-2">
                <a class="text-decoration-none small text-muted" href="reset_password.php">Quên mật khẩu?</a>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <a class="btn btn-light w-100 border rounded-pill py-2 text-dark fw-semibold small" href="register.php">
                  Tạo tài khoản mới
                </a>
              </div>
            </div>
          </form>
        </div>

      </div>
    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const toggleIcons = document.querySelectorAll('.password-toggle');
      toggleIcons.forEach(icon => {
        icon.addEventListener('click', function () {
          const targetId = this.getAttribute('data-target');
          const passwordInput = document.getElementById(targetId);
          if (passwordInput) {
            if (passwordInput.type === 'password') {
              passwordInput.type = 'text';
              this.classList.remove('fa-eye');
              this.classList.add('fa-eye-slash');
            } else {
              passwordInput.type = 'password';
              this.classList.remove('fa-eye-slash');
              this.classList.add('fa-eye');
            }
          }
        });
      });
    });
  </script>
</body>

</html>